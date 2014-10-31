# === Gigaom New Relic ===

Contributors: misterbisson

Tags: monitoring, telemetry, server monitoring, performance monitoring, newrelic, new relic, Gigaom

Requires at least: 3.5.1

Tested up to: 4.0

Stable tag: trunk

Configures New Relic to better track performance, errors, and uptime of WordPress sites, including multisite

## == Description ==

Supports both <a href="http://newrelic.com">New Relic</a> APM and Browser monitoring to give a clear picture of how your site performs both on the server and in the browser.

### = Application Performance Monitoring (APM) =

Automatically detects if the <a href="https://docs.newrelic.com/docs/agents/php-agent/getting-started/new-relic-php">APM extensions</a> are installed on the server. If so, the plugin will start reporting into the New Relic account associated with the <a href="https://docs.newrelic.com/docs/agents/php-agent/getting-started/new-relic-php#license_key">license key used when installing the extension</a>.

There's no UI, but the plugin automatically sets the app name and other configuration values ideally for each request. The app name is based on the blog's name. User-facing and dashboard activity are reported as separate apps so you can set different QoS and alert settings for each. Even  cron and admin-ajax activity are separated out for individual tracking.

Each blog in a multi-site installation is tracked separately, using the name of the blog as the app name.

### = Browser monitoring (RUM) =

Real user monitoring (browser monitoring) is automatically enabled if the APM extension is active, but in situations where the APM extension can't be used, the plugin can still be used to track browser performance.

This mode requires some configuration:

1. Get <a href="https://docs.newrelic.com/docs/browser/new-relic-browser/installation-configuration/adding-apps-new-relic-browser#copy-paste-app">the tracking JavaScript from New Relic</a>.
1. Go to your WordPress dashboard -> Settings -> New Relic Settings and paste in the JavaScript
1. Go to the New Relic dashboard to see your site reporting performance data!

The plugin extracts the configuration details from the JS and inserts them with a clean copy of the JS on each page (this cannot be used to inject arbitrary JS into the page).

Due to limitations of the Browser monitoring service/API, Browser-only monitoring does not include all the data or separate reporting of activity in separate apps as APM does.

### = In the WordPress.org plugin repo =

Here: https://wordpress.org/plugins/go-newrelic/

### = Fork me! =

This plugin is on Github: https://github.com/gigaOM/go-newrelic

## == Installation ==

1. Install and activate New Relic's PHP agent, https://docs.newrelic.com/docs/php/new-relic-for-php#installation
1. The web server should appear in New Relic's dashboard
1. Download and activate this plugin from http://wordpress.org/plugins/go-newrelic/
1. Go back to the New Relic dashboard and enjoy monitoring each WordPress blog (and different aspects of each blog)
1. Follow the Gigaom engineering team at http://kitchen.gigaom.com and https://github.com/gigaom/

## == Screenshots ==

1. New Relic application list, showing two blogs. Each WordPress blog is reported as four applications in New Relic to separate reader, writer, cron, and admin-ajax activity for better detail and fine-grained control.
2. New Relic application overview, showing performance history for a single app.
