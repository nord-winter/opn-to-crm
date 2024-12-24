<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get current mode
$test_mode = get_option('opn_test_mode', true);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form action="options.php" method="post">
        <?php
        settings_fields('opn_to_crm_settings');
        do_settings_sections('opn_to_crm_settings');
        ?>

        <!-- SalesRender API Settings Section -->
        <div class="sr-api-settings">
            <h2><?php _e('SalesRender API Configuration', 'opn-to-crm'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sr_company_id"><?php _e('Company ID', 'opn-to-crm'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="sr_company_id" name="sr_company_id" 
                               value="<?php echo esc_attr(get_option('sr_company_id')); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php _e('Enter your SalesRender company ID', 'opn-to-crm'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sr_api_token"><?php _e('API Token', 'opn-to-crm'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="sr_api_token" name="sr_api_token" 
                               value="<?php echo esc_attr(get_option('sr_api_token')); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php _e('Enter your SalesRender API token', 'opn-to-crm'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sr_project_id"><?php _e('Project ID', 'opn-to-crm'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="sr_project_id" name="sr_project_id" 
                               value="<?php echo esc_attr(get_option('sr_project_id')); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php _e('Enter your SalesRender project ID', 'opn-to-crm'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sr_default_status_id"><?php _e('Default Status ID', 'opn-to-crm'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="sr_default_status_id" name="sr_default_status_id" 
                               value="<?php echo esc_attr(get_option('sr_default_status_id', '19')); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php _e('Enter the default status ID for new orders', 'opn-to-crm'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- OPN Payments Settings Section -->
        <div class="opn-api-settings">
            <h2><?php _e('OPN Payments Configuration', 'opn-to-crm'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Mode', 'opn-to-crm'); ?>
                    </th>
                    <td>
                        <label for="opn_test_mode">
                            <input type="checkbox" id="opn_test_mode" name="opn_test_mode" 
                                   value="1" <?php checked(1, $test_mode); ?>>
                            <?php _e('Test Mode', 'opn-to-crm'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Check this box to enable test mode', 'opn-to-crm'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Test Mode Keys -->
                <tr class="test-mode-keys <?php echo $test_mode ? '' : 'hidden'; ?>">
                    <th scope="row">
                        <label for="opn_test_public_key"><?php _e('Test Public Key', 'opn-to-crm'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="opn_test_public_key" name="opn_test_public_key" 
                               value="<?php echo esc_attr(get_option('opn_test_public_key')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr class="test-mode-keys <?php echo $test_mode ? '' : 'hidden'; ?>">
                    <th scope="row">
                        <label for="opn_test_secret_key"><?php _e('Test Secret Key', 'opn-to-crm'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="opn_test_secret_key" name="opn_test_secret_key" 
                               value="<?php echo esc_attr(get_option('opn_test_secret_key')); ?>" 
                               class="regular-text">
                    </td>
                </tr>

                <!-- Live Mode Keys -->
                <tr class="live-mode-keys <?php echo $test_mode ? 'hidden' : ''; ?>">
                    <th scope="row">
                        <label for="opn_live_public_key"><?php _e('Live Public Key', 'opn-to-crm'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="opn_live_public_key" name="opn_live_public_key" 
                               value="<?php echo esc_attr(get_option('opn_live_public_key')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr class="live-mode-keys <?php echo $test_mode ? 'hidden' : ''; ?>">
                    <th scope="row">
                        <label for="opn_live_secret_key"><?php _e('Live Secret Key', 'opn-to-crm'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="opn_live_secret_key" name="opn_live_secret_key" 
                               value="<?php echo esc_attr(get_option('opn_live_secret_key')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(); ?>
    </form>
</div>

<style>
.hidden {
    display: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#opn_test_mode').on('change', function() {
        var isTestMode = $(this).is(':checked');
        $('.test-mode-keys').toggleClass('hidden', !isTestMode);
        $('.live-mode-keys').toggleClass('hidden', isTestMode);
    });
});
</script>