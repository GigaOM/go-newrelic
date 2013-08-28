<?php
/**
 * Plugin Name: Gigaom New Relic
 * Plugin URI: http://gigaom.com
 * Description: Sets the New Relic app name and other configuration values based on WordPress configuration. New Relic profiles code performance and activity, http://newrelic.com .
 * Version: 0a
 * Author:  misterbisson
 * License: GPL2
 */

// include required components
require_once dirname( __FILE__ ) .'/components/class-go-newrelic.php';
go_newrelic();