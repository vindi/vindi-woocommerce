<?php

require_once $path . 'includes/utils/AbstractInstance.php';

class WC_Vindi_Payment extends AbstractInstance
{
	/**
	 * Plugin version.
	 *
	 * @var string
	 */

	const CLIENT_NAME = 'plugin-vindi-woocommerce';
	const CLIENT_VERSION = '1.0.0';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */

	protected static $instance = null;


    protected function __construct()
    {

        $this->includes();

        $this->content = new MentoresContents();
    }

    private function includes()
    {
		}
		
		public function getPath()
    {
        return $path;
    }

}
