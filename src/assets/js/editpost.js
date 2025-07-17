jQuery(document).ready(function ($) {
    if (orderData.isOrderPage) {
        let side = document.querySelector(".page-title-action");
        if (side && !document.querySelector('#vindi-payment-link-btn')) {
            let button = document.createElement('a');
            button.id = 'vindi-payment-link-btn';
            button.className = 'button button-primary';
            button.style.marginLeft = '10px';
            button.style.marginTop = '10px';
            button.setAttribute("target", "_blank");
            button.innerText = "Gerar Link de Pagamento";
            button.setAttribute("href", `${location.origin}/wp-admin/post-new.php?post_type=shop_order&vindi-payment-link=true`);
            side.after(button);
        }
    }
});
