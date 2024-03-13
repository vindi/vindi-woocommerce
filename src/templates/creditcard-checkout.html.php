<?php if (!defined('ABSPATH')) exit; ?>

<?php if ($is_trial): ?>
  <div style="padding: 10px;border: 1px solid #f00; background-color: #fdd; color: #f00; margin: 10px 2px">
    <h3 style="color: #f00"><?php _e( 'MODO DE TESTES', VINDI ); ?></h3>
    <p>
      <?php _e('Sua conta na Vindi está em <strong>Modo Trial</strong>. Este modo é proposto para a realização de testes e, portanto, nenhum pedido será efetivamente cobrado.', VINDI); ?>
    </p>
  </div>
<?php endif; ?>

<fieldset class="vindi-fieldset">

<?php
if (isset($id)) {
    do_action('vindi_credit_card_form_start', $id);
}
?>

  <?php if(!empty($user_payment_profile)): ?>
    <div class="vindi-old-cc-data">
      <p class="form-row">
        <label>
          <?php _e("Cartão Cadastrado", VINDI); ?>
        </label>
        <br>
        <?php echo $user_payment_profile['holder_name']; ?><br>
        <div class="vindi-old-paymentcompany" style="background: url('https://s3.amazonaws.com/recurrent/payment_companies/<?php echo $user_payment_profile['payment_company']?>.png') no-repeat center right; background-size: auto 90%;">
          <?php echo $user_payment_profile['card_number']; ?>
        </div>
        <input class="vindi-old-cc-data-check" type="hidden" value='1' name="vindi-old-cc-data-check">
      </p>

      <p class="form-row">
        <a href="#" class="vindi-change-card"><?php echo __('usar outro cartão', VINDI); ?></a>
      </p>
    </div>
  <?php endif; ?>

  <div class='vindi-new-cc-data' style="<?php if(!empty($user_payment_profile)) echo 'display: none'; ?>">
    <div class="vindi_cc_card-container vindi_cc_preload">
      <div class="vindi_cc_creditcard">
        <div class="front">
          <div id="vindi_cc_ccsingle">
            <img
                src="
                <?php echo esc_url(
                    get_site_url() .
                        '/wp-content/plugins/vindi-payment-gateway/src/assets/images/card/single/unknown.svg'
                )
                ?>"
                data-img="single/unknown">
            </img>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="vindi_cc_cardfront" x="0px" y="0px" viewBox="0 0 750 471" style="enable-background:new 0 0 750 471;" xml:space="preserve">
            <mask id="vindi_cc_lightmask" x="0" y="0" width="600" height="370" maskUnits="userSpaceOnUse">
              <g id="mask0">
                <rect class="cls-1" x="0" y="0" width="600" height="370" fill="#fff"/>
              </g>
            </mask>
            <g id="Front">
              <g id="CardBackground">
                <g id="Page-1_1_">
                  <g id="amex_1_">
                    <path id="Rectangle-1_1_" class="vindi_cc_cardcolor greydark" d="M40,0h670c22.1,0,40,17.9,40,40v391c0,22.1-17.9,40-40,40H40c-22.1,0-40-17.9-40-40V40C0,17.9,17.9,0,40,0z" />
                  </g>
                </g>
                <g xmlns="http://www.w3.org/2000/svg" mask="url(#vindi_cc_lightmask)">
                  <path xmlns="http://www.w3.org/2000/svg" class="vindi_cc_lightcolor" d="M 0 0 V 370 C 262 360 570 198 600 0"/>
                </g>
              </g>
              <text transform="matrix(1 0 0 1 60.106 295.0121)" id="vindi_cc_svgnumber" class="st2 st3 st4">0123 4567 8910 1112</text>
              <text transform="matrix(1 0 0 1 54.1064 428.1723)" id="vindi_cc_svgname" class="st2 st5 st6">JOÃO DA SILVA</text>
              <text transform="matrix(1 0 0 1 54.1074 389.8793)" class="st7 st5 st8"><?php _e("Nome", VINDI); ?></text>
              <text transform="matrix(1 0 0 1 479.7754 388.8793)" class="st7 st5 st8"><?php _e("Validade", VINDI); ?></text>
              <text transform="matrix(1 0 0 1 65.1054 241.5)" class="st7 st5 st8"><?php _e("Número do Cartão", VINDI); ?></text>
              <g>
                <text transform="matrix(1 0 0 1 574.4219 433.8095)" id="vindi_cc_svgexpire" class="st2 st5 st9">01/23</text>
                <text transform="matrix(1 0 0 1 479.3848 417.0097)" class="st2 st10 st11">VALID</text>
                <text transform="matrix(1 0 0 1 479.3848 435.6762)" class="st2 st10 st11">THRU</text>
                <polygon class="st2" points="554.5,421 540.4,414.2 540.4,427.9"/>
              </g>
              <g id="cchip">
                <g>
                  <path class="st2" d="M168.1,143.6H82.9c-10.2,0-18.5-8.3-18.5-18.5V74.9c0-10.2,8.3-18.5,18.5-18.5h85.3 c10.2,0,18.5,8.3,18.5,18.5v50.2C186.6,135.3,178.3,143.6,168.1,143.6z"/>
                </g>
                <g>
                  <g>
                    <rect x="82" y="70" class="st12" width="1.5" height="60"/>
                  </g>
                  <g>
                    <rect x="167.4" y="70" class="st12" width="1.5" height="60"/>
                  </g>
                  <g>
                    <path class="st12" d="M125.5,130.8c-10.2,0-18.5-8.3-18.5-18.5c0-4.6,1.7-8.9,4.7-12.3c-3-3.4-4.7-7.7-4.7-12.3               c0-10.2,8.3-18.5,18.5-18.5s18.5,8.3,18.5,18.5c0,4.6-1.7,8.9-4.7,12.3c3,3.4,4.7,7.7,4.7,12.3               C143.9,122.5,135.7,130.8,125.5,130.8z M125.5,70.8c-9.3,0-16.9,7.6-16.9,16.9c0,4.4,1.7,8.6,4.8,11.8l0.5,0.5l-0.5,0.5               c-3.1,3.2-4.8,7.4-4.8,11.8c0,9.3,7.6,16.9,16.9,16.9s16.9-7.6,16.9-16.9c0-4.4-1.7-8.6-4.8-11.8l-0.5-0.5l0.5-0.5               c3.1-3.2,4.8-7.4,4.8-11.8C142.4,78.4,134.8,70.8,125.5,70.8z"/>
                  </g>
                  <g>
                    <rect x="82.8" y="82.1" class="st12" width="25.8" height="1.5"/>
                  </g>
                  <g>
                    <rect x="82.8" y="117.9" class="st12" width="26.1" height="1.5"/>
                  </g>
                  <g>
                    <rect x="142.4" y="82.1" class="st12" width="25.8" height="1.5"/>
                  </g>
                  <g>
                    <rect x="142" y="117.9" class="st12" width="26.2" height="1.5"/>
                  </g>
                </g>
              </g>
            </g>
            <g id="Back">
            </g>
          </svg>
        </div>
        <div class="back">
          <svg version="1.1" id="vindi_cc_cardback" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
            x="0px" y="0px" viewBox="0 0 750 471" style="enable-background:new 0 0 750 471;" xml:space="preserve">
            <g id="Front">
                <line class="st0" x1="35.3" y1="10.4" x2="36.7" y2="11" />
            </g>
            <g id="Back">
              <g id="Page-1_2_">
                <g id="amex_2_">
                  <path id="Rectangle-1_2_" class="vindi_cc_cardcolor greydark" d="M40,0h670c22.1,0,40,17.9,40,40v391c0,22.1-17.9,40-40,40H40c-22.1,0-40-17.9-40-40V40C0,17.9,17.9,0,40,0z" />
                </g>
              </g>
              <rect y="61.6" class="st2" width="750" height="78" />
              <g>
                <path class="st3" d="M701.1,249.1H48.9c-3.3,0-6-2.7-6-6v-52.5c0-3.3,2.7-6,6-6h652.1c3.3,0,6,2.7,6,6v52.5C707.1,246.4,704.4,249.1,701.1,249.1z" />
                <rect x="42.9" y="198.6" class="st4" width="664.1" height="10.5" />
                <rect x="42.9" y="224.5" class="st4" width="664.1" height="10.5" />
                <path class="st5" d="M701.1,184.6H618h-8h-10v64.5h10h8h83.1c3.3,0,6-2.7,6-6v-52.5C707.1,187.3,704.4,184.6,701.1,184.6z" />
              </g>
              <text transform="matrix(1 0 0 1 621.999 227.2734)" id="vindi_cc_svgsecurity" class="st6 st7">985</text>
              <g class="st8">
                <text transform="matrix(1 0 0 1 630.083 280.0879)" class="st9 st6 st10"><?php _e("CVC", VINDI); ?></text>
              </g>
              <rect x="58.1" y="378.6" class="st11" width="375.5" height="13.5" />
              <rect x="58.1" y="405.6" class="st11" width="421.7" height="13.5" />
              <text transform="matrix(1 0 0 1 59.5073 228.6099)" id="vindi_cc_svgnameback" class="st12 st13">João da Silva</text>
            </g>
          </svg>
        </div>
      </div>
    </div>
    <div class="vindi_cc_form-container">
      <div class="field-container">
        <label for="vindi_cc_name">
          <?php _e("Nome Impresso no Cartão", VINDI); ?>
          <span class="required">*</span>
        </label>
        <input id="vindi_cc_name" name="vindi_cc_fullname" maxlength="20" type="text">
      </div>
      <div class="field-container">
        <label for="vindi_cc_cardnumber">
          <?php _e("Número do Cartão", VINDI); ?>
          <span class="required">*</span>
        </label>
        <input id="vindi_cc_cardnumber"
               name="vindi_cc_number"
               type="text"
               inputmode="numeric"
               autocomplete="off"
               placeholder="•••• •••• •••• ••••"
        />
        <img
            id="vindi_cc_ccicon" class="vindi_cc_ccicon"
            src="
            <?php echo esc_url(
                get_site_url() .
                    '/wp-content/plugins/vindi-payment-gateway/src/assets/images/card/unknown.svg'
            )
            ?>"
            data-img="unknown">
        </img>
      </div>
      <div class="field-container">
        <label for="vindi_cc_expirationdate">
          <?php _e("Validade (mm/aa)", VINDI) ?>
          <span class="required">*</span>
        </label>
        <input id="vindi_cc_expirationdate" type="text" inputmode="numeric" placeholder="mm/aa" autocomplete="off">
      </div>
      <div class="field-container">
        <label for="vindi_cc_securitycode">
          <?php _e("Código de Segurança", VINDI); ?>
          <span class="required">*</span>
        </label>
        <input id="vindi_cc_securitycode"
               name="vindi_cc_cvc"
               type="text"
               inputmode="numeric"
               placeholder="CVC"
               autocomplete="off"
        />
      </div>
      <input name="vindi_cc_paymentcompany" type="hidden">
      <input name="vindi_cc_monthexpiry" type="hidden">
      <input name="vindi_cc_yearexpiry" type="hidden">
    </div>
  </div>
    <?php if (isset($installments) && !empty($installments)) : ?>
    <p class="form-row form-row-wide">
      <label for="vindi_cc_installments"><?php _e("Número de Parcelas", VINDI); ?>
        <span class="required">*</span>
      </label>
      <select name="vindi_cc_installments" class="input-text" style="width: 100%">
        <?php foreach($installments as $installment => $price): ?>
          <option value="<?php echo $installment; ?>"><?php echo sprintf(__('%dx de %s', VINDI), $installment, wc_price($price)); ?></option>
        <?php endforeach; ?>
      </select>
    </p>
    <?php endif; ?>
  <div class="clear"></div>
    <?php
    if (isset($id)) {
        do_action('vindi_credit_card_form_end', $id);
    }
    ?>

  <div class="clear"></div>
</fieldset>
