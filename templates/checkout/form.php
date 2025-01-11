<?php

$package_manager = new SR_Package_Admin();
$packages = $package_manager->get_packages();

$opn_api = new OPN_API();
$public_key = $opn_api->get_public_key();
?>

<script>
    const opnPublicKey = '<?php echo esc_js($public_key); ?>';
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
                        <div class="sr-package" data-package-id="<?php echo esc_attr($package['id']); ?>"
                            data-units="<?php echo esc_attr($package['units']); ?>"
                            data-discount="<?php echo esc_attr($package['discount']); ?>"
                            data-price="<?php echo esc_attr($package['price']); ?>">
                            <?php if (!empty($package['image'])): ?>
                                <div class="sr-package-image">
                                    <img src="<?php echo esc_url($package['image']); ?>"
                                        alt="<?php echo esc_attr($package['name']); ?>">
                                </div>
                            <?php endif; ?>

                            <div class="sr-package-content">
                                <div class="sr-package-info">
                                    <div class="sr-package-name">
                                        <?php echo sprintf(__('%s', 'opn-to-crm'), $package['name']); ?>
                                    </div>
                                    <div class="sr-package-units"><b><?php echo $package['units']; ?></b> box<?php echo $package['units'] > 1  ? 's' : ''?></div>
                                </div>
                                <div class="sr-package-price-container">
                                    <div class="sr-package-price">฿<?php echo number_format($package['price']); ?></div>
                                    <?php if ($package['discount'] > 0): ?>
                                        <div class="sr-package-original-price">
                                            ฿<?php echo number_format($package['price'] / (1 - $package['discount'] / 100)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($package['discount'] > 0): ?>
                                        <div class="sr-package-discount">Save <?php echo $package['discount']; ?>%</div>
                                        <div class="sr-package-delivery">
                                            <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/delivery.png" alt="Free delivery">
                                        </div>
                                    <?php endif; ?>
                                </div>
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
                        <label for="first_name"><?php _e('First Name - ชื่อ', 'opn-to-crm'); ?> *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="sr-form-field">
                        <label for="last_name"><?php _e('Last Name - นามสกุล', 'opn-to-crm'); ?> *</label>
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

            <!-- Shipping Information -->
            <div class="sr-section">
                <h2 class="sr-section-title"><?php _e('Shipping Information', 'opn-to-crm'); ?></h2>
                <div class="sr-form-stack">
                    <div class="sr-form-row">
                        <div class="sr-form-field">
                            <label for="city"><?php _e('City - เมือง', 'opn-to-crm'); ?> *</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        <div class="sr-form-field">
                            <label for="postal_code"><?php _e('Postal Code - รหัสไปรษณีย์', 'opn-to-crm'); ?> *</label>
                            <input type="text" id="postal_code" name="postal_code" maxlength="5" required>
                        </div>
                    </div>
                    <div class="sr-form-field">
                        <label for="address"><?php _e('Address - ที่อยู่', 'opn-to-crm'); ?> *</label>
                        <textarea id="address" name="address" required></textarea>
                    </div>
                    <div class="sr-form-field" style="display: none;">
                        <label for="country"><?php _e('Country', 'opn-to-crm'); ?></label>
                        <select id="country" name="country" disabled>
                            <option value="TH" selected>Thailand</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="sr-checkout-right">
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


                <div id="promptpay-form" class="sr-payment-form sr-promptpay-form" style="display: none;">
                    <div id="promptpay-qr" class="sr-qr-container"></div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="sr-section sr-order-summary">
                <h2 class="sr-section-title"><?php _e('Order Summary', 'opn-to-crm'); ?></h2>
                <div class="sr-summary-line">
                    <span><?php _e('Package(พัสดุ)', 'opn-to-crm'); ?></span>
                    <span id="summary-package">-</span>
                </div>
                <div class="sr-summary-line">
                    <span><?php _e('Count(จำนวน)', 'opn-to-crm'); ?></span>
                    <span id="summary-units">-</span>
                </div>
                <div class="sr-summary-line">
                    <span><?php _e('Discount(ส่วนลด)', 'opn-to-crm'); ?></span>
                    <span id="summary-discount">-</span>
                </div>
                <div class="sr-summary-line sr-total">
                    <span><?php _e('Total(ยอดรวม)', 'opn-to-crm'); ?></span>
                    <span id="summary-total">฿0</span>
                </div>
                <div class="sr-secured-by">
                    <div class="sr-secured-head">
                        <span class="sr-secured-text">Secured by</span>
                        <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/opnp.png" alt="OPN Payments"
                            class="sr-opn-logo">
                    </div>
                    <div class="sr-secure-methods">
                        <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/visa-secure.png" alt="Visa">
                        <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/upay.png" alt="UnionPay">
                        <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/mastercard-check.png" alt="Mastercard">
                        <img src="<?php echo OPN_TO_CRM_PLUGIN_URL; ?>assets/images/jcb-sec.png" alt="JCB">
                    </div>
                </div>
                <button type="button" class="sr-submit-button" id="sr-submit">
                    <span class="sr-button-text"><?php _e('Complete Order', 'opn-to-crm'); ?></span>
                    <span class="sr-button-loading" style="display: none;">
                        <span class="sr-spinner"></span>
                        <?php _e('Processing...', 'opn-to-crm'); ?>
                    </span>
                </button>
                <div id="payment-status" class="sr-payment-status" style="display: none;"></div>
                <!-- SSL Notice -->
                <!-- SSL Notice -->
                <div class="sr-ssl-notice">
                    <div class="sr-ssl-content">
                        <svg class="sr-ssl-icon" width="28" height="38" viewBox="0 0 28 38"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill="#008905"
                                d="M24.9 14.432v-5.21C24.9 4.136 20.87 0 15.917 0H11.98C7.026 0 2.996 4.137 2.996 9.221v5.23C1.296 14.763 0 16.233 0 17.986v16.417C0 36.38 1.65 38 3.668 38h20.664C26.35 38 28 36.381 28 34.403V17.986c0-1.789-1.35-3.283-3.1-3.554Zm-9.195 12.273v4.1c0 .669-.558 1.215-1.24 1.215h-.308c-.682 0-1.24-.547-1.24-1.216v-4.037c-1.39-.52-2.376-1.84-2.376-3.384 0-2.001 1.653-3.623 3.691-3.623 2.039 0 3.692 1.622 3.692 3.622.002 1.487-.912 2.765-2.22 3.323Zm6.193-12.316h-15.9V9.221c0-3.408 2.685-6.18 5.981-6.18h3.938c3.298 0 5.981 2.772 5.981 6.18v5.168Z">
                            </path>
                        </svg>
                        <div class="sr-ssl-text">
                            <p class="sr-ssl-title">SSL</p>
                            <p class="sr-ssl-desc">
                                <?php _e('การชำระเงินที่ปลอดภัยด้วยการเข้ารหัส SSL แบบ 256 บิต', 'opn-to-crm'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>