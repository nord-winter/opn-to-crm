jQuery(document).ready(function($) {
    // OPN instance
    let opn;
    let card;
    let selectedPackage = null;
    let processing = false;

    // Package configurations
    const packages = {
        1: { price: 1000, units: 10, discount: 0 },
        2: { price: 1000, units: 20, discount: 5 },
        3: { price: 1000, units: 30, discount: 10 },
        4: { price: 1000, units: 40, discount: 15 }
    };

    // Initialize OPN.js
    function initOpn() {
        opn = window.OPN(opnPublicKey);
        if (document.getElementById('sr-card-element')) {
            card = opn.elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#424770',
                        '::placeholder': {
                            color: '#aab7c4'
                        }
                    },
                    invalid: {
                        color: '#dc3545'
                    }
                }
            });
            card.mount('#sr-card-element');
            card.addEventListener('change', handleCardChange);
        }
    }

    // Handle package selection
    $('.sr-package').on('click', function() {
        $('.sr-package').removeClass('selected');
        $(this).addClass('selected');
        selectedPackage = $(this).data('package');
        $('#package_id').val(selectedPackage);
        updateOrderSummary();
        validateForm();
    });

    // Handle payment method selection
    $('input[name="payment_method"]').on('change', function() {
        const method = $(this).val();
        if (method === 'credit_card') {
            $('#sr-card-payment').show();
            $('#sr-promptpay-payment').hide();
        } else {
            $('#sr-card-payment').hide();
            $('#sr-promptpay-payment').show();
        }
        validateForm();
    });

    // Handle card input changes
    function handleCardChange(event) {
        const displayError = $('#sr-card-errors');
        if (event.error) {
            displayError.text(event.error.message);
        } else {
            displayError.text('');
        }
        validateForm();
    }

    // Update order summary
    function updateOrderSummary() {
        if (!selectedPackage || !packages[selectedPackage]) return;

        const pkg = packages[selectedPackage];
        const baseAmount = pkg.price * pkg.units;
        const discount = baseAmount * (pkg.discount / 100);
        const total = baseAmount - discount;

        $('#summary-package').text(`Package ${selectedPackage}x`);
        $('#summary-units').text(`${pkg.units} units`);
        $('#summary-discount').text(`${pkg.discount}%`);
        $('#summary-total').text(`฿${total.toLocaleString()}`);
    }

    // Validate form
    function validateForm() {
        const requiredFields = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'address',
            'city',
            'postcode'
        ];

        let valid = true;

        // Check required fields
        requiredFields.forEach(field => {
            if (!$(`#${field}`).val()) {
                valid = false;
            }
        });

        // Check package selection
        if (!selectedPackage) {
            valid = false;
        }

        // Validate specific fields
        const email = $('#email').val();
        const phone = $('#phone').val();
        const postcode = $('#postcode').val();

        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            valid = false;
        }

        if (phone && !/^(\+66|0)\d{9}$/.test(phone)) {
            valid = false;
        }

        if (postcode && !/^\d{5}$/.test(postcode)) {
            valid = false;
        }

        $('#sr-submit-btn').prop('disabled', !valid || processing);
        return valid;
    }

    // Handle form submission
    $('#sr-checkout-form').on('submit', async function(e) {
        e.preventDefault();
        if (!validateForm() || processing) return;

        processing = true;
        $('#sr-submit-btn').prop('disabled', true);
        $('#sr-loading').show();

        try {
            const formData = new FormData(this);
            const paymentMethod = $('input[name="payment_method"]:checked').val();

            if (paymentMethod === 'credit_card') {
                const {token} = await opn.createToken(card);
                formData.append('token', token);
            }

            const response = await $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false
            });

            if (response.success) {
                if (paymentMethod === 'credit_card') {
                    if (response.data.authorize_uri) {
                        window.location.href = response.data.authorize_uri;
                    } else {
                        handlePaymentSuccess(response.data);
                    }
                } else {
                    // Show PromptPay QR
                    $('#sr-promptpay-qr').html(`
                        <img src="${response.data.source.qr_code}" alt="PromptPay QR Code">
                        <p>Scan to pay ฿${(response.data.amount / 100).toFixed(2)}</p>
                    `);
                    startPollingPaymentStatus(response.data.id);
                }
            } else {
                throw new Error(response.data.message || 'Payment failed');
            }
        } catch (error) {
            handlePaymentError(error);
        }
    });

    // Poll payment status for PromptPay
    async function startPollingPaymentStatus(chargeId) {
        const pollInterval = setInterval(async () => {
            try {
                const response = await $.get(ajaxurl, {
                    action: 'sr_check_payment',
                    charge_id: chargeId
                });

                if (response.success) {
                    if (response.data.status === 'successful') {
                        clearInterval(pollInterval);
                        handlePaymentSuccess(response.data);
                    } else if (response.data.status === 'failed') {
                        clearInterval(pollInterval);
                        handlePaymentError(new Error('Payment failed'));
                    }
                }
            } catch (error) {
                console.error('Error checking payment status:', error);
            }
        }, 3000); // Poll every 3 seconds

        // Stop polling after 5 minutes
        setTimeout(() => {
            clearInterval(pollInterval);
        }, 300000);
    }

    // Handle successful payment
    function handlePaymentSuccess(data) {
        $('#sr-loading').hide();
        $('.sr-payment-form').hide();
        $('.sr-status-success').show();
        $('#sr-payment-status').show();
        
        // Redirect if URL provided
        if (data.redirect_url) {
            setTimeout(() => {
                window.location.href = data.redirect_url;
            }, 2000);
        }
    }

    // Handle payment error
    function handlePaymentError(error) {
        processing = false;
        $('#sr-loading').hide();
        $('#sr-submit-btn').prop('disabled', false);
        $('#sr-card-errors').text(error.message);
    }

    // Phone number formatting
    $('#phone').on('input', function() {
        let phone = $(this).val().replace(/\D/g, '');
        if (phone.startsWith('66')) {
            phone = '+' + phone;
        } else if (phone.startsWith('0')) {
            phone = '+66' + phone.substring(1);
        }
        $(this).val(phone);
    });

    // Postal code formatting
    $('#postcode').on('input', function() {
        $(this).val($(this).val().replace(/\D/g, '').substr(0, 5));
    });

    // Initialize form validation
    $('input, textarea').on('input', validateForm);

    // Initialize OPN.js when document is ready
    initOpn();
});