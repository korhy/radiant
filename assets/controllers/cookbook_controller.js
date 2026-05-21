import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['loadMoreContainer', 'button', 'searchInput', 'categorySelect', 'sortBtn']
    static values = { nextPage: Number }

    #loading = false
    #observer = null
    #sortField = null
    #sortDir = null
    #debounceTimer = null

    connect() {
        this.#observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) this.loadMore()
        }, { rootMargin: '200px' })

        if (this.hasLoadMoreContainerTarget) {
            this.#observer.observe(this.loadMoreContainerTarget)
        }

        this.searchInputTarget.addEventListener('input', () => {
            clearTimeout(this.#debounceTimer)
            this.#debounceTimer = setTimeout(() => this.#reset(), 400)
        })

        this.categorySelectTarget.addEventListener('change', () => this.#reset())
    }

    disconnect() {
        this.#observer?.disconnect()
    }

    sort(event) {
        const field = event.currentTarget.dataset.sort
        if (this.#sortField === field) {
            this.#sortDir = this.#sortDir === 'asc' ? 'desc' : 'asc'
        } else {
            this.#sortField = field
            this.#sortDir = 'asc'
        }
        this.#updateSortButtons()
        this.#reset()
    }

    async loadMore() {
        if (!this.nextPageValue || this.#loading) return
        this.#loading = true
        if (this.hasButtonTarget) this.buttonTarget.textContent = 'Chargement…'

        const params = new URLSearchParams({ page: this.nextPageValue })
        const query = this.searchInputTarget.value.trim()
        const category = this.categorySelectTarget.value
        if (query) params.set('query', query)
        if (category) params.set('category', category)
        if (this.#sortField) params.set(`order[${this.#sortField}]`, this.#sortDir)

        const res = await fetch(`/app/cookbook/recipes?${params}`)
        const data = await res.json()

        const grid = document.getElementById('recipe-grid')
        if (data.recipes.length === 0 && this.nextPageValue === 1) {
            grid.innerHTML = `<div class="col-span-full text-center py-12 text-slate-400">Aucune recette ne correspond à ces critères.</div>`
            if (this.hasLoadMoreContainerTarget) this.loadMoreContainerTarget.remove()
            this.#observer.disconnect()
            this.#loading = false
            return
        }

        data.recipes.forEach(recipe => {
            grid.insertAdjacentHTML('beforeend', this.#cardHtml(recipe))
        })

        if (data.hasNextPage) {
            this.nextPageValue = data.nextPage
            if (this.hasButtonTarget) this.buttonTarget.textContent = '↓'
            this.#loading = false
        } else {
            if (this.hasLoadMoreContainerTarget) this.loadMoreContainerTarget.remove()
            this.#observer.disconnect()
        }
    }

    #reset() {
        const grid = document.getElementById('recipe-grid')
        grid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6'
        grid.innerHTML = ''

        if (this.hasLoadMoreContainerTarget) {
            this.#observer.unobserve(this.loadMoreContainerTarget)
            this.loadMoreContainerTarget.remove()
        }

        const container = document.createElement('div')
        container.setAttribute('data-cookbook-target', 'loadMoreContainer')
        container.className = 'flex justify-center mt-6'
        container.innerHTML = `<span data-cookbook-target="button" class="text-slate-500 text-sm animate-pulse">Chargement…</span>`
        this.element.appendChild(container)
        this.#observer.observe(container)

        this.nextPageValue = 1
        this.#loading = false
        this.loadMore()
    }

    #updateSortButtons() {
        this.sortBtnTargets.forEach(btn => {
            const isActive = btn.dataset.sort === this.#sortField
            btn.classList.toggle('border-amber-500', isActive)
            btn.classList.toggle('text-amber-400', isActive)
            btn.classList.toggle('border-slate-700', !isActive)
            btn.classList.toggle('text-slate-400', !isActive)
            if (isActive) {
                btn.textContent = btn.dataset.sort === 'title'
                    ? (this.#sortDir === 'asc' ? 'Titre A→Z' : 'Titre Z→A')
                    : btn.dataset.sort === 'createdAt'
                        ? (this.#sortDir === 'asc' ? 'Date ↑' : 'Date ↓')
                        : (this.#sortDir === 'asc' ? 'Durée ↑' : 'Durée ↓')
            } else {
                btn.textContent = btn.dataset.sort === 'title' ? 'Titre'
                    : btn.dataset.sort === 'createdAt' ? 'Date' : 'Durée'
            }
        })
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
