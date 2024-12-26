<?php
if (!defined('ABSPATH')) {
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$test_mode = get_option('opn_test_mode', true);

if (!$order_id) {
    wp_redirect(home_url());
    exit;
}

// Get order data from SalesRender
$sr_api = new SR_API();
$order = $sr_api->get_order($order_id);

if (is_wp_error($order)) {
    wp_die(__('Order not found', 'opn-to-crm'));
}
?>

<div class="sr-payment-container">
    <?php if ($test_mode): ?>
    <div class="sr-test-mode-notice">
        <p><?php _e('Test mode is enabled. Use test card: 4242 4242 4242 4242', 'opn-to-crm'); ?></p>
    </div>
    <?php endif; ?>

    <!-- Order Details -->
    <div class="sr-payment-section">
        <h2><?php _e('Order Details', 'opn-to-crm'); ?></h2>
        <div class="sr-order-details">
            <div class="sr-detail-row">
                <span><?php _e('Order ID:', 'opn-to-crm'); ?></span>
                <span>#<?php echo esc_html($order_id); ?></span>
            </div>
            <div class="sr-detail-row">
                <span><?php _e('Amount:', 'opn-to-crm'); ?></span>
                <span><?php echo esc_html($order['amount']); ?> THB</span>
            </div>
        </div>
    </div>

    <!-- Payment Form -->
    <div class="sr-payment-section">
        <h2><?php _e('Payment Method', 'opn-to-crm'); ?></h2>
        
        <!-- Payment Method Selection -->
        <div class="sr-payment-methods">
            <div class="sr-method-option">
                <input type="radio" id="card_payment" name="payment_method" value="card" checked>
                <label for="card_payment">
                    <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/credit-card.png" alt="Credit Card">
                    <span><?php _e('Credit / Debit Card', 'opn-to-crm'); ?></span>
                </label>
            </div>
            
            <div class="sr-method-option">
                <input type="radio" id="promptpay_payment" name="payment_method" value="promptpay">
                <label for="promptpay_payment">
                    <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/promptpay.png" alt="PromptPay">
                    <span><?php _e('PromptPay QR', 'opn-to-crm'); ?></span>
                </label>
            </div>
        </div>

        <!-- Credit Card Form -->
        <div id="sr-card-form" class="sr-payment-form">
            <div id="card-element">
                <!-- OPN Payments Secure Card Element will be inserted here -->
            </div>
            <div id="card-errors" class="sr-error"></div>
        </div>

        <!-- PromptPay QR -->
        <div id="sr-promptpay-form" class="sr-payment-form" style="display: none;">
            <div id="promptpay-qr" class="sr-qr-container">
                <!-- QR Code will be inserted here -->
            </div>
            <div class="sr-qr-instructions">
                <p><?php _e('1. Open your banking app', 'opn-to-crm'); ?></p>
                <p><?php _e('2. Scan this QR code', 'opn-to-crm'); ?></p>
                <p><?php _e('3. Confirm the payment in your app', 'opn-to-crm'); ?></p>
            </div>
        </div>

        <!-- Payment Button -->
        <div class="sr-payment-action">
            <button id="sr-pay-button" class="sr-button">
                <?php echo sprintf(__('Pay %s THB', 'opn-to-crm'), esc_html($order['amount'])); ?>
            </button>
            <div id="sr-payment-processing" class="sr-processing" style="display: none;">
                <div class="sr-spinner"></div>
                <span><?php _e('Processing payment...', 'opn-to-crm'); ?></span>
            </div>
        </div>
    </div>

    <!-- Payment Status -->
    <div id="sr-payment-status" class="sr-payment-section" style="display: none;">
        <div class="sr-status-success" style="display: none;">
            <h2><?php _e('Payment Successful', 'opn-to-crm'); ?></h2>
            <p><?php _e('Your payment has been processed successfully.', 'opn-to-crm'); ?></p>
            <p><?php _e('You will receive a confirmation email shortly.', 'opn-to-crm'); ?></p>
            <a href="<?php echo home_url(); ?>" class="sr-button"><?php _e('Return to Home', 'opn-to-crm'); ?></a>
        </div>
        <div class="sr-status-error" style="display: none;">
            <h2><?php _e('Payment Failed', 'opn-to-crm'); ?></h2>
            <p class="sr-error-message"></p>
            <button class="sr-button" onclick="window.location.reload();">
                <?php _e('Try Again', 'opn-to-crm'); ?>
            </button>
        </div>
    </div>
</div>


