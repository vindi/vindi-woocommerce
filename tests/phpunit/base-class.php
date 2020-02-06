<?php
namespace Vindi\Testing;

class Vindi_Test_Base extends \WP_UnitTestCase {

	use Vindi_Test;

	protected function getSelf() {
		return $this;
	}
}
