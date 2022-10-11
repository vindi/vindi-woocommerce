<?php

class VindiLogger
{
  /**
   * identifier to WC_Logger
   * @var string
   */
  private $identifier;

  /**
   * @var WC_Logger
   */
  private $main_logger;

  /**
   * @var boolean
   */
  private $is_active;

  public function __construct($identifier, $is_active)
  {
    $this->main_logger = new WC_Logger();
    $this->identifier  = $identifier;
    $this->is_active   = $is_active;
  }

  /**
   * Create order log
   * @return bool
   */
  public function order($message)
  {
    return $this->log($message, '-order');
  }

  /**
   * Create product log
   * @return bool
   */
  public function product($message)
  {
    return $this->log($message, '-product');
  }

  /**
   * Create plan log
   * @return bool
   */
  public function plan($message)
  {
    return $this->log($message, '-plan');
  }

  /**
   * Create webhook log
   * @return bool
   */
  public function webhook($message)
  {
    return $this->log($message, '-webhook');
  }

  /**
   * Create customer log
   * @return bool
   */
  public function customer($message)
  {
    return $this->log($message, '-customer');
  }

  /**
   * Create request log
   * @return bool
   */
  public function request($message)
  {
    return $this->log($message, '-request-response');
  }

  /**
   * @return boolean
   */
  public function log($message, $type = '')
  {
    if ($this->is_active) {

      $id = $this->identifier . $type;
      $this->main_logger->add($id, $message);
      return true;
    }

    return false;
  }
}
