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
			$this->apm = new GO_NewRelic_APM( $this );
		}// end if

		return $this->apm;
	} // END apm

	/**
	 * returns our current configuration, or a value in the configuration.
	 *
	 * @param string $key (optional) key to a configuration value
	 * @return mixed Returns the config array, or a config value if
	 *  $key is not NULL, or NULL if $key is specified but isn't set in
	 *  our config file.
	 */
	public function config( $key = NULL )
	{
		if ( empty( $this->config ) )
		{
			$this->config = apply_filters(
				'go_config',
				// config array keys are the NR config names with sanitize_title_with_dashes() applied
				// and the `newrelic` prefix removed
				// see https://newrelic.com/docs/php/php-agent-phpini-settings for details
				array(
					'license' => '',
					'transaction-tracer-detail' => 0,
					'newrelic-loglevel' => 'info',
					'capture-params' => TRUE,
					'ignored-params' => '',
					'error-collector-enabled' => FALSE,
				),
				'go-newrelic'
			);

			if ( empty( $this->config ) )
			{
				do_action( 'go_slog', 'go-newrelic', 'Unable to load go-newrelic\' configuration' );
			}
		}//END if

		if ( ! empty( $key ) )
		{
			return isset( $this->config[ $key ] ) ? $this->config[ $key ] : NULL ;
		}

		return $this->config;
	}//END config


	/**
	 * returns a name for the app based on context
	 *
	 * The dashboard, admin-ajax, cron, and front-end are all logged as separate apps.
	 * This allows us to set different thresholds for those very different aspects of each site.
	 *
	 * @return string The appname
	 */
	public function get_appname()
	{
		// get the base app name from the home_url()
		$home_url = parse_url( home_url() );
		$app_name = $home_url['host'] . ( isset( $home_url['path'] ) ? $home_url['path'] : '' );

		// now get context
		if ( is_admin() )
		{
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			{
				$app_name .= ' ajax';
			}// END if
			else
			{
				$app_name .= ' admin';
			}// END else
		}// end if
		elseif ( defined( 'DOING_CRON' ) && DOING_CRON )
		{
			$app_name .= ' cron';
		}// END elseif

		return $app_name;
	}//END get_appname
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
