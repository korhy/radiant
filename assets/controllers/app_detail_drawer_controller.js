import { Controller } from '@hotwired/stimulus';

/**
 * Barre d'onglets du panneau « Behind the scenes ».
 *
 * L'ouverture, la fermeture, le piège au focus, Échap, l'inertie de
 * l'arrière-plan et le retour du focus sont assurés par le composant `Dialog`
 * du kit, qui s'appuie sur l'élément natif `<dialog>`. Ce contrôleur ne garde
 * que ce que le navigateur ne fournit pas : le motif `tablist`.
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
     * Navigation aux flèches dans la barre d'onglets, comme l'attend le motif
     * tablist : un seul onglet est dans l'ordre de tabulation à la fois.
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
