<?php
/**
 * Admin Lottery History Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get all lottery products that have ended
$args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'tax_query' => array(
        array(
            'taxonomy' => 'product_type',
            'field' => 'slug',
            'terms' => 'lottery'
        )
    ),
    'meta_query' => array(
        array(
            'key' => '_lottery_end_date',
            'value' => current_time('mysql'),
            'compare' => '<',
            'type' => 'DATETIME'
        )
    )
);

$past_lotteries = new WP_Query($args);
?>

<div class="wrap">
    <h1><?php _e('Past Lotteries', 'wc-lottery'); ?></h1>

    <div class="lottery-history-filters">
        <select id="lottery-status-filter">
            <option value=""><?php _e('All Statuses', 'wc-lottery'); ?></option>
            <option value="completed"><?php _e('Completed', 'wc-lottery'); ?></option>
            <option value="failed"><?php _e('Failed', 'wc-lottery'); ?></option>
        </select>

        <input type="text" id="lottery-date-filter" class="date-picker" placeholder="<?php _e('Filter by date', 'wc-lottery'); ?>">
    </div>

    <div class="lottery-history-table">
        <?php if ($past_lotteries->have_posts()): ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Lottery', 'wc-lottery'); ?></th>
                        <th><?php _e('End Date', 'wc-lottery'); ?></th>
                        <th><?php _e('Total Tickets', 'wc-lottery'); ?></th>
                        <th><?php _e('Tickets Sold', 'wc-lottery'); ?></th>
                        <th><?php _e('Status', 'wc-lottery'); ?></th>
                        <th><?php _e('Winner', 'wc-lottery'); ?></th>
                        <th><?php _e('Actions', 'wc-lottery'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($past_lotteries->have_posts()): $past_lotteries->the_post(); 
                        $product = wc_get_product(get_the_ID());
                        $winner = WC_Lottery::instance()->get_lottery_winners(get_the_ID());
                        $has_enough = $product->has_enough_participants();
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </td>
                            <td><?php echo $product->get_lottery_end_date(); ?></td>
                            <td><?php echo $product->get_max_tickets(); ?></td>
                            <td><?php echo $product->get_sold_tickets(); ?></td>
                            <td>
                                <?php if ($has_enough): ?>
                                    <span class="status-completed"><?php _e('Completed', 'wc-lottery'); ?></span>
                                <?php else: ?>
                                    <span class="status-failed"><?php _e('Failed', 'wc-lottery'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($winner) {
                                    echo esc_html($winner[0]->winner_name);
                                } else {
                                    echo $has_enough ? __('Pending Draw', 'wc-lottery') : '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($has_enough && !$winner): ?>
                                    <button class="button draw-winner" data-lottery-id="<?php echo get_the_ID(); ?>">
                                        <i class="fas fa-trophy"></i> <?php _e('Draw Winner', 'wc-lottery'); ?>
                                    </button>
                                <?php endif; ?>
                                <button class="button view-details" data-lottery-id="<?php echo get_the_ID(); ?>">
                                    <i class="fas fa-eye"></i> <?php _e('View Details', 'wc-lottery'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-history">
                <p><?php _e('No past lotteries found.', 'wc-lottery'); ?></p>
            </div>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
</div>

<!-- Lottery Details Modal -->
<div id="lottery-details-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2><?php _e('Lottery Details', 'wc-lottery'); ?></h2>
        <div class="lottery-details-content"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize datepicker
    $('#lottery-date-filter').datepicker({
        dateFormat: 'yy-mm-dd'
    });

    // Filter functionality
    function filterTable() {
        var status = $('#lottery-status-filter').val();
        var date = $('#lottery-date-filter').val();

        $('.lottery-history-table tbody tr').each(function() {
            var show = true;
            
            if (status && !$(this).find('td:eq(4)').text().toLowerCase().includes(status)) {
                show = false;
            }
            
            if (date && $(this).find('td:eq(1)').text() !== date) {
                show = false;
            }
            
            $(this).toggle(show);
        });
    }

    $('#lottery-status-filter, #lottery-date-filter').on('change', filterTable);

    // Draw winner functionality
    $('.draw-winner').on('click', function() {
        var lotteryId = $(this).data('lottery-id');
        var button = $(this);
        
        if (confirm('<?php _e('Are you sure you want to draw a winner?', 'wc-lottery'); ?>')) {
            button.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'draw_lottery_winner',
                    lottery_id: lotteryId,
                    nonce: '<?php echo wp_create_nonce('draw_lottery_winner'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        button.prop('disabled', false);
                        alert(response.data);
                    }
                },
                error: function() {
                    button.prop('disabled', false);
                    alert('<?php _e('Error drawing winner', 'wc-lottery'); ?>');
                }
            });
        }
    });

    // View details functionality
    $('.view-details').on('click', function() {
        var lotteryId = $(this).data('lottery-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_lottery_details',
                lottery_id: lotteryId,
                nonce: '<?php echo wp_create_nonce('get_lottery_details'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('.lottery-details-content').html(response.data);
                    $('#lottery-details-modal').show();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Error loading lottery details', 'wc-lottery'); ?>');
            }
        });
    });

    // Close modal
    $('.close').on('click', function() {
        $('#lottery-details-modal').hide();
    });

    $(window).on('click', function(event) {
        if ($(event.target).is('#lottery-details-modal')) {
            $('#lottery-details-modal').hide();
        }
    });
});
</script>

<style>
.lottery-history-filters {
    margin: 20px 0;
}

.lottery-history-filters select,
.lottery-history-filters input {
    margin-right: 10px;
}

.status-completed {
    color: #4CAF50;
}

.status-failed {
    color: #f44336;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    position: relative;
}

.close {
    position: absolute;
    right: 10px;
    top: 5px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.lottery-details-content {
    margin-top: 20px;
}
</style>