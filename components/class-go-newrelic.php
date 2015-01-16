<?php

class GO_NewRelic
{
	private $apm;
	private $browser;
	private $last_timer;
	private $wpcli;

	public function __construct()
	{

		// use the fancier APM if we have the New Relic module for PHP
		// see https://newrelic.com/docs/php/new-relic-for-php for installation instructions
		if ( function_exists( 'newrelic_set_appname' ) )
		{
			$this->apm();
		}//end if
		// browser monitoring works even when the PHP module isn't installed
		else
		{
			$this->browser();
		}

		// WPCLI methods to exercise a site
		if ( defined( 'WP_CLI' ) && WP_CLI )
		{
			$this->wpcli();
		}//end if

		// init the last_timer object for use later
		$this->last_timer = (object) array();
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
	 * A loader for the WP:CLI class
	 */
	public function wpcli()
	{
		if ( $this->wpcli )
		{
			return TRUE;
		}

		require_once __DIR__ . '/class-go-newrelic-wpcli.php';

		// declare the class to WP:CLI
		WP_CLI::add_command( 'go-newrelic', 'GO_NewRelic_Wpcli' );

		$this->wpcli = TRUE;
	}//end wpcli

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

	/**
	 * A timer that can be used anywhere
	 *
	 * Inspired by some work and code by Mark Jaquith http://coveredwebservices.com/
	 */
	public function timer( $name = '', $group = 'no group' )
	{
		if ( ! isset( $this->last_timer->$group ) )
		{
			$this->last_timer->$group = 0;
		}

		$current_timer = timer_stop( 0 );
		$change = $current_timer - $this->last_timer->$group;
		$this->last_timer->$group = $current_timer;

		echo '<!-- ' . esc_attr( "Total Time: $current_timer | $group / {$name}: " ) . number_format( $change, 3 ) . ' -->';
	}//END timer
}//END class

function go_newrelic()
{
	global $go_newrelic;

	if ( ! isset( $go_newrelic ) || ! is_object( $go_newrelic ) )
	{
		$go_newrelic = new GO_NewRelic();
	}// END if

	return $go_newrelic;
}// END go_newrelic
