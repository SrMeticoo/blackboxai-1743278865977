<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Lottery_Admin {
    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Add lottery product data tabs
        add_filter('woocommerce_product_data_tabs', array($this, 'add_lottery_product_tab'));
        
        // Add lottery product data fields
        add_action('woocommerce_product_data_panels', array($this, 'add_lottery_product_panels'));
        
        // Save lottery product data
        add_action('woocommerce_process_product_meta_lottery', array($this, 'save_lottery_product_data'));
        
        // Add lottery menu items
        add_action('admin_menu', array($this, 'add_lottery_menu'));
        
        // Add custom columns to products list
        add_filter('manage_edit-product_columns', array($this, 'add_lottery_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'display_lottery_columns'), 10, 2);
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_lottery_product_tab($tabs) {
        $tabs['lottery'] = array(
            'label' => __('Lottery', 'wc-lottery'),
            'target' => 'lottery_product_data',
            'class' => array('show_if_lottery'),
            'priority' => 21
        );
        return $tabs;
    }

    public function add_lottery_product_panels() {
        global $post; ?>
        <div id="lottery_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                woocommerce_wp_text_input(array(
                    'id' => '_lottery_start_date',
                    'label' => __('Start Date', 'wc-lottery'),
                    'type' => 'datetime-local',
                    'desc_tip' => true,
                    'description' => __('Set the start date for this lottery', 'wc-lottery')
                ));

                woocommerce_wp_text_input(array(
                    'id' => '_lottery_end_date',
                    'label' => __('End Date', 'wc-lottery'),
                    'type' => 'datetime-local',
                    'desc_tip' => true,
                    'description' => __('Set the end date for this lottery', 'wc-lottery')
                ));

                woocommerce_wp_text_input(array(
                    'id' => '_min_tickets',
                    'label' => __('Minimum Tickets', 'wc-lottery'),
                    'type' => 'number',
                    'desc_tip' => true,
                    'description' => __('Minimum number of tickets that must be sold', 'wc-lottery')
                ));

                woocommerce_wp_text_input(array(
                    'id' => '_max_tickets',
                    'label' => __('Maximum Tickets', 'wc-lottery'),
                    'type' => 'number',
                    'desc_tip' => true,
                    'description' => __('Maximum number of tickets available', 'wc-lottery')
                ));

                // Add lucky number selector
                ?>
                <div class="form-field lottery_numbers_field">
                    <label><?php _e('Available Numbers', 'wc-lottery'); ?></label>
                    <div class="lottery-numbers-grid">
                        <?php
                        for ($i = 1; $i <= 100; $i++) {
                            echo '<label class="number-checkbox">
                                    <input type="checkbox" name="_lottery_numbers[]" value="' . $i . '">
                                    <span>' . $i . '</span>
                                  </label>';
                        }
                        ?>
                    </div>
                    <p class="description"><?php _e('Select available numbers for this lottery', 'wc-lottery'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function save_lottery_product_data($post_id) {
        $start_date = isset($_POST['_lottery_start_date']) ? sanitize_text_field($_POST['_lottery_start_date']) : '';
        $end_date = isset($_POST['_lottery_end_date']) ? sanitize_text_field($_POST['_lottery_end_date']) : '';
        $min_tickets = isset($_POST['_min_tickets']) ? absint($_POST['_min_tickets']) : '';
        $max_tickets = isset($_POST['_max_tickets']) ? absint($_POST['_max_tickets']) : '';
        $lottery_numbers = isset($_POST['_lottery_numbers']) ? array_map('absint', $_POST['_lottery_numbers']) : array();

        update_post_meta($post_id, '_lottery_start_date', $start_date);
        update_post_meta($post_id, '_lottery_end_date', $end_date);
        update_post_meta($post_id, '_min_tickets', $min_tickets);
        update_post_meta($post_id, '_max_tickets', $max_tickets);
        update_post_meta($post_id, '_lottery_numbers', $lottery_numbers);
    }

    public function add_lottery_menu() {
        add_submenu_page(
            'woocommerce',
            __('Lottery Winners', 'wc-lottery'),
            __('Lottery Winners', 'wc-lottery'),
            'manage_woocommerce',
            'wc-lottery-winners',
            array($this, 'winners_page')
        );

        add_submenu_page(
            'woocommerce',
            __('Past Lotteries', 'wc-lottery'),
            __('Past Lotteries', 'wc-lottery'),
            'manage_woocommerce',
            'wc-lottery-history',
            array($this, 'history_page')
        );
    }

    public function winners_page() {
        include WC_LOTTERY_PLUGIN_DIR . 'templates/admin/winners.php';
    }

    public function history_page() {
        include WC_LOTTERY_PLUGIN_DIR . 'templates/admin/history.php';
    }

    public function add_lottery_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            if ($key === 'price') {
                $new_columns['lottery_status'] = __('Lottery Status', 'wc-lottery');
                $new_columns['lottery_dates'] = __('Lottery Dates', 'wc-lottery');
                $new_columns['tickets_sold'] = __('Tickets Sold', 'wc-lottery');
            }
        }
        return $new_columns;
    }

    public function display_lottery_columns($column, $post_id) {
        $product = wc_get_product($post_id);
        if ($product && $product->get_type() === 'lottery') {
            switch ($column) {
                case 'lottery_status':
                    if ($product->is_lottery_active()) {
                        echo '<span class="lottery-status active">' . __('Active', 'wc-lottery') . '</span>';
                    } elseif ($product->is_lottery_ended()) {
                        echo '<span class="lottery-status ended">' . __('Ended', 'wc-lottery') . '</span>';
                    } else {
                        echo '<span class="lottery-status upcoming">' . __('Upcoming', 'wc-lottery') . '</span>';
                    }
                    break;

                case 'lottery_dates':
                    echo sprintf(
                        __('Start: %s<br>End: %s', 'wc-lottery'),
                        $product->get_lottery_start_date(),
                        $product->get_lottery_end_date()
                    );
                    break;

                case 'tickets_sold':
                    echo sprintf(
                        __('%d / %d', 'wc-lottery'),
                        $product->get_sold_tickets(),
                        $product->get_max_tickets()
                    );
                    break;
            }
        }
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_style(
                'wc-lottery-admin',
                WC_LOTTERY_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                WC_LOTTERY_VERSION
            );

            wp_enqueue_script(
                'wc-lottery-admin',
                WC_LOTTERY_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                WC_LOTTERY_VERSION,
                true
            );
        }
    }
}