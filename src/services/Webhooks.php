<?php

namespace VindiPaymentGateways;

use WC_Subscriptions_Manager;
use Exception;
use WP_Query;
use WC_Order;

class VindiWebhooks
{
  /**
   * @var VindiSettings
   */
  private $vindi_settings;

  /**
   * @var VindiRoutes
   */
  private $routes;

  /**
   * @var WebhooksHelper
   */
    private $webhooksHelpers;

  /**
   * @param VindiSettings $vindi_settings
   */
    public function __construct(VindiSettings $vindi_settings)
    {
      $this->vindi_settings = $vindi_settings;
      $this->routes = $vindi_settings->routes;
        $this->webhooksHelpers = new WebhooksHelpers($this);
    }

  /**
   * Handle incoming webhook.
   */
    public function handle()
    {
        $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
        $raw_body = file_get_contents('php://input');
        $body = json_decode($raw_body);

        if (!$this->validate_access_token($token)) {
            http_response_code(403);
            die('invalid access token');
        }

        $this->vindi_settings->logger->log(sprintf(__('Novo Webhook chamado: %s', VINDI), $raw_body));

        try {
            $this->process_event($body);
        } catch (Exception $e) {
            $this->vindi_settings->logger->log($e->getMessage());

            if (2 === $e->getCode()) {
                header("HTTP/1.0 422 Unprocessable Entity");
                die($e->getMessage());
            }
        }
    }

  /**
   * @param string $token
   */
  private function validate_access_token($token)
  {
    return $token === $this->vindi_settings->get_token();
  }

  /**
   * Read json entity received and proccess the right event
   * @param string $body
   */
  private function process_event($body)
  {
    if (null == $body || empty($body->event))
      throw new Exception(__('Falha ao interpretar JSON do webhook: Evento do Webhook não encontrado!', VINDI));

    $type = $body->event->type;
    $data = $body->event->data;

    if (method_exists($this, $type)) {
      $this->vindi_settings->logger->log(sprintf(__('Novo Evento processado: %s', VINDI), $type));
      return $this->{$type}($data);
    }

    $this->vindi_settings->logger->log(sprintf(__('Evento do webhook ignorado pelo plugin: ', VINDI), $type));
  }

  /**
   * Process test event from webhook
   * @param $data array
   */
  private function test($data)
  {
    $this->vindi_settings->logger->log(__('Evento de teste do webhook.', VINDI));
  }

    /**
   * Process bill_created event from webhook
   * @param $data array
   */
    private function bill_created($data)
    {
        $response = ['message' => 'Não foi possível emitir a fatura', 'status' => 422];
        try {
            if (empty($data->bill->subscription)) {
                return;
            }
            $renewInfos = $this->webhooksHelpers->renew_infos_array($data);
            if ($this->webhooksHelpers->handle_subscription_renewal($renewInfos, $data)) {
                $response = ['message' => 'Fatura emitida corretamente', 'status' => 200];
            } elseif ($this->webhooksHelpers->handle_trial_period($renewInfos['wc_subscription_id'])) {
                $response = ['message' => 'O estado da assinatura passou para "Em espera"', 'status' => 200];
            }
        } catch (\Exception $e) {
            $this->handle_exception('bill_created', $e->getMessage(), $data->bill->id);
            $response = ['message' => 'Erro durante o processamento da fatura.', 'status' => 500];
        }

        return wp_send_json(['message' => $response['message']], $response['status']);
    }
  
  /**
   * Process subscription_renew event from webhook
   * @param $renew_infos array
   */
    public function subscription_renew($renew_infos)
  {
    $subscription = $this->find_subscription_by_id($renew_infos['wc_subscription_id']);

    if ($this->subscription_has_order_in_cycle($renew_infos['vindi_subscription_id'], $renew_infos['cycle'])) {
      throw new Exception(sprintf(
        __('Já existe o ciclo %s para a assinatura #%s pedido #%s!', VINDI),
        $renew_infos['cicle'],
        $renew_infos['vindi_subscription_id'],
        $subscription->get_last_order()
      ));
    }

    WC_Subscriptions_Manager::prepare_renewal($subscription->id);
    $order_id = $subscription->get_last_order();
    $order = $this->find_order_by_id($order_id);
    $subscription_id = $renew_infos['vindi_subscription_id'];
        $order_post_meta = array($order->get_meta('vindi_order', true));
    $order_post_meta[$subscription_id]['cycle'] = $renew_infos['cycle'];
    $order_post_meta[$subscription_id]['product'] = $renew_infos['plan_name'];
        $order_post_meta[$subscription_id]['bill'] = $this->webhooksHelpers->make_array_bill($renew_infos);
        $order->update_meta_data('vindi_order', $order_post_meta);
        $order->save();
    $this->vindi_settings->logger->log('Novo Período criado: Pedido #'.$order->id);

    // We've already processed the renewal
    remove_action('woocommerce_scheduled_subscription_payment', 'WC_Subscriptions_Manager::prepare_renewal');
    }

  /**
   * Process bill_paid event from webhook
   * @param $data array
   */
    private function bill_paid($data)
    {
        try {
            if (empty($data->bill->subscription)) {
              $order = $this->find_order_by_id($data->bill->code);
            } else {
                $vindi_subscription_id = $data->bill->subscription->id;
                $cycle = $data->bill->period->cycle;
                $order = $this->find_order_by_subscription_and_cycle($vindi_subscription_id, $cycle);
            }

            $vindi_order = get_post_meta($order->id, 'vindi_order', true);
            if (!is_array($vindi_order)) {
                return wp_send_json(['message' => 'Pedido Vindi não encontrado.'], 422);
            }

            if (empty($data->bill->subscription)) {
                $vindi_order['single_payment']['bill']['status'] = $data->bill->status;
            }
            $vindi_order[$vindi_subscription_id]['bill']['status'] = $data->bill->status;
            $order->update_meta_data('vindi_order', $vindi_order);
            $order->save();
            $vindi_order_info = end($vindi_order);

            if ($vindi_order_info['bill']['status'] == 'paid') {
                $new_status = $this->vindi_settings->get_return_status();
                $order->update_status($new_status, __('O pagamento foi processado com sucesso pela Vindi.', VINDI));
                $this->update_next_payment($data);
                return wp_send_json(['message' => 'Processamento de pagamento de fatura concluído.'], 200);
            }
            return wp_send_json(['message' => 'Não foi possível processar o pagamento da fatura'], 422);
        } catch (\Exception $e) {
            $this->handle_exception('bill_paid', $e->getMessage(), $data->bill->code);
            return wp_send_json(['message' => 'Erro durante o processamento do pagamento da fatura.'], 500);
        }
    }

  /**
   * Process bill_canceled event from webhook
   * @param $data array
   */
  private function bill_canceled($data)
  {
    try {
      if (empty($data->bill->subscription)) {
        $order = $this->find_order_by_id($data->bill->code);
      } else {
        $vindi_subscription_id = $data->bill->subscription->id;
        $cycle = $data->bill->period->cycle;
        $order = $this->find_order_by_subscription_and_cycle($vindi_subscription_id, $cycle);
      }

      $order->update_status('cancelled', __('Pagamento cancelado dentro da Vindi!', VINDI));
            return wp_send_json(['message' => 'Pagamento cancelado dentro da Vindi!'], 200);
    } catch (Exception $e) {
            $this->handle_exception('bill_canceled', $e->getMessage(), $data->bill->code);
            return wp_send_json(['message' => 'Erro durante o processamento de cancelamento da fatura.'], 500);
    }
  }

  /**
   * Process issue_created event from webhook
   * @param $data array
   */
  private function issue_created($data)
  {
    $issue_type = $data->issue->issue_type;
    $issue_status = $data->issue->status;
    $item_type = strtolower($data->issue->item_type);

    if ('charge_underpay' !== $issue_type)
      throw new Exception(sprintf(__('Pendência criada com o tipo "%s" não processada!', VINDI), $issue_type));

    if ('open' !== $issue_status)
      throw new Exception(sprintf(__('Pendência criada com o status "%s" não processada!', VINDI), $issue_status));

    if ('charge' !== $item_type)
      throw new Exception(sprintf(__('Pendência criada com o item do tipo "%s" não processada!', VINDI), $item_type));

    $item_id = (int) $data->issue->item_id;
    $issue_data = $data->issue->data;
    $bill = $this->find_bill_by_charge_id($item_id);
    $order = $this->find_order_by_bill_id($bill->id);

    $order->add_order_note(sprintf(
      __('Divergencia de valores do Pedido #%s: Valor Esperado R$ %s, Valor Pago R$ %s.', VINDI),
      $order->id,
      $issue_data->expected_amount,
      $issue_data->transaction_amount
    ));
  }

  /**
   * Process charge_rejected event from webhook
   * @param $data array
   */
    private function charge_rejected($data)
    {
      try {
            $order = $this->find_order_by_bill_id($data->charge->bill->id);
            if (!empty($order) && $order->get_status() == 'pending') {
                $order->update_status('failed', __('Pagamento rejeitado!', VINDI));
                return wp_send_json(['mensagem' => 'O pagamento foi rejeitado com sucesso.'], 200);
            }
            return wp_send_json(['mensagem' => 'Erro ao trocar status da fatura para "failed"
              pois a fatura #%s não está mais pendente!', $data->charge->bill->id], 404);
      } catch (Exception $e) {
            $this->handle_exception_rejected($e, $data);
            return wp_send_json(['mensagem' => 'Ocorreu erro na alteração da assinatura'], 500);
      }
    }

  /**
   * Process handle_exception_rejected
   * @param $e array, $data array
   */
    private function handle_exception_rejected($event, $data)
    {
        if ($event->getCode() == 2) {
            $bill = $this->routes->findBillById($data->charge->bill->id);
            $vindi_subscription_id = isset($bill['subscription']) ? $bill['subscription']['id'] : null;
            $cycle = isset($bill['period']) ? $bill['period']['cycle'] : null;
            $this->find_order_by_subscription_and_cycle($vindi_subscription_id, $cycle);
        }
        $this->handle_exception('charge_rejected', $event->getMessage(), $data->charge->bill->id);
    }

  /**
   * Process subscription_canceled event from webhook
   * @param $data array
   */
    private function subscription_canceled($data)
    {
        try {
            $subscription = $this->find_subscription_by_id($data->subscription->code);
            if ($this->subscription_cancellation_not_possible($subscription, $data)) {
                return wp_send_json(['mensagem' => 'Não foi possível cancelar a assinatura
                      devido ao seu status atual.'], 422);
            }
            if ($this->vindi_settings->dependencies->is_wc_memberships_active()) {
                return $this->handle_pending_cancel($subscription);
            }
            $synchronized_subscription = $this->routes->getSubscription($data->subscription->id);
            if ($synchronized_subscription['status'] === 'canceled') {
                $subscription->update_status('cancelled');
                $this->vindi_settings->logger->log(sprintf(__('Assinatura cancelado.', VINDI)));
                return wp_send_json(['mensagem' => 'Assinatura cancelado.'], 200);
            }
            $this->vindi_settings->logger->log(sprintf(__('Ocorreu um erro no cancelamento da assinatura', VINDI)));
            wp_send_json(['mensagem' => 'Ocorreu erro na assinatura'], 422);
        } catch (\Exception $e) {
            $this->handle_exception('subscription_canceled', $e->getMessage(), $data->subscription->id);
            $response = ['mensagem' => 'Ocorreu erro no cancelamento da assinatura'];
            wp_send_json($response, 500);
            return;
        }
    }

    private function subscription_cancellation_not_possible($subscription, $data)
    {
        return $this->vindi_settings->get_synchronism_status()
        && (
        $subscription->has_status('cancelled')
        || $subscription->has_status('pending-cancel')
        || $subscription->has_status('on-hold')
        )
          || $this->routes->hasPendingSubscriptionBills($data->subscription->id);
    }

    private function handle_pending_cancel($subscription)
    {
        $subscription->update_status('pending-cancel');
        $this->vindi_settings->logger->log(sprintf(__('Assinatura atualizada para cancelamento pendente.', VINDI)));
        return wp_send_json(['message' => 'Assinatura atualizada para cancelamento pendente.'], 200);
    }

  /**
   * Process subscription_reactivated event from webhook
   * @param $data array
   */
    private function subscription_reactivated($data)
    {
        try {
            if ($this->vindi_settings->get_synchronism_status()
              && !$this->routes->hasPendingSubscriptionBills($data->subscription->id)
            ) {
                $subscription_id = $data->subscription->code;
                $subscription = $this->find_subscription_by_id($subscription_id);
                $order_id = $subscription->get_last_order();
                $order = $this->find_order_by_id($order_id);
                $status_available = array('processing', 'completed', 'on-hold');
                if (in_array($order->get_status(), $status_available)) {
                    $subscription->update_status(
                        'active',
                        sprintf(__('Subscription %s reactivated by Vindi.', VINDI), $subscription_id)
                    );
                    return wp_send_json(['message' => 'A assinatura foi reativada com sucesso.'], 200);
                }
            }
            return wp_send_json(['message' => 'A assinatura não pôde ser reativada.'], 422);
        } catch (\Exception $e) {
            $this->handle_exception('subscription_reactivated', $e->getMessage(), $data->subscription->id);
            return wp_send_json(['message' => 'Erro durante o processamento da reativação da assinatura.'], 500);
        }
    }

    private function handle_exception($event, $error, $data)
    {
        $message = print_r([
        'event' => $event,
        'mensagem' => $error,
        "id" => $data,
        ], true);
        $this->vindi_settings->logger->log(sprintf(__('WEBHOOK ERROR: %s', VINDI), $message));
    }

  /**
   * find a subscription by id
   * @param int id
   * @return WC_Subscription
   */
    public function find_subscription_by_id($id_item)
  {
    // Webhooks Ids has "WC-" prefix
        $sanitized_id = explode('WC-', $id_item);
    $subscription = wcs_get_subscription(end($sanitized_id));

    if (empty($subscription))
        throw new Exception(sprintf(__('Assinatura #%s não encontrada!', VINDI), $id_item), 2);

    return $subscription;
    }

  /**
   * @param int id
   *
   * @return WC_Subscription
   */
  private function find_bill_by_charge_id($id)
  {
    $charge = $this->routes->getCharge($id);

    if (empty($charge))
      throw new Exception(sprintf(__('Cobrança #%s não encontrada!', VINDI), $id), 2);

    return (object) $charge['bill'];
  }

  /**
   * find a order by id
   * @param int id
   *
   * @return WC_Order
   */
  private function find_order_by_id($id)
  {
    $order = wc_get_order($id);

    if (empty($order))
      throw new Exception(sprintf(__('Pedido #%s não encontrado!', VINDI), $id), 2);

    return $order;
  }

  /**
   * find orders by bill_id meta
   *
   * @param int $bill_id
   *
   * @return WC_Order
   */
    private function find_order_by_bill_id($bill_id)
  {
    $args = array(
      'post_type' => 'shop_order',
      'meta_key' => 'vindi_bill_id',
      'meta_value' => $bill_id,
      'post_status' => 'any',
    );

    $query = new WP_Query($args);

    if (false === $query->have_posts())
      throw new Exception(sprintf(__('Pedido com bill_id #%s não encontrado!', VINDI), $bill_id), 2);

    return wc_get_order($query->post->ID);
    }

  /**
   * Query orders containing cycle meta
   *
   * @param int $subscription_id
   * @param int $cycle
   *
   * @return WC_Order
   */
    private function find_order_by_subscription_and_cycle($subscription_id, $cycle)
    {
    $query = $this->query_order_by_metas(array(
      array(
        'key' => 'vindi_order',
        'value' => 'i:' . $subscription_id . ';a:3:{s:5:"cycle";i:' . $cycle . ';',
        'compare' => 'LIKE'
      ),
    ));

    if (false === $query->have_posts())
      throw new Exception(sprintf(__('Pedido da assinatura #%s para o ciclo #%s não encontrado!', VINDI), $subscriptionn_id, $cycle), 2);

    return wc_get_order($query->post->ID);
    }

  /**
   * @param int $subscription_id
   * @param int $cycle
   *
   * @return boolean
   */
    public function subscription_has_order_in_cycle($subscription_id, $cycle)
    {
    $query = $this->query_order_by_metas(array(
      array(
        'key' => 'vindi_order',
        'value' => 'i:' . $subscription_id . ';a:3:{s:5:"cycle";i:' . $cycle . ';',
        'compare' => 'LIKE'
      ),
    ));

    return $query->have_posts();
    }

  /**
   * @param array $metas
   *
   * @return WP_Query
   */
    private function query_order_by_metas(array $metas)
    {
    $args = array(
      'post_type' => 'shop_order',
      'meta_query' => $metas,
      'post_status' => 'any',
    );

    return new WP_Query($args);
    }

  /**
   * Update next payment schedule of subscription
   *
   * @param $data object
   */
    public function update_next_payment($data)
  {
    // let's find the subscription in the API
    // we need this step because the actual next billing date does not come from the /bill webhook
    $vindi_subscription = $this->routes->getSubscription($data->bill->subscription->id);

    if ($vindi_subscription && isset($vindi_subscription['next_billing_at'])) {

      $next_billing_at = $vindi_subscription['next_billing_at'];

      $end_at = $vindi_subscription['end_at'];

      // na api, quando o plano é de cobrança única,
      // o next_billing_at é 1 segundo maior que o end_at
      // quando isso acontecer, o next_payment do wc deve ser null
      // (a issue #134 tem mais informações do problema)

      if ($end_at != null && $next_billing_at > $end_at) {
        return false;
      }

      // format next payment date
      $next_payment = $this->format_date($next_billing_at);

      // format end date
      $end_date = $this->format_date($end_at);

      // find our wc_subscription
      $subscription = $this->find_subscription_by_id($data->bill->subscription->code);

      // update the subscription dates
      $subscription->update_dates(array('next_payment' => $next_payment));
      $subscription->update_dates(array('end_date' => $end_date));
    }
    }

  private function format_date($date)
  {
    return date('Y-m-d H:i:s', strtotime($date));
  }
}
