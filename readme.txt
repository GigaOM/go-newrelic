=== Gigaom New Relic ===
Contributors: misterbisson
Tags: monitoring, telemetry, server monitoring, performance monitoring
Requires at least: 3.5.1
Tested up to: 3.6.1
Stable tag: trunk

Configures New Relic to better track performance, errors, and uptime of WordPress sites, including multisite

== Description ==

Sets the New Relic app name and other configuration values based on WordPress configuration. New Relic profiles code performance and activity, http://newrelic.com .

Separately reports user-facing and dashboard activity, even separates cron and admin-ajax activity to allow different QoS and alert settings for each.

Each blog in a multi-site installation is tracked separately, using the name of the blog as the app name.

== Installation ==

1. Install and activate New Relic's PHP agent, https://docs.newrelic.com/docs/php/new-relic-for-php#installation
1. The web server should appear in New Relic's dashboard
1. Download and activate this plugin
1. Go back to the New Relic dashboard and enjoy monitoring each WordPress blog (and different aspects of each blog)

== Screenshots ==

None yet.