<?php

/**
 * Add the gateway to WooCommerce.
 *
 * @param  array $methods WooCommerce payment methods.
 *
 * @return array Payment methods with Vindi.
 */

function add_gateway($methods)
{

  $methods[] = 'VindiCreditGateway';
  $methods[] = 'VindiBankSlipGateway';

  return $methods;
};
