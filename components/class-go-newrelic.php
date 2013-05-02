<?php

class GO_NewRelic
{	
	public function __construct()
	{
		// exit early if we don't have the New Relic extension
		// see https://newrelic.com/docs/php/new-relic-for-php for installation instructions
		if ( ! function_exists( 'newrelic_set_appname' ) )
		{
			return FALSE;
		}

		// config array keys are the NR config names with sanitize_title_with_dashes() applied 
		// and the `newrelic` prefix removed
		// see https://newrelic.com/docs/php/php-agent-phpini-settings for details
		$this->config = apply_filters( 'go_config', array(
			'license' => '',
			'transaction-tracer-detail' => 0,
			'newrelic-loglevel' => 'info',
			'capture-params' => TRUE,
			'ignored-params' => '',
			'error-collector-enabled' => FALSE,
		), 'go-newrelic' );

		// the license key is typically set elsewhere during the daemon/module installation, 
		// but this allows some potential future where the license key is set in the WP dashboard
		if ( ! empty( $this->config['license'] ) )
		{
			ini_set( 'newrelic.license', $this->config['license'] );
		}

		// basic settings
		ini_set( 'newrelic.framework', 'wordpress' );
		ini_set( 'newrelic.transaction_tracer.detail', $this->config['transaction-tracer-detail'] );
		ini_set( 'newrelic.error_collector.enabled', $this->config['error-collector-enabled'] );
		ini_set( 'newrelic.capture_params', $this->config['capture-params'] );
		ini_set( 'newrelic.ignored_params', $this->config['ignored-params'] );

		// get the base app name from the home_url()
		$home_url = parse_url( home_url() );
		$app_name = $home_url['host'] . ( isset( $home_url['path'] ) ? $home_url['path'] : '' );

		// the dashboard, admin-ajax, cron, and front-end are all logged as separate apps
		// this allows us to set different thresholds for those very different aspects of each site
		// see https://newrelic.com/docs/php/the-php-api for documentation on NR's PHP methods
		if ( is_admin() )
		{
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			{
				newrelic_set_appname( $app_name . ' ajax' );
			}
			else
			{
				newrelic_set_appname( $app_name . ' admin' );
			}
		}
		elseif ( defined( 'DOING_CRON' ) && DOING_CRON )
		{
				newrelic_set_appname( $app_name . ' cron' );
		}
		else
		{
			newrelic_set_appname( $app_name );

			// add more tracking of the template pieces
			add_action( 'template_include' , array( $this , 'template_include' ) );
		}

		// track the user info
		add_action( 'init' , array( $this , 'init' ) );
	}

	// add user info now that we know it
	public function init()
	{
		// not all versions of the php extension support this method
		if ( ! function_exists( 'newrelic_set_user_attributes' ) )
		{
			return;
		}

		// see https://newrelic.com/docs/features/browser-traces#set_user_attributes 
		// for docs on how to use the user info in the transaction trace
		if ( is_user_logged_in() )
		{
			$user = wp_get_current_user();
			newrelic_set_user_attributes( $user->ID, '', array_shift( $user->roles ) );
		}
		else
		{
			newrelic_set_user_attributes( 'not-logged-in', '', 'no-role' );
		}
	}

	// track the template we're using
	public function template_include( $template )
	{
		newrelic_add_custom_parameter( 'template' , $template );
		return $template;
	}

	// a method other plugins can call to ignore this transaction
	public function ignore()
	{
		newrelic_ignore_transaction();
		newrelic_ignore_apdex();
	}
}

function go_newrelic()
{
	global $go_newrelic;

	if ( ! isset( $go_newrelic ) || ! is_object( $go_newrelic ) )
	{	
		$go_newrelic = new GO_NewRelic();
	}

	return $go_newrelic;
} // END go_newrelic
