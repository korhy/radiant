import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['loadMoreContainer', 'button']
    static values = { nextPage: Number }

    #loading = false
    #observer = null

    connect() {
        this.#observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) this.loadMore()
        }, { rootMargin: '200px' })

        if (this.hasLoadMoreContainerTarget) {
            this.#observer.observe(this.loadMoreContainerTarget)
        }
    }

    disconnect() {
        this.#observer?.disconnect()
    }

    async loadMore() {
        if (!this.nextPageValue || this.#loading) return
        this.#loading = true
        this.buttonTarget.textContent = 'Chargement…'

        const res = await fetch(`/app/cookbook/recipes?page=${this.nextPageValue}`)
        const data = await res.json()

        const grid = document.getElementById('recipe-grid')
        data.recipes.forEach(recipe => {
            grid.insertAdjacentHTML('beforeend', this.#cardHtml(recipe))
        })

        if (data.hasNextPage) {
            this.nextPageValue = data.nextPage
            this.buttonTarget.textContent = '↓'
            this.#loading = false
        } else {
            this.loadMoreContainerTarget.remove()
            this.#observer.disconnect()
        }
    }

    #cardHtml(recipe) {
        const thumbnail = recipe.thumbnail
            ? `<img src="${recipe.thumbnail}" alt="${recipe.title}" class="w-full h-40 object-cover group-hover:brightness-110 transition-all duration-200">`
            : `<div class="w-full h-40 bg-slate-700 flex items-center justify-center">
                   <svg class="w-10 h-10 text-slate-500" fill="currentColor" viewBox="0 0 24 24">
                       <path d="M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
                   </svg>
               </div>`;

        const category = recipe.category
            ? `<span class="bg-amber-500/20 text-amber-400 text-xs font-medium px-2 py-0.5 rounded-full">${recipe.category.name}</span>`
            : '';

        const duration = recipe.duration
            ? `<span class="text-slate-400 text-xs flex items-center gap-1 ml-auto">
                   <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                       <circle cx="12" cy="12" r="10"/>
                       <path d="M12 6v6l4 2"/>
                   </svg>
                   ${recipe.duration} min
               </span>`
            : '';

        return `<a href="/app/cookbook/recipe/${recipe.id}"
                   class="group bg-slate-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-amber-500/10 hover:-translate-y-1 transition-all duration-200">
                    ${thumbnail}
                    <div class="p-4 flex flex-col gap-2">
                        <h2 class="text-white font-semibold text-sm leading-snug group-hover:text-amber-400 transition-colors">
                            ${recipe.title}
                        </h2>
                        <div class="flex items-center gap-2 flex-wrap mt-auto">
                            ${category}${duration}
                        </div>
                    </div>
                </a>`;
    }
}