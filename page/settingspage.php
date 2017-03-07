<?php
/**
 *
 * @copyright Patrick Hausmann
 * @author Patrick Hausmann <privat@patrck-designs.de>
 */

namespace WpKeywordMonitor\Page;
use WpKeywordMonitor\KeywordQuery;

/**
 *
 *
 */
class Settings
{
    const PWL_TEXT_DOMAIN = "wp-keyword-monitor";

    /**
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        $this->options = get_option(WP_KEYWORD_MONITOR_OPTIONS);
        add_action('admin_action_importKeywordsFromYoastSeo', array($this, "importFromYoast"));
        add_action('admin_init', array($this, 'pageInit'));
    }

    /**
     * Prints a statistic page.
     */
    function createPage()
    {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        ?>
        <div>
            <div style="float: left; width:50%;" class="wrap">
                <h1><?php _e("Settings", WP_KEYWORD_MONITOR_TEXT_DOMAIN); ?></h1>
                <p class="description"><?php _e("Current version", WP_KEYWORD_MONITOR_TEXT_DOMAIN); echo ": ".WP_KEYWORD_MONITOR_VERSION; ?></p>

                <p class="description">
                    <?php _e("Since 2008, Google does not longer allows us to parse the results page content, so we have to use the custom search API.", WP_KEYWORD_MONITOR_TEXT_DOMAIN) ?>
                </p>
                <form class="pwl-reset-form" method="post" action="options.php">
                    <?php
                    // This prints out all hidden setting fields
                    settings_fields('wp-keyword-monitor-option-group');
                    do_settings_sections('wp-keyword-monitor-settings');
                    submit_button(__("Save"), "primary", "submit", false);
                    ?>
                </form>
            </div>

            <div style="float: left; width:40%;" class="wrap">
                <h1><?php _e("Tutorial and Help", WP_KEYWORD_MONITOR_TEXT_DOMAIN); ?></h1>
                <p class="description">
                    <?php _e('Need help? You can find a tutorial <a href="https://dazdaztech.wordpress.com/2013/08/03/using-google-custom-search-api-from-the-command-line/">here</a>.', WP_KEYWORD_MONITOR_TEXT_DOMAIN) ?>
                </p>
                <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=28WZAXQDXYZ5A" class="button"><?php _e("Donate", WP_KEYWORD_MONITOR_TEXT_DOMAIN);?></a>
                <a target="_blank" href="https://wordpress.org/support/plugin/wp-keyword-monitor" class="button"><?php _e("Support Forum", WP_KEYWORD_MONITOR_TEXT_DOMAIN);?></a>
                <a target="_blank" href="https://github.com/blackus3r/wp-keyword-monitor/" class="button"><?php _e("Github", WP_KEYWORD_MONITOR_TEXT_DOMAIN);?></a>
            </div>

            <div style="margin-top:20px; float: left; width:40%;" class="wrap">
                <h1><?php _e("Import", WP_KEYWORD_MONITOR_TEXT_DOMAIN); ?></h1>

                <form method="post" action="<?php echo admin_url( 'admin.php' ); ?>">
                    <button name="action" value="importKeywordsFromYoastSeo" class="button button-primary"><?php _e("Import Keywords from Yoast SEO"); ?></button>
                </form>
            </div>

            <div style="margin-top:20px; float: left; width:40%;" class="wrap">
                <h1><?php _e("Other Plugins from Me", WP_KEYWORD_MONITOR_TEXT_DOMAIN); ?></h1>
                <a target="_blank" href="https://de.wordpress.org/plugins/post-worktime-logger/" class="button"><?php _e("Post Worktime Logger", WP_KEYWORD_MONITOR_TEXT_DOMAIN);?></a>
            </div>

        </div>

        <?php
    }


    public function importFromYoast()
    {
        global $wpdb;

        $results = $wpdb->get_results("
          SELECT 
            meta_value
          FROM ".$wpdb->prefix."postmeta
          WHERE `meta_key`='_yoast_wpseo_focuskw'", ARRAY_A );


        $keywordQuery = new KeywordQuery($wpdb);

        foreach ($results as $result)
        {
            if (!empty($result["meta_value"])) $keywordQuery->addKeyword($result["meta_value"]);
        }
        wp_redirect($_SERVER['HTTP_REFERER']);
        exit();
    }

    /**
     * Register and add settings
     */
    public function pageInit()
    {
        register_setting(
            'wp-keyword-monitor-option-group', // Option group
            WP_KEYWORD_MONITOR_OPTIONS, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'general',
            __('General',WP_KEYWORD_MONITOR_TEXT_DOMAIN),
            null, // Callback
            'wp-keyword-monitor-settings' // Page
        );

        add_settings_field(
            'apiKey',
            __('API-Key', WP_KEYWORD_MONITOR_TEXT_DOMAIN),
            array( $this, 'apiKeyCallback'),
            'wp-keyword-monitor-settings',
            'general'
        );

        add_settings_field(
            'cx',
            __('CX', WP_KEYWORD_MONITOR_TEXT_DOMAIN),
            array( $this, 'cxCallback'),
            'wp-keyword-monitor-settings',
            'general'
        );

        add_settings_field(
            'domain',
            __('Domain', WP_KEYWORD_MONITOR_TEXT_DOMAIN),
            array( $this, 'domainCallback'),
            'wp-keyword-monitor-settings',
            'general'
        );

        add_settings_field(
            'searchDepth',
            __('Search depth', WP_KEYWORD_MONITOR_TEXT_DOMAIN),
            array( $this, 'searchDepthCallback'),
            'wp-keyword-monitor-settings',
            'general'
        );

        add_settings_field(
            'checkInterval',
            __('Check interval', WP_KEYWORD_MONITOR_TEXT_DOMAIN),
            array( $this, 'checkIntervalCallback'),
            'wp-keyword-monitor-settings',
            'general'
        );

        add_settings_field(
            'maxApiCallsPerDay',
            __('Maximum API-calls per day', WP_KEYWORD_MONITOR_TEXT_DOMAIN),
            array( $this, 'maxApiCallsPerDayCallback'),
            'wp-keyword-monitor-settings',
            'general'
        );

        add_settings_field(
            'autoMode',
            __('Auto mode', WP_KEYWORD_MONITOR_TEXT_DOMAIN),
            array( $this, 'autoModeCallback'),
            'wp-keyword-monitor-settings',
            'general'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $_input Contains all settings fields as array keys
     *
     * @return array
     */
    public function sanitize($_input)
    {
        $newInput = array();
        if( isset( $_input['apiKey'] ) )
        {
            $newInput['apiKey'] = sanitize_text_field( wp_unslash( $_input['apiKey']));
        }

        if( isset( $_input['cx'] ) )
        {
            $newInput['cx'] = sanitize_text_field( wp_unslash( $_input['cx']));
        }

        if( isset( $_input['domain'] ) )
        {
            $newInput['domain'] = sanitize_text_field( wp_unslash( $_input['domain']));
        }

        if( isset( $_input['autoMode'] ) )
        {
            $newInput['autoMode'] = sanitize_text_field( wp_unslash( $_input['autoMode']));
        }

        if( isset( $_input['checkInterval'] ) )
        {
            $newInput['checkInterval'] = sanitize_text_field( wp_unslash( $_input['checkInterval']));
        }

        if( isset( $_input['searchDepth'] ) )
        {
            $newInput['searchDepth'] = sanitize_text_field( wp_unslash( $_input['searchDepth']));
        }

        if( isset( $_input['maxApiCallsPerDay'] ) )
        {
            $newInput['maxApiCallsPerDay'] = sanitize_text_field( wp_unslash( $_input['maxApiCallsPerDay']));
        }

        return $newInput;
    }

    public function apiKeyCallback()
    {
        if (isset($this->options["apiKey"])) $value = $this->options["apiKey"];
        else $value = null;
        ?>
        <input type="text" id="apiKey" name="<?php echo WP_KEYWORD_MONITOR_OPTIONS ?>[apiKey]" value="<?php echo $value ?>"/>
        <p class="description">
            <?php _e("You can get your Google API-Key <a href=\"https://console.developers.google.com/apis/credentials\">here</a>.", WP_KEYWORD_MONITOR_TEXT_DOMAIN)?>
        </p>
        <?php
    }

    public function cxCallback()
    {
        if (isset($this->options["cx"])) $value = $this->options["cx"];
        else $value = null;
        ?>
        <input type="text" id="cx" name="<?php echo WP_KEYWORD_MONITOR_OPTIONS ?>[cx]" value="<?php echo $value ?>"/>
        <p class="description">
            <?php _e("You can get your Google cx  <a href=\"https://cse.google.com/cse/all\">here</a>.", WP_KEYWORD_MONITOR_TEXT_DOMAIN)?>
        </p>
        <?php
    }

    public function domainCallback()
    {
        if (isset($this->options["domain"])) $value = $this->options["domain"];
        else $value = null;
        ?>
        <input type="text" id="domain" name="<?php echo WP_KEYWORD_MONITOR_OPTIONS ?>[domain]" value="<?php echo $value ?>"/>
        <p class="description">
            <?php _e("Without www or http, like derpade.de.", WP_KEYWORD_MONITOR_TEXT_DOMAIN)?>
        </p>
        <?php
    }

    public function searchDepthCallback()
    {
        if (isset($this->options["searchDepth"])) $value = $this->options["searchDepth"];
        else $value = 1;
        ?>
        <input type="number" id="searchDepth" name="<?php echo WP_KEYWORD_MONITOR_OPTIONS ?>[searchDepth]" value="<?php echo $value ?>"/>
        <p class="description">
            <?php _e("How many sites should we check for your domain? Please be careful, because this reacts like a multiplicator.", WP_KEYWORD_MONITOR_TEXT_DOMAIN)?>
        </p>
        <?php
    }

    public function checkIntervalCallback()
    {
        if (isset($this->options["checkInterval"])) $value = $this->options["checkInterval"];
        else $value = 1;
        ?>
        <input type="number" id="checkInterval" name="<?php echo WP_KEYWORD_MONITOR_OPTIONS ?>[checkInterval]" value="<?php echo $value ?>"/>
        <p class="description">
            <?php _e("The check interval in days. A good value is 3 days.", WP_KEYWORD_MONITOR_TEXT_DOMAIN)?>
        </p>
        <?php
    }

    public function maxApiCallsPerDayCallback()
    {
        if (isset($this->options["maxApiCallsPerDay"])) $value = $this->options["maxApiCallsPerDay"];
        else $value = 100;

            $usedApiCallsWithDate = get_option(WP_KEYWORD_MONITOR_USED_CALLS, 0);

            $today = date("Y-m-d", current_time("timestamp"));
            if (isset($usedApiCallsWithDate[$today])) $usedApiCalls = (int)$usedApiCallsWithDate[$today];
            else $usedApiCalls = 0;
        ?>
        <input type="number" id="checkInterval" name="<?php echo WP_KEYWORD_MONITOR_OPTIONS ?>[maxApiCallsPerDay]" value="<?php echo $value ?>"/>
        <p class="description">
            <?php _e("Defines the maximum of API-calls that we can use per day. This is helpful if you share the google acount with other blogs.", WP_KEYWORD_MONITOR_TEXT_DOMAIN)?> <strong><?php _e("Used today:", WP_KEYWORD_MONITOR_TEXT_DOMAIN); ?> <?php echo $usedApiCalls; ?></strong>
        </p>
        <?php
    }


    public function autoModeCallback()
    {
        if (isset($this->options["autoMode"])) $value = $this->options["autoMode"];
        else $value = 1;
        ?>
        <input type="checkbox" id="searchDepth" name="<?php echo WP_KEYWORD_MONITOR_OPTIONS ?>[autoMode]" <?php checked($value, 'on' ); ?>"/>
        <p class="description">
            <?php _e("Enables or disables the auto mode.", WP_KEYWORD_MONITOR_TEXT_DOMAIN)?>
        </p>
        <?php
    }
}
