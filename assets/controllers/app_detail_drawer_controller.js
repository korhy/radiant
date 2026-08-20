import { Controller } from '@hotwired/stimulus';

/**
 * Tab bar of the "Behind the scenes" panel.
 *
 * Opening, closing, the focus trap, Escape, background inertness and focus
 * restoration all come from the kit's `Dialog` component, built on the native
 * `<dialog>` element. This controller keeps only what the browser does not
 * provide: the `tablist` pattern.
 */
export default class extends Controller {
    static targets = ['panel', 'tab'];

    connect() {
        this.#activateTab(this.tabTargets[0]);
    }

    switchTab(event) {
        this.#activateTab(event.currentTarget);
    }

    /**
     * Arrow-key navigation across the tab bar, as the tablist pattern expects:
     * only one tab sits in the tab order at a time.
     */
    moveTab(event) {
        const step = { ArrowRight: 1, ArrowLeft: -1, Home: 'first', End: 'last' }[event.key];
        if (step === undefined) return;

        event.preventDefault();

        const tabs = this.tabTargets;
        const current = tabs.indexOf(event.currentTarget);
        let next;

        if (step === 'first') next = 0;
        else if (step === 'last') next = tabs.length - 1;
        else next = (current + step + tabs.length) % tabs.length;

        this.#activateTab(tabs[next]);
        tabs[next].focus();
    }

    #activateTab(activeTab) {
        if (!activeTab) return;

        const activeKey = activeTab.dataset.tab;

        this.tabTargets.forEach(tab => {
            const isActive = tab.dataset.tab === activeKey;
            tab.classList.toggle('text-content-max', isActive);
            tab.classList.toggle('border-b-2', isActive);
            tab.classList.toggle('border-brand-light', isActive);
            tab.classList.toggle('text-content-low', !isActive);
            tab.setAttribute('aria-selected', String(isActive));
            tab.tabIndex = isActive ? 0 : -1;
        });

        this.panelTargets.forEach(panel => {
            panel.classList.toggle('hidden', panel.dataset.tab !== activeKey);
        });
    }
}
