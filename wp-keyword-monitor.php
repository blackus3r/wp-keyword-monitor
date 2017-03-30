<?php
/*
Plugin Name: WP Keyword Monitor
Plugin URI: https://wordpress.org/plugins/wp-keyword-monitor
Description: WP Keyword Monitor (KeyMon) is a powerful SEO Tool to track your keyword rankings in google serps.
Version: 1.0.5
Author: Patrick Hausmann
Author URI: https://profiles.wordpress.org/filme-blog/
License: GPLv3
Text Domain: wp-keyword-monitor
*/

include_once __DIR__."/lib/wpkeywordmonitor.php";
include_once __DIR__."/lib/rankchecker.php";
include_once __DIR__."/model/keyword.php";
include_once __DIR__."/model/keywordresults.php";
include_once __DIR__."/page/settingspage.php";
include_once __DIR__."/page/statisticspage.php";
include_once __DIR__."/query/keywordresultquery.php";
include_once __DIR__."/query/keywordquery.php";

const WP_KEYWORD_MONITOR_VERSION = "1.0.4";



const WP_KEYWORD_MONITOR_OPTIONS = "wp-keyword-monitor-options";
const WP_KEYWORD_MONITOR_ERROR = "wp-keyword-monitor-error";
const WP_KEYWORD_MONITOR_USED_CALLS = "wp-keyword-monitor-used-calls";
const WP_KEYWORD_MONITOR_KEYWORD_CHARTS= "wp-keyword-monitor-keyword-charts";
const WP_KEYWORD_MONITOR_TEXT_DOMAIN= "wp-keyword-monitor";
const WP_KEYWORD_MONITOR = "wp-keyword-monitor";

if (is_admin())
{

    $settingsPage = new \WpKeywordMonitor\Page\Settings();
    $statisticsPage = new \WpKeywordMonitor\Page\Statistics();

    add_action('admin_menu', function () {
        global $settingsPage, $statisticsPage;

        add_menu_page(
            __("Statistics", WP_KEYWORD_MONITOR_TEXT_DOMAIN),
            __("KeyMon", WP_KEYWORD_MONITOR_TEXT_DOMAIN),
            'manage_options',
            "wp-keyword-monitor-statistics",
            array($statisticsPage, "createPage"),
	        "dashicons-chart-line"
        );

        add_submenu_page(
            "wp-keyword-monitor-statistics",
            __("Settings", WP_KEYWORD_MONITOR_TEXT_DOMAIN),
            __("Settings", WP_KEYWORD_MONITOR_TEXT_DOMAIN),
            'manage_options',
            "wp-keyword-monitor-settings",
            array($settingsPage, "createPage")
        );
    });
}

register_activation_hook(__FILE__, function(){
    global $wpdb;
    \WpKeywordMonitor\WpKeywordMonitor::install($wpdb);

    if (! wp_next_scheduled ( 'checkRanks' ))
    {
        wp_schedule_event(time(), '10min', "checkRanks");
    }
});


add_action( 'init', function () {
    $domain = WP_KEYWORD_MONITOR_TEXT_DOMAIN;
    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
    if ( $loaded = load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' ) )
    {
        return $loaded;
    }
    else
    {
        load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/lang/' );
    }
});

add_action("admin_enqueue_scripts", function ($hook) {

    wp_register_style( 'wp-keyword-monitor-css', plugins_url( "resources/css/wp-keyword-monitor.css", __FILE__ ), false, '1.0.0' );
    wp_enqueue_style( 'wp-keyword-monitor-css' );

    wp_enqueue_script(WP_KEYWORD_MONITOR, plugins_url( "resources/js/Chart.bundle.min.js", __FILE__ ));
});

add_filter('cron_schedules',function($schedules){
    return \WpKeywordMonitor\WpKeywordMonitor::enhanceCronSchedule($schedules);
});

add_action('checkRanks', function() {
    global $wpdb;
    \WpKeywordMonitor\WpKeywordMonitor::checkRanks($wpdb);
});

register_deactivation_hook(__FILE__, 'pluginDeactivated');
function pluginDeactivated() {
	wp_clear_scheduled_hook('checkRanks');
}

if (! wp_next_scheduled ( 'checkRanks' ))
{
    wp_schedule_event(time(), '10min', "checkRanks");
}