<?php

/**
* Redirects the user to create an account if he wants
* to go to the cart without an account
* @since 1.0.0
* @version 1.0.0
*/

function VindiRedirectToMyAccount() {
  if (
      (! is_user_logged_in() && get_option('woocommerce_enable_guest_checkout') == "no") 
      && (is_checkout()) 
  ) {
      wp_redirect(get_permalink( wc_get_page_id( 'myaccount' ) ));
      exit;
  }
}
  add_action('template_redirect', 'VindiRedirectToMyAccount');



function VindiRedirectInfo() {

  if(is_user_logged_in() || get_option('woocommerce_enable_guest_checkout') == "yes") return;
  ?>
  <div class="woocommerce-info">
    <span>
      <?php _e('Para finalizar sua compra, é necessário estar logado', VINDI); ?>
      <a href="<?php echo get_permalink( wc_get_page_id( 'myaccount' ) ); ?>">
      <?php _e('Clique aqui para acessar uma conta existente ou criá-la.', VINDI ); ?>
    </a>
    </span>

  </div>
<?php }

  add_action('woocommerce_before_cart', 'VindiRedirectInfo');
?>