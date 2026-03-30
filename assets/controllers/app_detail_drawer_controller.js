import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['drawer', 'overlay', 'panel', 'tab'];

    connect() {
        this.#activateTab(this.tabTargets[0]);
    }

    open() {
        this.drawerTarget.classList.remove('translate-x-full');
        this.overlayTarget.classList.remove('hidden');
    }

    close() {
        this.drawerTarget.classList.add('translate-x-full');
        this.overlayTarget.classList.add('hidden');
    }

    switchTab(event) {
        this.#activateTab(event.currentTarget);
    }

    #activateTab(activeTab) {
        const activeKey = activeTab.dataset.tab;

        this.tabTargets.forEach(tab => {
            const isActive = tab.dataset.tab === activeKey;
            tab.classList.toggle('text-white', isActive);
            tab.classList.toggle('border-b-2', isActive);
            tab.classList.toggle('border-amber-400', isActive);
            tab.classList.toggle('text-slate-400', !isActive);
        });

        this.panelTargets.forEach(panel => {
            panel.classList.toggle('hidden', panel.dataset.tab !== activeKey);
        });
    }
}
