import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['loadMoreContainer', 'button', 'searchInput', 'categorySelect', 'sortBtn']
    static values = { nextPage: Number, unavailableLabel: String, recipesUrl: String }

    #loading = false
    #observer = null
    #loadingLabel = ''
    #sortField = null
    #sortDir = null
    #debounceTimer = null

    connect() {
        // Kept from the template so no visible text lives in this file.
        this.#loadingLabel = this.hasButtonTarget ? this.buttonTarget.textContent.trim() : ''

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

        const res = await fetch(`${this.recipesUrlValue}?${params}`)

        // Without this guard, the empty payload of a 503 would read as
        // "no result" and show the wrong message.
        if (!res.ok) {
            if (this.hasButtonTarget) this.buttonTarget.textContent = this.unavailableLabelValue
            this.#observer?.disconnect()
            this.#loading = false
            return
        }

        const data = await res.json()

        const grid = document.getElementById('recipe-grid')
        if (data.empty) {
            grid.innerHTML = data.html
            this.#hideLoadMore()
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
            this.#hideLoadMore()
        }
    }

    #hideLoadMore() {
        if (this.hasLoadMoreContainerTarget) this.loadMoreContainerTarget.classList.add('hidden')
        this.#observer?.disconnect()
    }

    #reset() {
        document.getElementById('recipe-grid').innerHTML = ''

        // The container is part of the page, not something to rebuild: it is
        // only shown again, with the label the template gave it.
        if (this.hasLoadMoreContainerTarget) {
            this.loadMoreContainerTarget.classList.remove('hidden')
            if (this.hasButtonTarget) this.buttonTarget.textContent = this.#loadingLabel
            this.#observer.observe(this.loadMoreContainerTarget)
        }

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
