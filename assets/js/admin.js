jQuery(document).ready(function($) {
    // Product type specific options
    $('select#product-type').on('change', function() {
        if ($(this).val() === 'lottery') {
            $('.show_if_lottery').show();
            $('.hide_if_lottery').hide();
        } else {
            $('.show_if_lottery').hide();
            $('.hide_if_lottery').show();
        }
    }).trigger('change');

    // Initialize datetime pickers
    $('#_lottery_start_date, #_lottery_end_date').each(function() {
        $(this).datetimepicker({
            dateFormat: 'yy-mm-dd',
            timeFormat: 'HH:mm:ss',
            minDate: 0
        });
    });

    // Quick select/deselect all numbers
    $('.lottery-numbers-field').prepend(`
        <div class="lottery-numbers-actions">
            <button type="button" class="button select-all-numbers">Select All</button>
            <button type="button" class="button clear-all-numbers">Clear All</button>
        </div>
    `);

    $('.select-all-numbers').on('click', function() {
        $('.lottery-numbers-grid input[type="checkbox"]').prop('checked', true);
    });

    $('.clear-all-numbers').on('click', function() {
        $('.lottery-numbers-grid input[type="checkbox"]').prop('checked', false);
    });

    // Draw winner functionality
    $('.draw-winner').on('click', function(e) {
        e.preventDefault();
        
        const lotteryId = $(this).data('lottery-id');
        const button = $(this);
        
        if (!confirm('Are you sure you want to draw a winner? This action cannot be undone.')) {
            return;
        }
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Drawing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'draw_lottery_winner',
                lottery_id: lotteryId,
                nonce: $('#lottery_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Error drawing winner. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="fas fa-trophy"></i> Draw Winner');
            }
        });
    });

    // Notify winner functionality
    $('.notify-winner').on('click', function(e) {
        e.preventDefault();
        
        const winnerId = $(this).data('winner-id');
        const button = $(this);
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'notify_lottery_winner',
                winner_id: winnerId,
                nonce: $('#lottery_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    button.html('<i class="fas fa-check"></i> Notified').addClass('disabled');
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Error notifying winner. Please try again.');
            },
            complete: function() {
                if (!button.hasClass('disabled')) {
                    button.prop('disabled', false).html('<i class="fas fa-envelope"></i> Notify Winner');
                }
            }
        });
    });

    // View lottery details modal
    $('.view-details').on('click', function(e) {
        e.preventDefault();
        
        const lotteryId = $(this).data('lottery-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_lottery_details',
                lottery_id: lotteryId,
                nonce: $('#lottery_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#lottery-details-content').html(response.data);
                    $('#lottery-details-modal').fadeIn();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Error loading lottery details. Please try again.');
            }
        });
    });

    // Close modal
    $('.modal-close, .modal-backdrop').on('click', function() {
        $('#lottery-details-modal').fadeOut();
    });

    // Prevent modal close when clicking inside
    $('.modal-content').on('click', function(e) {
        e.stopPropagation();
    });

    // History page filters
    $('#lottery-status-filter, #lottery-date-filter').on('change', function() {
        const status = $('#lottery-status-filter').val();
        const date = $('#lottery-date-filter').val();
        
        $('.lottery-history-table tbody tr').each(function() {
            let show = true;
            
            if (status && !$(this).find('.lottery-status').text().toLowerCase().includes(status)) {
                show = false;
            }
            
            if (date && $(this).find('.lottery-date').text() !== date) {
                show = false;
            }
            
            $(this).toggle(show);
        });
    });

    // Validate lottery settings
    $('#publish').on('click', function(e) {
        if ($('#product-type').val() === 'lottery') {
            const startDate = $('#_lottery_start_date').val();
            const endDate = $('#_lottery_end_date').val();
            const minTickets = $('#_min_tickets').val();
            const maxTickets = $('#_max_tickets').val();
            
            if (!startDate || !endDate) {
                alert('Please set both start and end dates for the lottery.');
                e.preventDefault();
                return;
            }
            
            if (new Date(startDate) >= new Date(endDate)) {
                alert('End date must be after start date.');
                e.preventDefault();
                return;
            }
            
            if (!minTickets || !maxTickets) {
                alert('Please set minimum and maximum tickets for the lottery.');
                e.preventDefault();
                return;
            }
            
            if (parseInt(minTickets) > parseInt(maxTickets)) {
                alert('Minimum tickets cannot be greater than maximum tickets.');
                e.preventDefault();
                return;
            }
            
            const selectedNumbers = $('.lottery-numbers-grid input:checked').length;
            if (selectedNumbers === 0) {
                alert('Please select at least one lottery number.');
                e.preventDefault();
                return;
            }
            
            if (selectedNumbers < parseInt(maxTickets)) {
                alert('The number of available lottery numbers must be at least equal to the maximum tickets.');
                e.preventDefault();
                return;
            }
        }
    });
});