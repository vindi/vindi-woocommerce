<?php

include_once VINDI_PATH . 'src/validators/Dependencies.php';

class Vindi_Test_Dependencies extends Vindi_Test_Base
{
  public function test_critical_dependencies()
  {
    $this->assertTrue(VindiDependencies::check_critical_dependencies());
  }
}; ?>