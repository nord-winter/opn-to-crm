<?php
if (!defined('ABSPATH')) {
    exit;
}

$test_mode = get_option('opn_test_mode', true);
?>

<div class="sr-checkout-container">
    <?php if ($test_mode): ?>
    <div class="sr-test-mode-notice">
        <p><?php _e('Test mode is enabled. Use test card: 4242 4242 4242 4242', 'opn-to-crm'); ?></p>
    </div>
    <?php endif; ?>

    <form id="sr-checkout-form" class="sr-checkout-form">
        <!-- Package Selection -->
        <div class="sr-form-section">
            <h3><?php _e('Select Package', 'opn-to-crm'); ?></h3>
            <div class="sr-packages">
                <div class="sr-package-grid">
                    <div class="sr-package" data-package="1">
                        <div class="sr-package-header">
                            <h4><?php _e('Package 1x', 'opn-to-crm'); ?></h4>
                            <span class="sr-package-price">฿1,000</span>
                        </div>
                        <div class="sr-package-body">
                            <p class="sr-package-description"><?php _e('10 Units', 'opn-to-crm'); ?></p>
                            <p class="sr-package-discount"><?php _e('No Discount', 'opn-to-crm'); ?></p>
                        </div>
                    </div>

                    <div class="sr-package" data-package="2">
                        <div class="sr-package-header">
                            <h4><?php _e('Package 2x', 'opn-to-crm'); ?></h4>
                            <span class="sr-package-price">฿1,900</span>
                        </div>
                        <div class="sr-package-body">
                            <p class="sr-package-description"><?php _e('20 Units', 'opn-to-crm'); ?></p>
                            <p class="sr-package-discount"><?php _e('5% Discount', 'opn-to-crm'); ?></p>
                        </div>
                    </div>

                    <div class="sr-package" data-package="3">
                        <div class="sr-package-header">
                            <h4><?php _e('Package 3x', 'opn-to-crm'); ?></h4>
                            <span class="sr-package-price">฿2,700</span>
                        </div>
                        <div class="sr-package-body">
                            <p class="sr-package-description"><?php _e('30 Units', 'opn-to-crm'); ?></p>
                            <p class="sr-package-discount"><?php _e('10% Discount', 'opn-to-crm'); ?></p>
                        </div>
                    </div>

                    <div class="sr-package" data-package="4">
                        <div class="sr-package-header">
                            <h4><?php _e('Package 4x', 'opn-to-crm'); ?></h4>
                            <span class="sr-package-price">฿3,400</span>
                        </div>
                        <div class="sr-package-body">
                            <p class="sr-package-description"><?php _e('40 Units', 'opn-to-crm'); ?></p>
                            <p class="sr-package-discount"><?php _e('15% Discount', 'opn-to-crm'); ?></p>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="package_id" id="package_id" value="">
            </div>
        </div>

        <!-- Personal Information -->
        <div class="sr-form-section">
            <h3><?php _e('Personal Information', 'opn-to-crm'); ?></h3>
            <div class="sr-form-row">
                <div class="sr-form-col">
                    <label for="first_name"><?php _e('First Name', 'opn-to-crm'); ?> *</label>
                    <input type="text" name="first_name" id="first_name" required>
                </div>
                <div class="sr-form-col">
                    <label for="last_name"><?php _e('Last Name', 'opn-to-crm'); ?> *</label>
                    <input type="text" name="last_name" id="last_name" required>
                </div>
            </div>
            <div class="sr-form-row">
                <div class="sr-form-col">
                    <label for="email"><?php _e('Email', 'opn-to-crm'); ?> *</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <div class="sr-form-col">
                    <label for="phone"><?php _e('Phone', 'opn-to-crm'); ?> *</label>
                    <div class="sr-phone-input">
                        <span class="sr-phone-prefix">+66</span>
                        <input type="tel" name="phone" id="phone" placeholder="9XXXXXXXX" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Information -->
        <div class="sr-form-section">
            <h3><?php _e('Shipping Information', 'opn-to-crm'); ?></h3>
            <div class="sr-form-row">
                <div class="sr-form-col">
                    <label for="address"><?php _e('Address', 'opn-to-crm'); ?> *</label>
                    <textarea name="address" id="address" required></textarea>
                </div>
            </div>
            <div class="sr-form-row">
                <div class="sr-form-col">
                    <label for="city"><?php _e('City', 'opn-to-crm'); ?> *</label>
                    <input type="text" name="city" id="city" required>
                </div>
                <div class="sr-form-col">
                    <label for="postcode"><?php _e('Postal Code', 'opn-to-crm'); ?> *</label>
                    <input type="text" name="postcode" id="postcode" maxlength="5" required>
                </div>
            </div>
            <div class="sr-form-row">
                <div class="sr-form-col">
                    <label for="region"><?php _e('Region', 'opn-to-crm'); ?></label>
                    <input type="text" name="region" id="region">
                </div>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="sr-form-section">
            <h3><?php _e('Payment Method', 'opn-to-crm'); ?></h3>
            <div class="sr-payment-methods">
                <div class="sr-payment-method">
                    <input type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                    <label for="credit_card">
                        <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/credit-card.png" alt="Credit Card">
                        <?php _e('Credit/Debit Card', 'opn-to-crm'); ?>
                    </label>
                </div>
                <div class="sr-payment-method">
                    <input type="radio" name="payment_method" id="promptpay" value="promptpay">
                    <label for="promptpay">
                        <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/promptpay.png" alt="PromptPay">
                        <?php _e('PromptPay QR', 'opn-to-crm'); ?>
                    </label>
                </div>
            </div>

            <!-- Card Payment Form -->
            <div id="sr-card-payment" class="sr-payment-form">
                <div id="sr-card-element"></div>
                <div id="sr-card-errors" class="sr-error"></div>
            </div>

            <!-- PromptPay QR -->
            <div id="sr-promptpay-payment" class="sr-payment-form" style="display: none;">
                <div id="sr-promptpay-qr"></div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="sr-order-summary">
            <h3><?php _e('Order Summary', 'opn-to-crm'); ?></h3>
            <div class="sr-summary-row">
                <span><?php _e('Package', 'opn-to-crm'); ?></span>
                <span id="summary-package">-</span>
            </div>
            <div class="sr-summary-row">
                <span><?php _e('Units', 'opn-to-crm'); ?></span>
                <span id="summary-units">-</span>
            </div>
            <div class="sr-summary-row">
                <span><?php _e('Discount', 'opn-to-crm'); ?></span>
                <span id="summary-discount">-</span>
            </div>
            <div class="sr-summary-row sr-total">
                <span><?php _e('Total', 'opn-to-crm'); ?></span>
                <span id="summary-total">฿0</span>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="sr-form-submit">
            <button type="submit" id="sr-submit-btn" class="sr-submit-button" disabled>
                <?php _e('Complete Order', 'opn-to-crm'); ?>
            </button>
            <div id="sr-loading" class="sr-loading" style="display: none;">
                <div class="sr-spinner"></div>
                <span><?php _e('Processing...', 'opn-to-crm'); ?></span>
            </div>
        </div>

        <?php wp_nonce_field('sr_checkout_nonce'); ?>
    </form>
</div>

<style>
.sr-checkout-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.sr-test-mode-notice {
    background-color: #fff3cd;
    border: 1px solid #ffeeba;
    color: #856404;
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.sr-form-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.sr-package-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.sr-package {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.sr-package.selected {
    border-color: #2271b1;
    background-color: #f0f7ff;
}

.sr-package-header {
    text-align: center;
    margin-bottom: 15px;
}

.sr-package-price {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}

.sr-form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.sr-form-col {
    flex: 1;
}

.sr-phone-input {
    display: flex;
    align-items: center;
}

.sr-phone-prefix {
    background: #f0f0f0;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-right: none;
    border-radius: 4px 0 0 4px;
}

input[type="tel"] {
    border-radius: 0 4px 4px 0;
}

.sr-payment-methods {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.sr-payment-method {
    flex: 1;
    text-align: center;
}

.sr-payment-method img {
    max-width: 60px;
    height: auto;
    margin-bottom: 10px;
}

.sr-order-summary {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

.sr-summary-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
}

.sr-total {
    font-weight: bold;
    font-size: 18px;
    border-bottom: none;
}

.sr-submit-button {
    width: 100%;
    padding: 15px;
    background: #2271b1;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.sr-submit-button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.sr-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.sr-spinner {
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #2271b1;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .sr-form-row {
        flex-direction: column;
        gap: 10px;
    }
    
    .sr-payment-methods {
        flex-direction: column;
    }
}
</style>