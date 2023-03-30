class Product {
    constructor() {
        this.setEvents();
    }

    setEvents() {
        const period = document.querySelector("#_subscription_period");
        const type   = document.querySelector("#product-type");
        const middle = document.querySelector("#_subscription_period_interval");

        const elements = [period, type, middle];

        this.showCustom(this.handleMaxInstallments());
        elements.forEach(element => {
            if (element) {
                element.addEventListener("change", () => {
                    this.showCustom(this.handleMaxInstallments());
                });
            }
        });
    }

    showCustom(show) {
        const custom = document.querySelector("#vindi_max_credit_installments");

        if (!custom) return;

        const parent = custom.parentElement;
        if (show) {
            parent.style.display = "block";

            this.setMaxInstallments(show);
        } else {
            parent.style.display = "none";
        }

    }

    handleMaxInstallments() {
        const period = document.querySelector("#_subscription_period");
        const middle = document.querySelector("#_subscription_period_interval");
        const type   = document.querySelector("#product-type");
        const installments = this.getMaxInstallments(period, middle);

        if (type.value.includes("subscription") && installments) {
            return installments;
        }

        return false;
    }

    getMaxInstallments(period, middle) {
        if ( period.value === 'year' ) {
            return 12;
        }

        if( period.value === 'month' && middle.value) {
            if (middle.value != 1) {
                return middle.value;
            }
        }

        return false;
    }

    setMaxInstallments(max) {
        if (!max) return;

        const custom = document.querySelector("#vindi_max_credit_installments");
        if (custom) {
            custom.setAttribute("max", max);

            if (parseInt(custom.value) > parseInt(max)) {
                custom.value = max;
            }
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    new Product;
})