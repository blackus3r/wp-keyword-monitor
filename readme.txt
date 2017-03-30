=== WP Keyword Monitor ===
Contributors: filme-blog
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=28WZAXQDXYZ5A
Tags: seo, keyword monitor, ranking, rank checker, google, keywords
Requires at least: 2.3.1
Tested up to: 4.7
Stable tag: 1.0.5
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.html

WP Keyword Monitor (KeyMon) is a powerful SEO Tool to track your keyword rankings in google serps.

== Description ==

WP Keyword Monitor is the first SEO rank checker plugin for wordpress, which uses the official API from Google. This plugin helps you to track your ranking keywords.
You can track up to 100 keywords for free per day.
This plugin is 100% free!

You also have the ability to import all keywords from Yoast SEO.

This project is actively maintained on [Github](https://github.com/blackus3r/wp-keyword-monitor).

Please be careful, this plugin is new and in beta status.

== Installation ==

1. Upload `/wp-keyword-monitor/` to the `/wp-content/plugins/` directory in your WordPress blog.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to KeyMon > Settings to configure this plugin.

== Usage ==

1. Go to KeyMon > Settings an fill in all necessary parameters.
1. Now go to KeyMon > Statistics and add your keywords.
1. Wait some minutes. WP Keyword Monitor will check the rankings of you keywords in the background.

== Screenshots ==

1. Here you can see the statistics for your keywords.
1. All keywords can be found in a table.
1. These are the settings.

== Donation ==
You can donate to this project via PayPal by visiting this page: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=28WZAXQDXYZ5A

== Changelog ==

= 1.0.5 =

* Fixed statistics page which does not show the correct rankings, if interval is greater than one.
* Fixed a bug when searching in deep rankings over 10 results.

= 1.0.4 =

* Fixed a bug in the rank checker, which always calculates a ranking with position '1'

= 1.0.3 =
This version contains many bug fixes. Please upgrade as soon as possible.

* Fixed check interval calculation.
* Fixed copyright in files.
* Fixed limit daily api calls.
* Fixed error handling, if daily limit exceeded.
* Refactored rank checker, to be smarter.
* Refactored everything, that uses date() without the current timezone information.
* Added support to print the current version in settingspage.
* Added support to print the used API calls.

= 1.0.2 =
* Fixed behaviour when limiting the maximum api calls.

= 1.0.1 =
* Added some links to support forum and other plugins from me.
* Added support to define the max API-calls per day
* Fixed import button
* Fixed add to chart button

= 1.0.0 =
* First Version
