<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Product_Lottery extends WC_Product {
    protected $post_type = 'product';
    protected $product_type = 'lottery';

    public function __construct($product) {
        parent::__construct($product);
    }

    public function get_type() {
        return 'lottery';
    }

    // Custom lottery fields
    public function get_lottery_start_date($context = 'view') {
        return $this->get_prop('lottery_start_date', $context);
    }

    public function get_lottery_end_date($context = 'view') {
        return $this->get_prop('lottery_end_date', $context);
    }

    public function get_min_tickets($context = 'view') {
        return $this->get_prop('min_tickets', $context);
    }

    public function get_max_tickets($context = 'view') {
        return $this->get_prop('max_tickets', $context);
    }

    public function get_available_tickets($context = 'view') {
        $max_tickets = $this->get_max_tickets();
        $sold_tickets = $this->get_sold_tickets();
        return $max_tickets - $sold_tickets;
    }

    public function get_sold_tickets() {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wc_lottery_tickets WHERE lottery_id = %d",
            $this->get_id()
        ));
    }

    // Setters
    public function set_lottery_start_date($date) {
        $this->set_prop('lottery_start_date', $date);
    }

    public function set_lottery_end_date($date) {
        $this->set_prop('lottery_end_date', $date);
    }

    public function set_min_tickets($number) {
        $this->set_prop('min_tickets', absint($number));
    }

    public function set_max_tickets($number) {
        $this->set_prop('max_tickets', absint($number));
    }

    // Check if lottery is active
    public function is_lottery_active() {
        $now = current_time('timestamp');
        $start_date = strtotime($this->get_lottery_start_date());
        $end_date = strtotime($this->get_lottery_end_date());

        return ($now >= $start_date && $now <= $end_date);
    }

    // Check if lottery has ended
    public function is_lottery_ended() {
        $now = current_time('timestamp');
        $end_date = strtotime($this->get_lottery_end_date());

        return ($now > $end_date);
    }

    // Check if lottery has enough participants
    public function has_enough_participants() {
        return $this->get_sold_tickets() >= $this->get_min_tickets();
    }

    // Generate random winning numbers
    public function generate_winning_numbers($count = 1) {
        global $wpdb;
        
        $tickets = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wc_lottery_tickets 
            WHERE lottery_id = %d AND status = 'active'
            ORDER BY RAND() LIMIT %d",
            $this->get_id(),
            $count
        ));

        return $tickets;
    }

    // Override virtual product methods
    public function is_virtual() {
        return true;
    }

    public function is_downloadable() {
        return false;
    }

    public function is_purchasable() {
        return $this->is_lottery_active() && $this->get_available_tickets() > 0;
    }
}