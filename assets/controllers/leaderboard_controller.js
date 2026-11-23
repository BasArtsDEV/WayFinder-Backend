import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['search', 'filter', 'row', 'check', 'checkAll', 'removeSelected', 'count', 'noResult'];

    connect() {
        if (this.hasRowTarget) {
            this.updateSelection();
        }
    }

    filter() {
        const search = this.searchTarget.value.trim().toLowerCase();
        const type = this.filterTarget.value;
        let visibleRows = 0;

        this.rowTargets.forEach((row) => {
            const matchesType = type === 'all'
                || row.dataset.type === type
                || (type === 'timed-out' && row.dataset.timedOut === '1');

            row.hidden = !(matchesType && row.dataset.name.includes(search));

            if (row.hidden) {
                row.querySelector('[data-leaderboard-target="check"]').checked = false;
            } else {
                visibleRows++;
            }
        });

        this.noResultTarget.hidden = visibleRows > 0;
        this.updateSelection();
    }

    toggleAll() {
        this.checkTargets.forEach((check) => {
            if (!check.closest('tr').hidden) {
                check.checked = this.checkAllTarget.checked;
            }
        });

        this.updateSelection();
    }

    updateSelection() {
        const selected = this.checkTargets.filter((check) => check.checked).length;
        const visible = this.checkTargets.filter((check) => !check.closest('tr').hidden).length;

        this.countTarget.textContent = selected;
        this.removeSelectedTarget.disabled = selected === 0;
        this.checkAllTarget.checked = selected > 0 && selected === visible;
    }
}
