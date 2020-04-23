<?php

class VindiSubscriptionStatusHandler
{
  /**
   * @var VindiSettings
   */
  private $vindi_settings;

  public function __construct(VindiSettings $vindi_settings)
  {
    $this->vindi_settings = $vindi_settings;
    $this->routes = $vindi_settings->routes;

    add_action('woocommerce_subscription_status_cancelled',array(
      &$this, 'cancelled_status'
    ));

    add_action('woocommerce_subscription_status_updated',array(
      &$this, 'filter_pre_status'
    ), 1, 3);
  }

  /**
   * @param WC_Subscription $wc_subscription
   * @param string          $new_status
   * @param string          $old_status
   */
  public function filter_pre_status($wc_subscription, $new_status, $old_status)
  {
    switch ($new_status) {
      case 'on-hold':
        $this->suspend_status($wc_subscription);
        break;
      case 'active':
        $this->active_status($wc_subscription, $old_status);
        break;
      case 'cancelled':
        $this->cancelled_status($wc_subscription);
        break;
      case 'pending-cancel':
        // TODO: check memberships
        // if (!$this->vindi_settings->dependency->wc_memberships_are_activated()) {
          $wc_subscription->update_status('cancelled');
        // }
        break;
    }
  }

  /**
   * @param WC_Subscription $wc_subscription
   */
  public function suspend_status($wc_subscription)
  {
    $subscription_id = $this->get_vindi_subscription_id($wc_subscription);
    if ($this->vindi_settings->get_synchronism_status()) {
      $this->routes->suspendSubscription($subscription_id);
    }
  }

  /**
   * @param WC_Subscription $wc_subscription
   */
  public function cancelled_status($wc_subscription)
  {
    $subscription_id = $this->get_vindi_subscription_id($wc_subscription);
    if ($this->routes->isSubscriptionActive($subscription_id)) {
      $this->routes->suspendSubscription($subscription_id, true); 
    }
  }

  /**
   * @param WC_Subscription $wc_subscription
   */
  public function active_status($wc_subscription, $old_status)
  {
    if ('pending' == $old_status)
      return;
    $subscription_id = $this->get_vindi_subscription_id($wc_subscription);
    if ($this->vindi_settings->get_synchronism_status()
      && !$this->routes->isSubscriptionActive($subscription_id)) {
      $this->routes->activateSubscription($subscription_id);
    }
  }

  /**
   * @param WC_Subscription $wc_subscription
   */
  public function get_vindi_subscription_id($wc_subscription)
  {
    $subscription_id = method_exists($wc_subscription, 'get_id')
    ? $wc_subscription->get_id()
    : $wc_subscription->id;
    return end(get_post_meta($subscription_id, 'vindi_wc_subscription_id'));
  }
}