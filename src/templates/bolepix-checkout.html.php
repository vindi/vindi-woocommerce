<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php if ($is_trial) : ?>
    <div style="padding: 10px;border: 1px solid #f00; background-color: #fdd; color: #f00; margin: 10px 2px">
        <h3 style="color: #f00"><?php _e('MODO DE TESTES', VINDI); ?></h3>
        <p>
            <?php _e(
                'Sua conta na Vindi está em <strong>Modo Trial</strong>. 
                Este modo é proposto para a realização de testes e, portanto, 
                nenhum pedido será efetivamente cobrado.',
                VINDI
            ); ?>
        </p>
    </div>
<?php endif; ?>
<fieldset>
    <?php do_action('vindi_bolepix_form_start'); ?>

    <div class="vindi-invoice-description" style="padding: 20px 0; font-weight: bold;">
        <?php
            _e('Após confirmar o pedido, use PIX ou Boleto Bancário para efetuar o pagamento.', VINDI);
        ?>
    </div>
    <div class="clear"></div>

    <?php do_action('vindi_bolepix_form_end'); ?>

    <div class="clear"></div>
</fieldset>
