<?php

use PHPUnit\Framework\TestCase;


final class EmailTest extends TestCase
{
  public function testeCanBeMessage(): void
  {
        $this->assertEquals(
            'testes',
            'testes'
        );
    }
}


