jQuery(document).ready(function($) {
    // Lucky Number Generator
    $('.generate-lucky-numbers').on('click', function(e) {
        e.preventDefault();
        
        const productId = $(this).data('product-id');
        const count = $('.lucky-number-count').val();
        const button = $(this);
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');
        
        $.ajax({
            url: wcLotteryParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_lucky_numbers',
                product_id: productId,
                count: count,
                nonce: wcLotteryParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Clear previous selections
                    $('input[name="lottery_number"]').prop('checked', false);
                    
                    // Select the generated numbers
                    response.data.numbers.forEach(function(number) {
                        $(`input[name="lottery_number"][value="${number}"]`).prop('checked', true);
                    });
                    
                    // Update selected numbers display
                    updateSelectedNumbers();
                    
                    // Scroll to the first selected number
                    const firstSelected = $('.number-option input:checked').first().closest('.number-option');
                    if (firstSelected.length) {
                        $('.lottery-number-grid').animate({
                            scrollTop: firstSelected.position().top
                        }, 500);
                    }
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Error generating lucky numbers. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="fas fa-dice"></i> Feel Lucky? Generate Random Numbers!');
            }
        });
    });

    // Handle number selection
    $('.number-option input[type="radio"]').on('change', function() {
        updateSelectedNumbers();
    });

    // Update selected numbers display
    function updateSelectedNumbers() {
        const selectedNumbers = [];
        $('.number-option input:checked').each(function() {
            selectedNumbers.push($(this).val());
        });

        const display = $('.selected-numbers-display');
        display.empty();

        if (selectedNumbers.length > 0) {
            selectedNumbers.forEach(function(number) {
                display.append(`<span class="number">${number}</span>`);
            });
        } else {
            display.append('<p>No numbers selected</p>');
        }
    }

    // Initialize countdown timer for active lotteries
    function initCountdown() {
        const endDate = $('.lottery-end-date').data('end');
        if (!endDate) return;

        const countdown = setInterval(function() {
            const now = new Date().getTime();
            const end = new Date(endDate).getTime();
            const distance = end - now;

            if (distance < 0) {
                clearInterval(countdown);
                $('.lottery-countdown').html('Lottery has ended');
                location.reload();
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            $('.lottery-countdown').html(`
                <span class="countdown-section">
                    <span class="countdown-amount">${days}</span>
                    <span class="countdown-period">Days</span>
                </span>
                <span class="countdown-section">
                    <span class="countdown-amount">${hours}</span>
                    <span class="countdown-period">Hours</span>
                </span>
                <span class="countdown-section">
                    <span class="countdown-amount">${minutes}</span>
                    <span class="countdown-period">Minutes</span>
                </span>
                <span class="countdown-section">
                    <span class="countdown-amount">${seconds}</span>
                    <span class="countdown-period">Seconds</span>
                </span>
            `);
        }, 1000);
    }

    // Initialize lottery features
    function init() {
        updateSelectedNumbers();
        initCountdown();
    }

    init();
});