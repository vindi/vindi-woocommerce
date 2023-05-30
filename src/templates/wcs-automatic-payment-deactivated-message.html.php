<?php if (!defined('ABSPATH')) exit; ?>

<div class="error notice">
    <h3>
        <?php
            echo __('Configuração da renovação de assinaturas', VINDI);
        ?>
    </h3>
    <p>
        O plugin Vindi agora utiliza os mecanismos do WooCommerce para gerenciar as
         renovações das assinaturas.<br>Para isso, você precisa marcar a opção "
        <i>
            <?php
                echo __('Turn off Automatic Payments', 'woocommerce-subscriptions');
            ?>
        </i>" nas
        <a href="admin.php?page=wc-settings&tab=subscriptions">
             configurações do WooCommerce Subscriptions
        </a>.
    </p>
</div>
