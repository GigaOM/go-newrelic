<?php
class GO_NewRelic_Wpcli extends WP_CLI_Command
{
	public function find_urls( $text )
	{
		// nice regex thanks to John Gruber http://daringfireball.net/2010/07/improved_regex_for_matching_urls
		preg_match_all( '#(?i)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'".,<>?«»“”‘’]))#', $text, $urls );
		return $urls[0];
	}

	public function excercise( $args, $assoc_args )
	{
		// don't this in New Relic
		if ( function_exists( 'newrelic_ignore_transaction' ) )
		{
			newrelic_ignore_transaction();
		}

		if ( empty( $args ) )
		{
			WP_CLI::error( 'Please specify a URL to test.' );
			return;
		}

		if ( ! is_array( $assoc_args ) )
		{
			$assoc_args = array();
		}

		if (
			file_exists( $args[0] ) &&
			$list = file_get_contents( $args[0] )
		)
		{
			
		}
		else
		{
			$assoc_args['url'] = $args[0];
		}

		$args = (object) array_intersect_key( $assoc_args, array(
			'url' => TRUE,
			'count' => TRUE,
		) );

		if ( ! isset( $args->count ) )
		{
			$args->count = 17;
		}

		$runs = array();
		for ( $i = 1; $i <= $args->count; $i++ )
		{
			// the URL we're testing now
//			$test_url = add_query_arg( array( 'go-newrelic-excercize' => rand() ), $args->url );
			$test_url = $args->url;

			// init this stat run
			$runs[ $i ] = (object) array( 'request_url' => $test_url );

			WP_CLI::line( $test_url );
			$start_time = microtime( TRUE );
			$fetch_raw = wp_remote_get( $test_url, array(
				'timeout'    => 90,
				'user-agent' => 'go-newrelic WordPress exerciser',
			) );

			// time the request
			$runs[ $i ]->response_time = microtime( TRUE ) - $start_time;

			// get the size
			$runs[ $i ]->response_size = strlen( wp_remote_retrieve_body( $fetch_raw ) );

			// get the response code
			$runs[ $i ]->response_code = wp_remote_retrieve_response_code( $fetch_raw );

			// get the count of any cookies
			$temp = wp_remote_retrieve_header( $fetch_raw, 'set-cookie' );
			$runs[ $i ]->response_cookies = empty( $temp ) ? 0 : count( (array) $temp );

			// the last modified header
			$temp = wp_remote_retrieve_header( $fetch_raw, 'last-modified' );
			$runs[ $i ]->response_modified = is_array( $temp ) ? 'WARNING, ' . count( $temp ) .' headers found' : $temp;

			// the cache control header
			$temp = wp_remote_retrieve_header( $fetch_raw, 'cache-control' );
			$runs[ $i ]->response_cachecontrol = is_array( $temp ) ? 'WARNING, ' . count( $temp ) .' headers found' : $temp;
		}

		print_r( $runs );
	}
}