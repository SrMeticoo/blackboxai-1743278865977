<?php
/**
 * Lottery tab template
 */

if (!defined('ABSPATH')) {
    exit;
}

global $product;
?>

<div class="lottery-details">
    <div class="lottery-status">
        <h3><?php _e('Lottery Status', 'wc-lottery'); ?></h3>
        <?php if ($product->is_lottery_active()): ?>
            <span class="status active"><?php _e('Active', 'wc-lottery'); ?></span>
        <?php elseif ($product->is_lottery_ended()): ?>
            <span class="status ended"><?php _e('Ended', 'wc-lottery'); ?></span>
        <?php else: ?>
            <span class="status upcoming"><?php _e('Upcoming', 'wc-lottery'); ?></span>
        <?php endif; ?>
    </div>

    <div class="lottery-dates">
        <div class="start-date">
            <strong><?php _e('Start Date:', 'wc-lottery'); ?></strong>
            <?php echo esc_html($product->get_lottery_start_date()); ?>
        </div>
        <div class="end-date">
            <strong><?php _e('End Date:', 'wc-lottery'); ?></strong>
            <?php echo esc_html($product->get_lottery_end_date()); ?>
        </div>
    </div>

    <div class="lottery-progress">
        <h3><?php _e('Ticket Sales Progress', 'wc-lottery'); ?></h3>
        <div class="progress-bar">
            <?php
            $sold = $product->get_sold_tickets();
            $max = $product->get_max_tickets();
            $percentage = ($sold / $max) * 100;
            ?>
            <div class="progress" style="width: <?php echo esc_attr($percentage); ?>%"></div>
        </div>
        <div class="progress-stats">
            <span class="sold"><?php printf(__('%d tickets sold', 'wc-lottery'), $sold); ?></span>
            <span class="remaining"><?php printf(__('%d tickets remaining', 'wc-lottery'), $max - $sold); ?></span>
        </div>
    </div>

    <?php if ($product->is_lottery_ended() && $product->has_enough_participants()): ?>
        <div class="lottery-winners">
            <h3><?php _e('Winners', 'wc-lottery'); ?></h3>
            <?php
            $winners = WC_Lottery::instance()->get_lottery_winners($product->get_id());
            if ($winners): ?>
                <ul class="winners-list">
                    <?php foreach ($winners as $winner): ?>
                        <li>
                            <span class="winner-name"><?php echo esc_html($winner->winner_name); ?></span>
                            <span class="ticket-number"><?php echo esc_html($winner->ticket_id); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><?php _e('Winners have not been drawn yet.', 'wc-lottery'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>