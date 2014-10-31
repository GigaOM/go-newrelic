<?php

class GO_NewRelic_APM
{
	private $config;
	private $go_newrelic;

	public function __construct( $go_newrelic )
	{
		// get the calling object
		$this->go_newrelic = $go_newrelic;

		// can't lazy load the config, we need 
		$this->config = $this->go_newrelic->config();

		// the license key is typically set elsewhere during the daemon/module installation,
		// but this allows some potential future where the license key is set in the WP dashboard
		if ( ! empty( $this->config['license'] ) )
		{
			ini_set( 'newrelic.license', $this->config['license'] );
		}// END if

		// set the app name
		newrelic_set_appname( $this->go_newrelic->get_appname() );

		// basic settings
		// make sure the config isn't empty or invalid for any of these
		// ...sanity and validation intentionally skipped for performance reasons
		ini_set( 'newrelic.framework', 'wordpress' );
		ini_set( 'newrelic.transaction_tracer.detail', $this->config['transaction-tracer-detail'] );
		ini_set( 'newrelic.error_collector.enabled', $this->config['error-collector-enabled'] );
		if ( isset( $this->config['capture-params'] ) && $this->config['capture-params'] )
		{
			newrelic_capture_params();
		}// END if
		ini_set( 'newrelic.ignored_params', $this->go_newrelic->config( 'ignored-params' ) );

		// set logging parameters based on request context
		// ajax responses _cannot_ have RUM in them, for example
		if ( is_admin() )
		{
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			{
				newrelic_disable_autorum();
			}// END if
		}// end if
		elseif ( defined( 'DOING_CRON' ) && DOING_CRON )
		{
			newrelic_disable_autorum();
		}// END elseif
		else
		{
			// add more tracking of the template pieces
			add_action( 'template_include', array( $this, 'template_include' ) );
		}// END else

		// track the user info
		add_action( 'init', array( $this, 'init' ) );
	}//end __construct

	/**
	 * add user info now that we know it
	 */
	public function init()
	{
		// not all versions of the php extension support this method
		if ( ! function_exists( 'newrelic_set_user_attributes' ) )
		{
			return;
		}// END if

		// see https://newrelic.com/docs/features/browser-traces#set_user_attributes
		// for docs on how to use the user info in the transaction trace
		if ( is_user_logged_in() )
		{
			$user = wp_get_current_user();
			newrelic_set_user_attributes( $user->ID, '', array_shift( $user->roles ) );
		}// END if
		else
		{
			newrelic_set_user_attributes( 'not-logged-in', '', 'no-role' );
		}// END else
	}// END init

	/**
	 * track the template we're using
	 */
	public function template_include( $template )
	{
		newrelic_add_custom_parameter( 'template', $template );
		return $template;
	}// END template_include

	/**
	 * a method other plugins can call to ignore this transaction
	 */
	public function ignore()
	{
		newrelic_ignore_transaction();
		newrelic_ignore_apdex();
	}// END ignore
}// end class