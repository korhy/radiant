import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['drawer', 'overlay', 'panel', 'tab', 'trigger'];

    #onKeyDown = (event) => {
        if (event.key === 'Escape') this.close();
    };

    connect() {
        this.#activateTab(this.tabTargets[0]);
    }

    disconnect() {
        document.removeEventListener('keydown', this.#onKeyDown);
    }

    open() {
        this.drawerTarget.classList.remove('translate-x-full');
        this.overlayTarget.classList.remove('hidden');

        // `inert` sort le tiroir fermé de l'ordre de tabulation et de l'arbre
        // d'accessibilité : sans ça, ses boutons restent atteignables au clavier
        // alors qu'ils sont hors écran.
        this.drawerTarget.removeAttribute('inert');

        if (this.hasTriggerTarget) this.triggerTarget.setAttribute('aria-expanded', 'true');

        const active = this.tabTargets.find(tab => tab.getAttribute('aria-selected') === 'true');
        (active ?? this.tabTargets[0])?.focus();

        document.addEventListener('keydown', this.#onKeyDown);
    }

    close() {
        this.drawerTarget.classList.add('translate-x-full');
        this.overlayTarget.classList.add('hidden');
        this.drawerTarget.setAttribute('inert', '');

        document.removeEventListener('keydown', this.#onKeyDown);

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'false');
            // Rendre le focus à son point de départ, sinon il retombe sur le <body>.
            this.triggerTarget.focus();
        }
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
            tab.classList.toggle('border-accent-light', isActive);
            tab.classList.toggle('text-content-low', !isActive);
            tab.setAttribute('aria-selected', String(isActive));
            tab.tabIndex = isActive ? 0 : -1;
        });

        this.panelTargets.forEach(panel => {
            panel.classList.toggle('hidden', panel.dataset.tab !== activeKey);
        });
    }
}
