
<?php

class VindiRoutes
{

  /**
   * @var VindiSettings
   */
  private $settings;

  /**
   * @var void
   */
  private $api;

  function __construct()
  {

    $this->settings = new VindiSettings();
    $this->api = $this->settings->api;
  }

  public function createPlan($data)
  {

    $response = $this->api->request('plans', 'POST', $data);

    return $response;
  }
}
?>
