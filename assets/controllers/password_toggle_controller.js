import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'button', 'icon'];
    static values = {
        showLabel: String,
        hideLabel: String,
    };

    toggle() {
        const isTypePassword = this.inputTarget.type === 'password';

        this.inputTarget.type = isTypePassword ? 'text' : 'password';

        this.iconTarget.classList.toggle('bi-eye', !isTypePassword);
        this.iconTarget.classList.toggle('bi-eye-slash', isTypePassword);

        this.buttonTarget.setAttribute('aria-pressed', String(isTypePassword));

        const label = isTypePassword ? this.hideLabelValue : this.showLabelValue;
        if (label) {
            this.buttonTarget.setAttribute('aria-label', label);
        }
    }
}
