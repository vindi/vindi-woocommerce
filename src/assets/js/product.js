class Product {
    constructor() {
        this.setEvents();
    }

    setEvents() {
        const period = document.querySelector("#_subscription_period");
        const type   = document.querySelector("#product-type");
        const middle = document.querySelector("#_subscription_period_interval");

        const elements = [period, type, middle];

        this.showCustom(this.getMaxInstallments());
        elements.forEach(element => {
            if (element) {
                element.addEventListener("change", () => {
                    this.showCustom(this.getMaxInstallments());
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

    getMaxInstallments() {
        const period = document.querySelector("#_subscription_period");
        const middle = document.querySelector("#_subscription_period_interval");

        const type   = document.querySelector("#product-type");

        if ( ! type.value.includes("subscription") ) return;

        if ( period.value === 'year' ) {
            return 12;
        }

        if( period.value === 'month' ) {
            if (middle.value) {
                if (middle.value == 1) return;
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

            if (custom.value > max) {
                custom.value = max;
            }
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    new Product;
})