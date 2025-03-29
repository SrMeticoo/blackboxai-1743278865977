<?php
/**
 * Admin Winners Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$winners = WC_Lottery::instance()->get_lottery_winners();
?>

<div class="wrap">
    <h1><?php _e('Lottery Winners', 'wc-lottery'); ?></h1>

    <div class="lottery-winners-table">
        <?php if ($winners): ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Lottery', 'wc-lottery'); ?></th>
                        <th><?php _e('Winner', 'wc-lottery'); ?></th>
                        <th><?php _e('Ticket Number', 'wc-lottery'); ?></th>
                        <th><?php _e('Won Date', 'wc-lottery'); ?></th>
                        <th><?php _e('Actions', 'wc-lottery'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($winners as $winner): ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($winner->lottery_id); ?>">
                                    <?php echo esc_html($winner->lottery_name); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($winner->winner_name); ?></td>
                            <td><?php echo esc_html($winner->ticket_id); ?></td>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($winner->won_at)); ?></td>
                            <td>
                                <button class="button notify-winner" data-winner-id="<?php echo esc_attr($winner->id); ?>">
                                    <i class="fas fa-envelope"></i> <?php _e('Notify Winner', 'wc-lottery'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-winners">
                <p><?php _e('No lottery winners yet.', 'wc-lottery'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.notify-winner').on('click', function() {
        var winnerId = $(this).data('winner-id');
        var button = $(this);
        
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'notify_lottery_winner',
                winner_id: winnerId,
                nonce: '<?php echo wp_create_nonce('notify_lottery_winner'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    button.html('<i class="fas fa-check"></i> <?php _e('Notified', 'wc-lottery'); ?>');
                } else {
                    button.prop('disabled', false);
                    alert(response.data);
                }
            },
            error: function() {
                button.prop('disabled', false);
                alert('<?php _e('Error notifying winner', 'wc-lottery'); ?>');
            }
        });
    });
});
</script>