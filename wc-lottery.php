<?php
/**
 * Plugin Name: WooCommerce Lottery & Raffles
 * Plugin URI: 
 * Description: A complete lottery and raffle system for WooCommerce with random number selection, winner management, and lottery history.
 * Version: 1.0.0
 * Author: BLACKBOXAI
 * Text Domain: wc-lottery
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_LOTTERY_VERSION', '1.0.0');
define('WC_LOTTERY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_LOTTERY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check if WooCommerce is active
function wc_lottery_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                 __('WooCommerce Lottery requires WooCommerce to be installed and active.', 'wc-lottery') . 
                 '</p></div>';
        });
        return false;
    }
    return true;
}

// Initialize the plugin
function wc_lottery_init() {
    if (!wc_lottery_check_woocommerce()) {
        return;
    }

    // Load text domain
    load_plugin_textdomain('wc-lottery', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Include required files
    require_once WC_LOTTERY_PLUGIN_DIR . 'includes/class-wc-lottery.php';
    require_once WC_LOTTERY_PLUGIN_DIR . 'includes/class-wc-lottery-product.php';
    require_once WC_LOTTERY_PLUGIN_DIR . 'includes/class-wc-lottery-admin.php';
    require_once WC_LOTTERY_PLUGIN_DIR . 'includes/class-wc-lottery-frontend.php';

    // Initialize main plugin class
    WC_Lottery::instance();
}

add_action('plugins_loaded', 'wc_lottery_init');

// Activation hook
register_activation_hook(__FILE__, 'wc_lottery_activate');
function wc_lottery_activate() {
    // Create necessary database tables
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = array();
    
    // Lottery tickets table
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wc_lottery_tickets (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        lottery_id bigint(20) NOT NULL,
        ticket_number varchar(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        order_id bigint(20) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY lottery_id (lottery_id),
        KEY user_id (user_id),
        KEY order_id (order_id)
    ) $charset_collate;";
    
    // Lottery winners table
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wc_lottery_winners (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        lottery_id bigint(20) NOT NULL,
        ticket_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        won_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY lottery_id (lottery_id),
        KEY ticket_id (ticket_id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    foreach ($sql as $query) {
        dbDelta($query);
    }
}