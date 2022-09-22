class Product {
    constructor() {
        this.setEvents();
    }

    setEvents() {
        const type = document.querySelector("#_subscription_period");
        if (!type) return;

        this.showCustom();
        type.addEventListener("change", () => {
            this.showCustom();
        });
    }

    showCustom() {
        const custom = document.querySelector("#vindi_max_credit_installments");
        const type   = document.querySelector("#_subscription_period");
        if (!custom) return;

        const parent = custom.parentElement;
        if (type.value === 'year') {
            parent.style.display = "block";
        } else {
            parent.style.display = "none";
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    new Product;
})