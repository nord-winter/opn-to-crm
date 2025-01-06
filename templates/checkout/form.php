<?php

// В form.php заменить хардкод на:
$package_manager = new SR_Package_Admin();
$packages = $package_manager->get_packages();

// $packages = array(
//     array('id' => 4, 'name' => '4x', 'units' => 40, 'discount' => 15, 'price' => 3400),
//     array('id' => 3, 'name' => '3x', 'units' => 30, 'discount' => 10, 'price' => 2700),
//     array('id' => 2, 'name' => '2x', 'units' => 20, 'discount' => 5, 'price' => 1900),
//     array('id' => 1, 'name' => '1x', 'units' => 10, 'discount' => 0, 'price' => 1000),
// );
$opn_api = new OPN_API();
$public_key = $opn_api->get_public_key();
?>

<script>
    const opnPublicKey = '<?php echo esc_js($public_key); ?>';
    // Добавляем данные о пакетах из PHP в JavaScript
    const packagesData = <?php echo json_encode($packages); ?>;
</script>

<div class="sr-checkout-container">
    <!-- Test Mode Notice -->
    <?php if (get_option('opn_test_mode')): ?>
        <div class="sr-test-mode-notice">
            <p><?php _e('Test mode is enabled. Use test card: 4242 4242 4242 4242', 'opn-to-crm'); ?></p>
        </div>
    <?php endif; ?>

    <div class="sr-checkout-grid">
        <!-- Left Column -->
        <div class="sr-checkout-left">
            <!-- Package Selection -->
            <div class="sr-section">
                <h2 class="sr-section-title"><?php _e('Select Package', 'opn-to-crm'); ?></h2>
                <div class="sr-package-grid">
                    <?php
                    foreach ($packages as $package): ?>
                        <div class="sr-package" data-package-id="<?php echo esc_attr($package['id']); ?>">
                            <h3><?php echo sprintf(__('%s', 'opn-to-crm'), $package['name']); ?></h3>
                            <div class="sr-package-details">
                                <span class="sr-package-price">฿<?php echo number_format($package['price']); ?></span>
                                <span class="sr-package-units"><?php echo $package['units']; ?> units</span>
                                <?php if ($package['discount'] > 0): ?>
                                    <span class="sr-package-discount">Save <?php echo $package['discount']; ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="sr-section">
                <h2 class="sr-section-title"><?php _e('Personal Information', 'opn-to-crm'); ?></h2>
                <div class="sr-form-grid">
                    <div class="sr-form-field">
                        <label for="first_name"><?php _e('First Name', 'opn-to-crm'); ?> *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="sr-form-field">
                        <label for="last_name"><?php _e('Last Name', 'opn-to-crm'); ?> *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    <div class="sr-form-field">
                        <label for="email"><?php _e('Email', 'opn-to-crm'); ?> *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="sr-form-field">
                        <label for="phone"><?php _e('Phone', 'opn-to-crm'); ?> *</label>
                        <div class="sr-phone-input">
                            <span class="sr-phone-prefix">+66</span>
                            <input type="tel" id="phone" name="phone" placeholder="9XXXXXXXX" pattern="[0-9]{9}"
                                maxlength="9" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="sr-checkout-right">
            <!-- Shipping Information -->
            <div class="sr-section">
                <h2 class="sr-section-title"><?php _e('Shipping Information', 'opn-to-crm'); ?></h2>
                <div class="sr-form-stack">
                    <div class="sr-form-field">
                        <label for="country"><?php _e('Country', 'opn-to-crm'); ?></label>
                        <select id="country" name="country" disabled>
                            <option value="TH" selected>Thailand</option>
                        </select>
                    </div>
                    <div class="sr-form-field">
                        <label for="address"><?php _e('Address', 'opn-to-crm'); ?> *</label>
                        <textarea id="address" name="address" required></textarea>
                    </div>
                    <div class="sr-form-field">
                        <label for="city"><?php _e('City', 'opn-to-crm'); ?> *</label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    <div class="sr-form-field">
                        <label for="postal_code"><?php _e('Postal Code', 'opn-to-crm'); ?> *</label>
                        <input type="text" id="postal_code" name="postal_code" maxlength="5" required>
                    </div>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="sr-section">
                <h2 class="sr-section-title"><?php _e('Payment Method', 'opn-to-crm'); ?></h2>
                <div class="sr-payment-methods">
                    <div class="sr-payment-method">
                        <input type="radio" id="card_payment" name="payment_method" value="card" checked>
                        <label for="card_payment">
                            <span class="sr-payment-icons">
                                <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/visa.png" alt="Visa">
                                <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/mastercard.png"
                                    alt="Mastercard">
                            </span>
                            <?php _e('Credit / Debit Card', 'opn-to-crm'); ?>
                        </label>
                    </div>
                    <div class="sr-payment-method">
                        <input type="radio" id="promptpay" name="payment_method" value="promptpay">
                        <label for="promptpay">
                            <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/promptpay.png" alt="PromptPay">
                            <?php _e('PromptPay', 'opn-to-crm'); ?>
                        </label>
                    </div>
                </div>

                <!-- Контейнер для QR-кода PromptPay -->
                <div id="promptpay-form" class="sr-payment-form sr-promptpay-form" style="display: none;">
                    <div id="promptpay-qr" class="sr-qr-container"></div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="sr-section sr-order-summary">
                <h2 class="sr-section-title"><?php _e('Order Summary', 'opn-to-crm'); ?></h2>
                <div class="sr-summary-line">
                    <span><?php _e('Package', 'opn-to-crm'); ?></span>
                    <span id="summary-package">-</span>
                </div>
                <div class="sr-summary-line">
                    <span><?php _e('Units', 'opn-to-crm'); ?></span>
                    <span id="summary-units">-</span>
                </div>
                <div class="sr-summary-line">
                    <span><?php _e('Discount', 'opn-to-crm'); ?></span>
                    <span id="summary-discount">-</span>
                </div>
                <div class="sr-summary-line sr-total">
                    <span><?php _e('Total', 'opn-to-crm'); ?></span>
                    <span id="summary-total">฿0</span>
                </div>
                <button type="button" class="sr-submit-button" id="sr-submit">
                    <span class="sr-button-text"><?php _e('Complete Order', 'opn-to-crm'); ?></span>
                    <span class="sr-button-loading" style="display: none;">
                        <span class="sr-spinner"></span>
                        <?php _e('Processing...', 'opn-to-crm'); ?>
                    </span>
                </button>
                <div id="payment-status" class="sr-payment-status" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>