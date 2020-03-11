<?php

/**
 * Junção de todos os controllers necessários para
 * a comunicação entre a API da Vindi.
 *
 * @return void;
 */



class VindiControllers
{

  /**
   * @var string
   */
  private $path;


  function __construct()
  {
    $this->path = WC_Vindi_Payment::getPath();

    $this->includes();
  }


  function includes()
  {

    print_r($this->path);
  }
}
