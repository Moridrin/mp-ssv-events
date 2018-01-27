<?php

namespace mp_ssv_events;

use mp_ssv_general\base\BaseFunctions;
use mp_ssv_general\base\SSV_Global;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

abstract class SSV_Events
{
    const PATH = SSV_EVENTS_PATH;
    const URL = SSV_EVENTS_URL;

    const TICKET_FORM_REFERER = 'ssv_events__tickets_admin_referer';

    const OPTION_PUBLISH_ERROR = 'ssv_events__option__event_publish_error';
    const OPTION_MAPS_API_KEY = 'ssv_events__option__google_maps_api_key';
    const OPTION_EVENTS_PAGE = 'ssv_events__hidden_option__events_page';

    const TAG_EVENTS_OVERVIEW = '[ssv_events_overview]';

    const TICKETS_TABLE = SSV_EVENTS_TICKETS_TABLE;
    const REGISTRATIONS_TABLE = SSV_EVENTS_REGISTRATIONS_TABLE;

    public static function setupForBlog()
    {
        $wpdb = SSV_Global::getDatabase();
        $charset_collate = $wpdb->get_charset_collate();
        $tableName      = $wpdb->prefix . "ssv_event_tickets";
        $sql
                        = "
            CREATE TABLE IF NOT EXISTS $tableName (
                t_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                t_e_id bigint(20) NOT NULL,
                t_title VARCHAR(255) NOT NULL,
                t_start VARCHAR(255) NOT NULL,
                t_end VARCHAR(255) NOT NULL,
                t_price DECIMAL(6,2) NOT NULL,
                t_f_id BIGINT(20) NOT NULL,
                UNIQUE KEY (`t_e_id`, `t_title`)
            ) $charset_collate;";
        $wpdb->query($sql);
        $tableName = $wpdb->prefix . "ssv_event_registrations";
        $sql
                   = "
            CREATE TABLE IF NOT EXISTS $tableName (
                r_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                r_e_id bigint(20),
                r_t_id bigint(20),
                r_userId bigint(20),
                r_data VARCHAR(255),
                r_status VARCHAR(15) NOT NULL DEFAULT 'pending'
            ) $charset_collate;";
        $wpdb->query($sql);

        $registerPost = array(
            'post_content' => self::TAG_EVENTS_OVERVIEW,
            'post_name'    => 'events',
            'post_title'   => 'Events',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );
        $postId = wp_insert_post($registerPost);
        update_option(self::OPTION_EVENTS_PAGE, $postId);
    }

    public static function setup($networkEnable)
    {
        if ($networkEnable) {
            SSV_Global::runFunctionOnAllSites([self::class, 'setupForBlog']);
        } else {
            self::setupForBlog();
        }
    }

    public static function enqueueScripts()
    {
        wp_enqueue_script('ssv_events_maps', SSV_Events::URL . '/js/maps.js', array('jquery'));
        wp_enqueue_style('ssv_events_main_css', SSV_Events::URL . '/css/ssv-events.css');
    }

    public static function showMapsApiKeyMissingMessage()
    {
        if (empty(get_option(self::OPTION_MAPS_API_KEY))) {
            ?>
            <div class="update-nag notice">
                <p>You still need to set the Google Maps API Key in order for the maps to work.</p>
                <p><a href="<?= admin_url('admin.php') ?>?page=ssv-events-settings&tab=general">Set Now</a></p>
            </div>
            <?php
        }
    }

    public static function eventsCategoryTitleFilter($title)
    {
        if (strtolower($title) === 'archives: events') {
            return 'Events';
        } else {
            return $title;
        }
    }

    public static function cleanInstallForBlog()
    {
        $wpdb = SSV_Global::getDatabase();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $tableName      = $wpdb->prefix . "ssv_event_tickets";
        $wpdb->query("DROP TABLE $tableName;");
        $tableName = $wpdb->prefix . "ssv_event_registrations";
        $wpdb->query("DROP TABLE $tableName;");
        $postId = get_option(self::OPTION_EVENTS_PAGE);
        wp_delete_post($postId);
        self::setupForBlog();
    }

    public static function CLEAN_INSTALL($networkEnable)
    {
        if ($networkEnable) {
            SSV_Global::runFunctionOnAllSites([self::class, 'cleanInstallForBlog']);
        } else {
            self::cleanInstallForBlog();
        }
    }
}

register_activation_hook(SSV_FORMS_ACTIVATOR_PLUGIN, [SSV_Events::class, 'setup']);
add_action('wp_enqueue_scripts', [SSV_Events::class, 'enqueueScripts']);
add_filter('get_the_archive_title', [SSV_Events::class, 'eventsCategoryTitleFilter'], 10);
