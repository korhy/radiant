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

        // Without this guard, the empty payload of a 503 would read as
        // "no result" and show the wrong message.
        if (!res.ok) {
            if (this.hasButtonTarget) this.buttonTarget.textContent = 'Recettes momentanément indisponibles'
            this.#observer?.disconnect()
            this.#loading = false
            return
        }

        const data = await res.json()

        const grid = document.getElementById('recipe-grid')
        if (data.empty) {
            grid.innerHTML = `<div class="col-span-full text-center py-12 text-content-low">Aucune recette ne correspond à ces critères.</div>`
            if (this.hasLoadMoreContainerTarget) this.loadMoreContainerTarget.remove()
            this.#observer.disconnect()
            this.#loading = false
            return
        }

        // Server-rendered markup: Twig escaped every field, the browser only inserts it.
        grid.insertAdjacentHTML('beforeend', data.html)

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
        container.innerHTML = `<span data-cookbook-target="button" class="text-content-min text-sm animate-pulse">Chargement…</span>`
        this.element.appendChild(container)
        this.#observer.observe(container)

        this.nextPageValue = 1
        this.#loading = false
        this.loadMore()
    }

    #updateSortButtons() {
        this.sortBtnTargets.forEach(btn => {
            const isActive = btn.dataset.sort === this.#sortField
            btn.classList.toggle('border-brand-fg', isActive)
            btn.classList.toggle('text-brand-fg-em', isActive)
            btn.classList.toggle('border-line-control', !isActive)
            btn.classList.toggle('text-content-low', !isActive)
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
}
