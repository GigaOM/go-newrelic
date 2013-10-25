=== Gigaom New Relic ===
Contributors: misterbisson
Tags: monitoring, telemetry, server monitoring, performance monitoring, newrelic, new relic, Gigaom
Requires at least: 3.5.1
Tested up to: 3.6.1
Stable tag: trunk

Configures New Relic to better track performance, errors, and uptime of WordPress sites, including multisite

== Description ==

Sets the New Relic app name and other configuration values based on WordPress configuration. New Relic profiles code performance and activity, http://newrelic.com .

Separately reports user-facing and dashboard activity, even separates cron and admin-ajax activity to allow different QoS and alert settings for each.

Each blog in a multi-site installation is tracked separately, using the name of the blog as the app name.

Follow Gigaom at https://github.com/gigaom/ and http://kitchen.gigaom.com

== Installation ==

1. Install and activate New Relic's PHP agent, https://docs.newrelic.com/docs/php/new-relic-for-php#installation
1. The web server should appear in New Relic's dashboard
1. Download and activate this plugin from http://wordpress.org/plugins/go-newrelic/
1. Go back to the New Relic dashboard and enjoy monitoring each WordPress blog (and different aspects of each blog)

== Screenshots ==

1. New Relic application list, showing two blogs. Each WordPress blog is reported as four applications in New Relic to separate reader, writer, cron, and admin-ajax activity for better detail and fine-grained control.
2. New Relic application overview, showing performance history for a single app.