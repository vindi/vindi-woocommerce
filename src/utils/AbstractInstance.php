<?php

namespace VindiPaymentGateways;

/**
 * Está classe, ao ser extendida, obriga que o metodo __construct seja privado,
 * assim, a classe que extende não pode ser instanciada diretamente com o new.
 * Para instancia-la é necessário usar o método estático Class::instance()
 * O método ::instance() sempre devolve a mesma instancia da classe, verificando
 * se a mesma já está instanciada e na memória, se estiver, devolve a mesma instancia,
 * caso contrário, instancia e retorna.
 *
 * O proprósito disso é evitar desperdício de memória pelo servidor e evitar também
 * execução importantes sejam executadas mais de uma vez no fluxo.
 */

abstract class AbstractInstance
{

	protected static $_instance = null;

	protected function __construct(){}
	protected function __clone(){}

	public static function instance()
	{
		if (is_null(static::$_instance)) {
			static::$_instance = new static;
		}
		return static::$_instance;
	}

}
