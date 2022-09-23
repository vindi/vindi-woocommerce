class Product {
    constructor() {
        this.setEvents();
    }

    setEvents() {
        const period = document.querySelector("#_subscription_period");
        const type   = document.querySelector("#product-type");
        if (!type || !period) return;

        this.showCustom();
        period.addEventListener("change", () => {
            this.showCustom();
        });

        type.addEventListener("change", () => {
            this.showCustom();
        });
    }

    showCustom() {
        const custom = document.querySelector("#vindi_max_credit_installments");
        const period = document.querySelector("#_subscription_period");
        const type   = document.querySelector("#product-type");

        if (!custom) return;

        const parent = custom.parentElement;
        if (period.value === 'year' && type.value.includes("subscription") ) {
            parent.style.display = "block";
        } else {
            parent.style.display = "none";
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    new Product;
})