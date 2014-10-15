<?php

class GO_NewRelic
{
	private $apm;
	private $browser;

	public function __construct()
	{
		// use the fancier APM if we have the New Relic extension
		// see https://newrelic.com/docs/php/new-relic-for-php for installation instructions
		if ( function_exists( 'newrelic_set_appname' ) )
		{
			$this->apm();
			return;
		}//end if

		$this->browser();
	}// END __construct

	/**
	 * an object accessor for the browser object
	 */
	public function browser()
	{
		if ( ! $this->browser )
		{
			require_once __DIR__ . '/class-go-newrelic-browser.php';
			$this->browser = new GO_NewRelic_Browser();
		}// end if

		return $this->browser;
	} // END browser

	/**
	 * an object accessor for the apm object
	 */
	public function apm()
	{
		if ( ! $this->apm )
		{
			require_once __DIR__ . '/class-go-newrelic-apm.php';
			$this->apm = new GO_NewRelic_APM();
		}// end if

		return $this->apm;
	} // END apm
}// END class

function go_newrelic()
{
	global $go_newrelic;

	if ( ! isset( $go_newrelic ) || ! is_object( $go_newrelic ) )
	{
		$go_newrelic = new GO_NewRelic();
	}// END if

	return $go_newrelic;
}// END go_newrelic
