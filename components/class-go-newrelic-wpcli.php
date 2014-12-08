<?php

/*
 * Args:
 * A URL or filename containing a list of URLs
 *	...A list of URLs should be a text file with one URL per line
 * --count: the integer number of times to test the named URL(s)
 * --rand: if present will cause the exerciser to insert random get vars that (maybe) will prevent page caching
 * --redirection: the number of redirects to follow, 0 is default
 * --user_id: if present, will cause the request to be made with the specified user's authentication tokens
 *
 * Examples:
 * wp --url=wpsite.example.org go-newrelic exercise "http://wpsite.example.org/" --count=13 --rand
 * wp --url=wpsite.example.org go-newrelic exercise url-list.txt --count=13 --rand
 * while true; do wp --url=wpsite.example.org go-newrelic exercise url-list.txt --count=7 --rand; sleep 100; done
 *
 * TODO:
 * Metrics are collected for summation, but none is done.
 * Summation by URL and among a group of URLs would be great
 * Output in CSV form, maybe...
 */

class GO_NewRelic_Wpcli extends WP_CLI_Command
{
	private $token = NULL;

	public function exercise( $args, $assoc_args )
	{
		// don't this in New Relic
		if ( function_exists( 'newrelic_ignore_transaction' ) )
		{
			newrelic_ignore_transaction();
		}//end if

		if ( empty( $args ) )
		{
			WP_CLI::error( 'Please specify a URL (or file with URLs) to test.' );
			return;
		}//end if

		if ( ! is_array( $assoc_args ) )
		{
			$assoc_args = array();
		}//end if

		if (
			file_exists( $args[0] ) &&
			( $lines = file( $args[0], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) )
		)
		{
			shuffle( $lines );
			foreach ( $lines as $url )
			{
				$assoc_args['url'] = $this->find_url( $url );
				self::test_url( $assoc_args );
			}
		}//end if
		else
		{
			$assoc_args['url'] = $this->find_url( $args[0] );
			self::test_url( $assoc_args );
		}//end else
	}//end exercise

	private function test_url( $args )
	{
		$args = (object) wp_parse_args( $args, array(
			'url' => NULL,
			'count' => 11,
			'redirection' => 0,
			'rand' => FALSE,
			'user_id' => FALSE,
		) );

		if ( ! $args->url )
		{
			WP_CLI::warning( 'Empty URL, skipping.' );
			return;
		}

		WP_CLI::line( "\n$args->url" );
		if ( $args->rand )
		{
			WP_CLI::line( 'URL will include randomized get vars to (maybe) break caching' );
		}
		$cookies = array();
		if ( $args->user_id )
		{
			$cookies = $this->get_auth_cookies( $args->user_id );
			WP_CLI::line( 'Using cookies from user ID ' . $args->user_id );
		}
		WP_CLI::line( "Response\nCode\tSize\tTime\tCookies\tLast Modified\t\t\tCache Control\t\t\tCanonical" );

		$runs = array();
		for ( $i = 1; $i <= $args->count; $i++ )
		{
			// the URL we're testing now
			if ( $args->rand )
			{
				$test_url = add_query_arg( array( 'go-newrelic-exercise' => rand() ), $args->url );
			}
			else
			{
				$test_url = $args->url;
			}

			// init this stat run
			$runs[ $i ] = (object) array( 'request_url' => $test_url );

			$start_time = microtime( TRUE );
			$fetch_raw = wp_remote_get( $test_url, array(
				'timeout'     => 90,
				'redirection' => absint( $args->redirection ),
				'headers'     => array( 'x-go-newrelic-exercise' => rand() ),
				'cookies'     => $cookies,
				'user-agent'  => 'go-newrelic WordPress exerciser',
				'sslverify'   => FALSE, // this would be hugely insecure if we were doing anything with the data returned, but since this is used for testing (often against local hosts with self-signed certs)....
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
			$runs[ $i ]->response_modified = is_array( $temp ) ? 'WARNING, ' . count( $temp ) .' headers found' : empty( $temp ) ? "Null response\t\t" : $temp;

			// the cache control header
			$temp = wp_remote_retrieve_header( $fetch_raw, 'cache-control' );
			$runs[ $i ]->response_cachecontrol = is_array( $temp ) ? 'WARNING, ' . count( $temp ) .' headers found' : empty( $temp ) ? "Null response\t\t" : $temp;

			// canonical or redirect?
			$runs[ $i ]->response_canonical = 'Null response'; // default value, override if another is found
			$temp = wp_remote_retrieve_header( $fetch_raw, 'location' );
			if (
				! empty( $temp ) &&
				! is_array( $temp )
			)
			{
				$runs[ $i ]->response_canonical = $this->find_url( $temp );
			}//end if
			else
			{
				preg_match_all( '#<link([^>]+)(/>|></link>)#is', wp_remote_retrieve_body( $fetch_raw ), $matches );
				foreach ( $matches[1] as $temp )
				{
					if ( preg_match( '#rel\s?=[^=]*canonical#is', $temp ) )
					{
						$runs[ $i ]->response_canonical = $this->find_url( $temp );
						break;
					}
				}
			}//end else

			WP_CLI::line( sprintf(
				"%d\t%sK\t%s\t%d\t%s\t%s\t%s",
				$runs[ $i ]->response_code,
				number_format( $runs[ $i ]->response_size / 1024, 1 ),
				number_format( $runs[ $i ]->response_time, 2 ),
				$runs[ $i ]->response_cookies,
				$runs[ $i ]->response_modified,
				$runs[ $i ]->response_cachecontrol,
				$runs[ $i ]->response_canonical
			) );
		}//end for

		// indeiscriminately attempt to clear the session
		// prevents accumulation of sessions in user_meta
		$this->clear_auth_session( $args->user_id );
	}//end test_url

	private function find_url( $text )
	{
		// nice regex thanks to John Gruber http://daringfireball.net/2010/07/improved_regex_for_matching_urls
		preg_match_all( '#(?i)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))#', $text, $urls );

		if ( ! isset( $urls[0][0] ) )
		{
			return NULL;
		}

		return $urls[0][0];
	}//end find_url

	private function get_auth_cookies( $user_id )
	{
		$expiration = time() + DAY_IN_SECONDS;

		require_once( ABSPATH . WPINC . '/session.php' );

		$manager = WP_Session_Tokens::get_instance( $user_id );
		$this->token = $manager->create( $expiration );

		return array(
			SECURE_AUTH_COOKIE => wp_generate_auth_cookie( $user_id, $expiration, 'secure_auth', $this->token ),
			AUTH_COOKIE        => wp_generate_auth_cookie( $user_id, $expiration, 'auth',        $this->token ),
			LOGGED_IN_COOKIE   => wp_generate_auth_cookie( $user_id, $expiration, 'logged_in',   $this->token ),
		);
	}//end get_auth_cookies

	private function clear_auth_session( $user_id )
	{
		if ( $this->token )
		{
			$manager = WP_Session_Tokens::get_instance( $user_id );
			$manager->destroy( $this->token );
		}
	}//end clear_auth_session
}//end class