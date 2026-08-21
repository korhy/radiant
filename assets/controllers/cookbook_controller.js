import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'grid', 'noResult', 'emptyState', 'loadMoreContainer', 'button', 'buttonLabel',
        'status', 'searchInput', 'categorySelect', 'sortBtn',
    ]

    static values = {
        nextPage: Number,
        loadingLabel: String,
        loadMoreLabel: String,
        unavailableLabel: String,
    }

    #loading = false
    #observer = null
    #sortField = null
    #sortDir = null
    #debounceTimer = null
    #generation = 0

    connect() {
        this.#observer = new IntersectionObserver(entries => {
            if (entries[entries.length - 1].isIntersecting) this.loadMore()
        }, { rootMargin: '200px' })

        this.#observer.observe(this.loadMoreContainerTarget)

        this.searchInputTarget.addEventListener('input', () => {
            clearTimeout(this.#debounceTimer)
            this.#debounceTimer = setTimeout(() => this.#reset(), 400)
        })

        this.categorySelectTarget.addEventListener('change', () => this.#reset())
    }

    disconnect() {
        clearTimeout(this.#debounceTimer)
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

        const page = this.nextPageValue
        const generation = this.#generation
        this.#loading = true
        this.#showLoading()

        let data
        try {
            const res = await fetch(`/app/cookbook/recipes?${this.#params(page)}`)

            // Without this guard, the empty payload of a 503 would read as
            // "no result" and show the wrong message.
            if (!res.ok) throw new Error(`Cookbook answered ${res.status}`)

            data = await res.json()
        } catch {
            // A newer request already owns the UI: stay quiet rather than
            // reporting an outage over its results.
            if (generation === this.#generation) {
                this.#loading = false
                this.#showUnavailable()
            }

            return
        }

        // Same reasoning: these results answer a query the visitor has replaced.
        if (generation !== this.#generation) return

        this.#loading = false

        data.recipes.forEach(recipe => {
            this.gridTarget.insertAdjacentHTML('beforeend', this.#cardHtml(recipe))
        })

        if (data.recipes.length === 0 && page === 1) {
            this.noResultTarget.classList.remove('hidden')
        }

        // Paging stops when the API says so, but also when a page brings nothing
        // or fails to move forward: both would let the re-arming below spin.
        if (!data.hasNextPage || data.recipes.length === 0 || data.nextPage <= page) {
            this.#endOfStream()

            return
        }

        this.nextPageValue = data.nextPage
        this.#showLoadMore()
        this.#rearmObserver()
    }

    #rearmObserver() {
        // An IntersectionObserver reports transitions, not states. On a document
        // barely taller than the viewport the sentinel never leaves the 200px
        // margin, so the first callback is also the last one and paging stops
        // halfway. Observing it again asks the browser for a fresh verdict on
        // the layout the page we just appended produced.
        this.#observer.unobserve(this.loadMoreContainerTarget)
        this.#observer.observe(this.loadMoreContainerTarget)
    }

    #reset() {
        // Anything still in flight belongs to the previous query.
        this.#generation += 1
        this.#loading = false

        this.gridTarget.innerHTML = ''
        this.gridTarget.classList.remove('hidden')
        this.noResultTarget.classList.add('hidden')
        this.emptyStateTarget.classList.add('hidden')

        this.nextPageValue = 1
        this.loadMore()
    }

    #showLoading() {
        this.loadMoreContainerTarget.classList.remove('hidden')
        this.buttonTarget.classList.remove('hidden')
        this.buttonTarget.setAttribute('aria-busy', 'true')
        this.buttonLabelTarget.textContent = this.loadingLabelValue
        this.buttonLabelTarget.classList.add('animate-pulse')
        this.statusTarget.classList.add('hidden')
        this.statusTarget.textContent = ''
    }

    #showLoadMore() {
        this.buttonTarget.setAttribute('aria-busy', 'false')
        this.buttonLabelTarget.textContent = this.loadMoreLabelValue
        this.buttonLabelTarget.classList.remove('animate-pulse')
    }

    #showUnavailable() {
        this.nextPageValue = 0
        this.buttonTarget.classList.add('hidden')
        this.statusTarget.textContent = this.unavailableLabelValue
        this.statusTarget.classList.remove('hidden')
    }

    #endOfStream() {
        // A zeroed next page is what closes `loadMore` to both the observer and
        // the button, so the hidden sentinel cannot be reawakened by either.
        this.nextPageValue = 0
        this.loadMoreContainerTarget.classList.add('hidden')
    }

    #params(page) {
        const params = new URLSearchParams({ page })
        const query = this.searchInputTarget.value.trim()
        const category = this.categorySelectTarget.value
        if (query) params.set('query', query)
        if (category) params.set('category', category)
        if (this.#sortField) params.set(`order[${this.#sortField}]`, this.#sortDir)

        return params
    }

    #updateSortButtons() {
        this.sortBtnTargets.forEach(btn => {
            const isActive = btn.dataset.sort === this.#sortField
            btn.classList.toggle('border-brand-fg', isActive)
            btn.classList.toggle('text-brand-fg-em', isActive)
            btn.classList.toggle('border-line-control', !isActive)
            btn.classList.toggle('text-content-low', !isActive)
            btn.setAttribute('aria-pressed', String(isActive))
            btn.textContent = isActive
                ? (this.#sortDir === 'asc' ? btn.dataset.labelAsc : btn.dataset.labelDesc)
                : btn.dataset.label
        })
    }

    #cardHtml(recipe) {
        const thumbnail = recipe.thumbnail
            ? `<img src="${recipe.thumbnail}" alt="${recipe.title}" class="w-full h-40 object-cover group-hover:brightness-110 transition-all duration-200">`
            : `<div class="w-full h-40 bg-surface-inset flex items-center justify-center">
                   <svg class="w-10 h-10 text-content-min" fill="currentColor" viewBox="0 0 24 24">
                       <path d="M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
                   </svg>
               </div>`;

        const category = recipe.category
            ? `<span class="bg-brand/20 text-brand-fg-em text-xs font-medium px-2 py-0.5 rounded-full">${recipe.category.name}</span>`
            : '';

        const duration = recipe.duration
            ? `<span class="text-content-low text-xs flex items-center gap-1 ml-auto">
                   <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                       <circle cx="12" cy="12" r="10"/>
                       <path d="M12 6v6l4 2"/>
                   </svg>
                   ${recipe.duration} min
               </span>`
            : '';

        return `<a href="/app/cookbook/recipe/${recipe.id}"
                   class="group bg-surface-raised rounded-2xl overflow-hidden shadow-lg hover:shadow-brand/10 hover:-translate-y-1 transition-all duration-200">
                    ${thumbnail}
                    <div class="p-4 flex flex-col gap-2">
                        <h2 class="text-content-max font-semibold text-sm leading-snug group-hover:text-brand-fg-em transition-colors">
                            ${recipe.title}
                        </h2>
                        <div class="flex items-center gap-2 flex-wrap mt-auto">
                            ${category}${duration}
                        </div>
                    </div>
                </a>`;
    }
}
