<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Lottery_Frontend {
    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Add lottery tab on single product page
        add_filter('woocommerce_product_tabs', array($this, 'add_lottery_tab'));
        
        // Display lottery information
        add_action('woocommerce_single_product_summary', array($this, 'display_lottery_info'), 25);
        
        // Add lottery number selection before add to cart
        add_action('woocommerce_before_add_to_cart_button', array($this, 'add_lottery_number_selection'));
        
        // Validate lottery number selection
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_lottery_number_selection'), 10, 3);
        
        // Add lottery number to cart item
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_lottery_number_to_cart'), 10, 3);
        
        // Display lottery number in cart
        add_filter('woocommerce_get_item_data', array($this, 'display_lottery_number_in_cart'), 10, 2);
        
        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // AJAX handlers for lucky number generator
        add_action('wp_ajax_generate_lucky_numbers', array($this, 'generate_lucky_numbers'));
        add_action('wp_ajax_nopriv_generate_lucky_numbers', array($this, 'generate_lucky_numbers'));
    }

    public function add_lottery_tab($tabs) {
        global $product;
        
        if ($product && $product->get_type() === 'lottery') {
            $tabs['lottery'] = array(
                'title' => __('Lottery Details', 'wc-lottery'),
                'priority' => 20,
                'callback' => array($this, 'lottery_tab_content')
            );
        }
        
        return $tabs;
    }

    public function lottery_tab_content() {
        global $product;
        
        if ($product && $product->get_type() === 'lottery') {
            wc_get_template(
                'single-product/tabs/lottery.php',
                array(
                    'product' => $product
                ),
                '',
                WC_LOTTERY_PLUGIN_DIR . 'templates/'
            );
        }
    }

    public function display_lottery_info() {
        global $product;
        
        if ($product && $product->get_type() === 'lottery') {
            wc_get_template(
                'single-product/lottery-info.php',
                array(
                    'product' => $product
                ),
                '',
                WC_LOTTERY_PLUGIN_DIR . 'templates/'
            );
        }
    }

    public function add_lottery_number_selection() {
        global $product;
        
        if ($product && $product->get_type() === 'lottery') {
            $available_numbers = get_post_meta($product->get_id(), '_lottery_numbers', true);
            $sold_numbers = $this->get_sold_numbers($product->get_id());
            
            wc_get_template(
                'single-product/lottery-number-selection.php',
                array(
                    'product' => $product,
                    'available_numbers' => array_diff($available_numbers, $sold_numbers)
                ),
                '',
                WC_LOTTERY_PLUGIN_DIR . 'templates/'
            );
        }
    }

    public function validate_lottery_number_selection($passed, $product_id, $quantity) {
        $product = wc_get_product($product_id);
        
        if ($product && $product->get_type() === 'lottery') {
            if (!isset($_POST['lottery_number']) || empty($_POST['lottery_number'])) {
                wc_add_notice(__('Please select a lottery number.', 'wc-lottery'), 'error');
                return false;
            }
            
            $number = absint($_POST['lottery_number']);
            $available_numbers = get_post_meta($product_id, '_lottery_numbers', true);
            $sold_numbers = $this->get_sold_numbers($product_id);
            
            if (!in_array($number, $available_numbers) || in_array($number, $sold_numbers)) {
                wc_add_notice(__('Selected lottery number is not available.', 'wc-lottery'), 'error');
                return false;
            }
        }
        
        return $passed;
    }

    public function add_lottery_number_to_cart($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['lottery_number'])) {
            $cart_item_data['lottery_number'] = absint($_POST['lottery_number']);
        }
        return $cart_item_data;
    }

    public function display_lottery_number_in_cart($item_data, $cart_item) {
        if (isset($cart_item['lottery_number'])) {
            $item_data[] = array(
                'key' => __('Lottery Number', 'wc-lottery'),
                'value' => $cart_item['lottery_number']
            );
        }
        return $item_data;
    }

    private function get_sold_numbers($product_id) {
        global $wpdb;
        return $wpdb->get_col($wpdb->prepare(
            "SELECT ticket_number FROM {$wpdb->prefix}wc_lottery_tickets WHERE lottery_id = %d",
            $product_id
        ));
    }

    public function generate_lucky_numbers() {
        check_ajax_referer('generate_lucky_numbers', 'nonce');
        
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $count = isset($_POST['count']) ? absint($_POST['count']) : 1;
        
        if (!$product_id) {
            wp_send_json_error(__('Invalid product', 'wc-lottery'));
        }
        
        $available_numbers = get_post_meta($product_id, '_lottery_numbers', true);
        $sold_numbers = $this->get_sold_numbers($product_id);
        $available_numbers = array_diff($available_numbers, $sold_numbers);
        
        if (count($available_numbers) < $count) {
            wp_send_json_error(__('Not enough numbers available', 'wc-lottery'));
        }
        
        $lucky_numbers = array_rand(array_flip($available_numbers), $count);
        wp_send_json_success(array('numbers' => $lucky_numbers));
    }

    public function enqueue_frontend_scripts() {
        if (is_product()) {
            global $product;
            
            if ($product && $product->get_type() === 'lottery') {
                wp_enqueue_style(
                    'wc-lottery-frontend',
                    WC_LOTTERY_PLUGIN_URL . 'assets/css/frontend.css',
                    array(),
                    WC_LOTTERY_VERSION
                );

                wp_enqueue_script(
                    'wc-lottery-frontend',
                    WC_LOTTERY_PLUGIN_URL . 'assets/js/frontend.js',
                    array('jquery'),
                    WC_LOTTERY_VERSION,
                    true
                );

                wp_localize_script('wc-lottery-frontend', 'wcLotteryParams', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('generate_lucky_numbers')
                ));
            }
        }
    }
}