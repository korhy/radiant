import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['messages', 'input', 'submit'];
    static values = { url: String };

    /** @type {Array<{role: string, content: string}>} */
    history = [];

    /** @type {string|null} */
    conversationId = null;

    connect() {
        this.#scrollToBottom();
    }

    submitOnEnter(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.submit(event);
        }
    }

    async submit(event) {
        event.preventDefault();

        const message = this.inputTarget.value.trim();
        if (!message || this.#isLoading()) return;

        this.inputTarget.value = '';
        this.#setLoading(true);

        this.#appendUserMessage(message);
        const loadingEl = this.#appendLoading();

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message, history: this.history }),
            });

            loadingEl.remove();

            if (!response.ok) {
                this.#appendError('Une erreur est survenue.');
                this.#setLoading(false);
                return;
            }

            const data = await response.json();
            this.#appendRecipeCard(data.recipe);

            this.conversationId = data.conversationId ?? null;
            this.history.push({ role: 'user', content: message });
            this.history.push({ role: 'assistant', content: data.recipe.title });

        } catch {
            loadingEl.remove();
            this.#appendError('Impossible de contacter le serveur.');
        }

        this.#setLoading(false);
        this.inputTarget.focus();
    }

    reset() {
        this.history = [];
        this.conversationId = null;
        const welcome = this.messagesTarget.querySelector(':first-child');
        this.messagesTarget.innerHTML = '';
        if (welcome) this.messagesTarget.appendChild(welcome);
    }

    #appendUserMessage(content) {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex gap-3 justify-end';
        wrapper.innerHTML = `
            <div class="max-w-2xl">
                <div class="bg-amber-500/10 border border-amber-500/30 text-slate-200 rounded-2xl rounded-tr-sm px-4 py-3 text-sm leading-relaxed">
                    ${this.#escape(content)}
                </div>
            </div>
            <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
        `;
        this.messagesTarget.appendChild(wrapper);
        this.#scrollToBottom();
    }

    /** @returns {HTMLElement} */
    #appendLoading() {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex gap-3 justify-start';
        wrapper.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-amber-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div class="bg-slate-800 border border-slate-700 rounded-2xl rounded-tl-sm px-4 py-3">
                <span class="inline-flex gap-1 text-slate-500">
                    <span class="animate-bounce" style="animation-delay:0ms">·</span>
                    <span class="animate-bounce" style="animation-delay:150ms">·</span>
                    <span class="animate-bounce" style="animation-delay:300ms">·</span>
                </span>
            </div>
        `;
        this.messagesTarget.appendChild(wrapper);
        this.#scrollToBottom();
        return wrapper;
    }

    #appendRecipeCard(recipe) {
        const ingredients = recipe.recipeIngredients
            .map(ri => `<li class="flex gap-2"><span class="text-amber-400 font-medium">${this.#escape(ri.quantity)} ${this.#escape(ri.unit)}</span><span>${this.#escape(ri.ingredient.name)}</span></li>`)
            .join('');

        const steps = recipe.instructions
            .sort((a, b) => a.position - b.position)
            .map(step => `<li class="flex gap-3"><span class="text-amber-500 font-bold flex-shrink-0">${step.position}.</span><span>${this.#escape(step.content)}</span></li>`)
            .join('');

        const category = recipe.category?.name ?? '';

        const wrapper = document.createElement('div');
        wrapper.className = 'flex gap-3 justify-start';
        wrapper.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-amber-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div class="max-w-2xl w-full bg-slate-800 border border-slate-700 rounded-2xl rounded-tl-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between gap-3">
                    <h3 class="text-white font-semibold text-base">${this.#escape(recipe.title)}</h3>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs text-slate-400 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ${recipe.duration} min
                        </span>
                        ${category ? `<span class="text-xs text-slate-500 border border-slate-600 rounded-full px-2 py-0.5">${this.#escape(category)}</span>` : ''}
                    </div>
                </div>
                ${recipe.description ? `<p class="px-4 pt-3 text-slate-400 text-sm italic">${this.#escape(recipe.description)}</p>` : ''}
                <div class="px-4 py-3 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-slate-500 text-xs uppercase tracking-wide mb-2">Ingrédients</p>
                        <ul class="space-y-1 text-slate-300">${ingredients}</ul>
                    </div>
                    <div>
                        <p class="text-slate-500 text-xs uppercase tracking-wide mb-2">Étapes</p>
                        <ol class="space-y-2 text-slate-300">${steps}</ol>
                    </div>
                </div>
            </div>
        `;
        this.messagesTarget.appendChild(wrapper);
        this.#scrollToBottom();
    }

    #appendError(message) {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex gap-3 justify-start';
        wrapper.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="max-w-2xl bg-red-500/10 border border-red-500/30 text-red-300 rounded-2xl rounded-tl-sm px-4 py-3 text-sm">
                ${this.#escape(message)}
            </div>
        `;
        this.messagesTarget.appendChild(wrapper);
        this.#scrollToBottom();
    }

    #isLoading() {
        return this.submitTarget.disabled;
    }

    #setLoading(loading) {
        this.submitTarget.disabled = loading;
        this.inputTarget.disabled = loading;
    }

    #scrollToBottom() {
        this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
    }

    #escape(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
}
