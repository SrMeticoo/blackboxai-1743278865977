<?php
/**
 * Lottery number selection template
 */

if (!defined('ABSPATH')) {
    exit;
}

global $product;
?>

<div class="lottery-number-selection">
    <h3><?php _e('Select Your Lucky Number', 'wc-lottery'); ?></h3>
    
    <div class="lottery-number-grid">
        <?php foreach ($available_numbers as $number): ?>
            <label class="number-option">
                <input type="radio" name="lottery_number" value="<?php echo esc_attr($number); ?>">
                <span class="number"><?php echo esc_html($number); ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <div class="lucky-number-generator">
        <button type="button" class="generate-lucky-numbers" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
            <i class="fas fa-dice"></i> <?php _e('Feel Lucky? Generate Random Numbers!', 'wc-lottery'); ?>
        </button>
        <select class="lucky-number-count">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>"><?php printf(_n('%d number', '%d numbers', $i, 'wc-lottery'), $i); ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="selected-numbers">
        <h4><?php _e('Your Selected Numbers:', 'wc-lottery'); ?></h4>
        <div class="selected-numbers-display"></div>
    </div>
</div>