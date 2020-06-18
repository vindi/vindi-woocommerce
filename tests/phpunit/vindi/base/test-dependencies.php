<?php

include_once VINDI_PATH . 'src/validators/Dependencies.php';

class Vindi_Test_Dependencies extends Vindi_Test_Base
{
  // It's only possible to test critical dependencies,
  // because we do not load all plugins during unit testing.
  public function test_critical_dependencies()
  {
    $this->assertTrue(VindiDependencies::check_critical_dependencies());
  }
}; ?>