<?php

namespace VindiPaymentGateways;

class VindiApi
{
  /**
   * @var string
   */
  private $key;

  /**
   * @var string
   */
  public $last_error = '';

  /**
   * @var bool
   */
  public $accept_bank_slip;

  /**
   * @var VindiLogger
   */
  private $logger;

  /**
   * @var string
   */
  private $recentRequest;

  /**
   * @var VindiPlan
   */
  public $current_plan;

  /**
   * @var String 'Yes' or 'no'
   */
  private $sandbox;

  private $errors_list = array(
    'invalid_parameter|card_number'          => 'Número do cartão inválido.',
    'invalid_parameter|registry_code'        => 'CPF ou CNPJ Invalidos',
    'invalid_parameter|payment_company_code' => 'Método de pagamento Inválido',
    'invalid_parameter|phones.number'        => 'Número de telefone inválido',
    'invalid_parameter|phones'               => 'Erro ao cadastrar o telefone'
  );

  /**
   * API Base path
   *
   * @return string
   */
  public function base_path()
  {
    if ('yes' === $this->sandbox) {
      return 'https://sandbox-app.vindi.com.br/api/v1/';
    }

    return 'https://app.vindi.com.br/api/v1/';
  }

  /**
   * @param string $key
   * @param VindiLogger $logger
   * @param string $sandbox
   */
  public function __construct($key, VindiLogger $logger, $sandbox)
  {
    $this->key          = $key;
    $this->logger       = $logger;
    $this->sandbox      = $sandbox;
  }

  /**
   * @param array $data
   *
   * @return mixed
   */
  private function build_body($data)
  {
    $body = null;

    if (!empty($data)) {
      $body = json_encode($data);
    }

    return $body;
  }

  /**
   * @param array $data
   *
   * @return mixed
   */
  private function convert_body_to_json($data)
  {
    $body = null;

    if (!empty($data)) {
      $body = json_encode($data);
    }

    return $body;
  }

  /**
   * Generate Basic Authentication Header .
   * @return string
   */
  private function get_auth_header()
  {
    return sprintf('Basic %s', base64_encode($this->key . ":"));
  }

  /**
   * @param array $error
   *
   * @return string
   */
  private function get_error_message($error)
  {
    $error_id         = empty($error['id']) ? '' : $error['id'];
    $error_parameter  = empty($error['parameter']) ? '' : $error['parameter'];

    $error_identifier = sprintf('%s|%s', $error_id, $error_parameter);

    if (false === array_key_exists($error_identifier, $this->errors_list))
      return $error_identifier;

    return __($this->errors_list[$error_identifier], VINDI);
  }

    /**
    * @param array $response
    *
    * @return bool
    */
    private function check_response($response)
    {
        if (isset($response['errors']) && !empty($response['errors'])) {
            foreach ($response['errors'] as $error) {
                $message = $this->get_error_message($error);

                if (function_exists('wc_add_notice') && !strpos($message, '|')) {
                    wc_add_notice(__($message, VINDI), 'error');
                }

                $this->last_error = $message;
            }

            return false;
        }

        $this->last_error = '';

        return true;
    }

  /**
   * Verify API key authorization and clear
   * all transient data if access was denied
   *@param $api_key string
   *@return mixed|boolean|string
   */
  public function test_api_key($api_key)
  {
    delete_transient('vindi_merchant');

    $url         = $this->base_path() . 'merchant';
    $method      = 'GET';
    $request_id  = rand();
    $data_to_log = 'API Authorization Test';

    $this->logger->log(sprintf("[Request #%s]: Novo Request para a API.\n%s %s\n%s", $request_id, $method, $url, $data_to_log));

    $response = wp_remote_post($url, [
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
        'Content-Type'  => 'application/json',
        'User-Agent'    => sprintf('Vindi-WooCommerce/%s; %s', VINDI_VERSION, get_bloginfo('url')),
      ],
      'method'    => $method,
      'timeout'   => 60,
      'sslverify' => true,
    ]);

    if (is_wp_error($response)) {
      $this->logger->log(sprintf("[Request #%s]: Erro ao fazer request! %s", $request_id, print_r($response, true)));

      return false;
    }

    $status = $response['response']['code'] . ' ' . $response['response']['message'];
    $this->logger->log(sprintf("[Request #%s]: Nova Resposta da API.\n%s\n%s", $request_id, $status, print_r($response['body'], true)));

    $response_body = wp_remote_retrieve_body($response);

    if (!$response_body) {
      $this->logger->log(sprintf('[Request #%s]: Erro ao recuperar corpo do request! %s', $request_id, print_r($response, true)));

      return false;
    }

    $response_body_array = json_decode($response_body, true);

    if (isset($response_body_array['errors']) && !empty($response_body_array['errors'])) {
      foreach ($response_body_array['errors'] as $error) {
        if ('unauthorized' == $error['id'] and 'authorization' == $error['parameter']) {
          delete_transient('vindi_plans');
          delete_transient('vindi_payment_methods');
          $message = $this->get_error_message($error);
          $this->last_error = $message;

          return $error['id'];
        }
      }
    }

    set_transient('vindi_merchant', $response_body_array['merchant'], 1 * HOUR_IN_SECONDS);
    return true;
  }

  /**
   * @param string $endpoint
   * @param string $method
   * @param array  $data
   * @param null   $data_to_log
   *
   * @return array|bool|mixed
   */
  public function request($endpoint, $method = 'POST', $data = array(), $data_to_log = null)
  {
    $url  = sprintf('%s%s', $this->base_path(), $endpoint);
    $body = $this->build_body($data);

    $request_id = rand();

    $data_to_log = null !== $data_to_log ? $this->build_body($data_to_log) : $body;

    $this->logger->log(sprintf("[Request #%s]: Novo Request para a API.\n%s %s\n%s", $request_id, $method, $url, $data_to_log));

    $response = wp_remote_post($url, array(
      'headers' => array(
        'Authorization' => $this->get_auth_header(),
        'Content-Type'  => 'application/json',
        'User-Agent'    => sprintf(VINDI . '/%s; %s', VINDI_VERSION, get_bloginfo('url')),
      ),
      'method'    => $method,
      'timeout'   => 60,
      'sslverify' => true,
      'body'      => $body,
    ));

    if (is_wp_error($response)) {
      $this->logger->log(sprintf("[Request #%s]: Erro ao fazer request! %s", $request_id, print_r($response, true)));

      return false;
    }

    $status = sprintf('%s %s', $response['response']['code'], $response['response']['message']);
    $this->logger->log(sprintf("[Request #%s]: Nova Resposta da API.\n%s\n%s", $request_id, $status, print_r($response['body'], true)));

    $response_body = wp_remote_retrieve_body($response);

    if (!$response_body) {
      $this->logger->log(sprintf('[Request #%s]: Erro ao recuperar corpo do request! %s', $request_id, print_r($response, true)));

      return false;
    }

    $response_body_array = json_decode($response_body, true);

    if (!$this->check_response($response_body_array)) {
      return false;
    }

    return $response_body_array;
  }
}
