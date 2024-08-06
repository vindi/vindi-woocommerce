<div>
    <div>
        <h3><?php _e('Billing details', 'woocommerce'); ?></h3>
        <?php do_action('woocommerce_before_checkout_billing_form', $order); ?>

        <div class="woocommerce-billing-fields__field-wrapper">
            <?php foreach ($fields as $key => $field) : ?>
                <?php if (is_callable(array($order, 'get_' . $key))) : ?>
                    <?php woocommerce_form_field($key, $field, $order->{"get_$key"}('edit')); ?>
                <?php else : ?>
        <?php woocommerce_form_field($key, array_merge($field, ['required' => true]), $order->get_meta('_' . $key)); ?>
                <?php endif ?>
            <?php endforeach; ?>
        </div>
        <?php do_action('woocommerce_after_checkout_billing_form', $order); ?>
    </div>
</div>

<?php do_action('woocommerce_after_checkout_shipping_form', $order); ?>