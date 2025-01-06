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
        <?php settings_fields('opn_to_crm_settings'); ?>
        
        <div class="sr-api-settings">
            <h2><?php _e('General Settings', 'opn-to-crm'); ?></h2>
            <table class="form-table">
                <!-- SalesRender API Settings -->
                <tr>
                    <th scope="row"><label for="sr_company_id"><?php _e('Company ID', 'opn-to-crm'); ?></label></th>
                    <td><input type="text" id="sr_company_id" name="sr_company_id" value="<?php echo esc_attr(get_option('sr_company_id')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sr_api_token"><?php _e('API Token', 'opn-to-crm'); ?></label></th>
                    <td><input type="password" id="sr_api_token" name="sr_api_token" value="<?php echo esc_attr(get_option('sr_api_token')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sr_project_id"><?php _e('Project ID', 'opn-to-crm'); ?></label></th>
                    <td><input type="text" id="sr_project_id" name="sr_project_id" value="<?php echo esc_attr(get_option('sr_project_id')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sr_default_status_id"><?php _e('Default Status ID', 'opn-to-crm'); ?></label></th>
                    <td><input type="text" id="sr_default_status_id" name="sr_default_status_id" value="<?php echo esc_attr(get_option('sr_default_status_id', '19')); ?>" class="regular-text"></td>
                </tr>
                
                <!-- OPN API Settings -->
                <tr>
                    <th scope="row"><?php _e('Mode', 'opn-to-crm'); ?></th>
                    <td>
                        <label for="opn_test_mode">
                            <input type="checkbox" id="opn_test_mode" name="opn_test_mode" value="1" <?php checked(1, $test_mode); ?>>
                            <?php _e('Test Mode', 'opn-to-crm'); ?>
                        </label>
                    </td>
                </tr>
                
                <!-- Test Mode Keys -->
                <tr class="test-mode-keys <?php echo $test_mode ? '' : 'hidden'; ?>">
                    <th scope="row"><label for="opn_test_public_key"><?php _e('Test Public Key', 'opn-to-crm'); ?></label></th>
                    <td><input type="text" id="opn_test_public_key" name="opn_test_public_key" value="<?php echo esc_attr(get_option('opn_test_public_key')); ?>" class="regular-text"></td>
                </tr>
                <tr class="test-mode-keys <?php echo $test_mode ? '' : 'hidden'; ?>">
                    <th scope="row"><label for="opn_test_secret_key"><?php _e('Test Secret Key', 'opn-to-crm'); ?></label></th>
                    <td>
                        <input type="password" id="opn_test_secret_key" name="opn_test_secret_key" value="<?php echo esc_attr(get_option('opn_test_secret_key')); ?>" class="regular-text">
                        <button type="button" class="button toggle-password" data-target="#opn_test_secret_key">
                            <?php _e('Show', 'opn-to-crm'); ?>
                        </button>
                    </td>
                </tr>

                <!-- Live Mode Keys -->
                <tr class="live-mode-keys <?php echo $test_mode ? 'hidden' : ''; ?>">
                    <th scope="row"><label for="opn_live_public_key"><?php _e('Live Public Key', 'opn-to-crm'); ?></label></th>
                    <td><input type="text" id="opn_live_public_key" name="opn_live_public_key" value="<?php echo esc_attr(get_option('opn_live_public_key')); ?>" class="regular-text"></td>
                </tr>
                <tr class="live-mode-keys <?php echo $test_mode ? 'hidden' : ''; ?>">
                    <th scope="row"><label for="opn_live_secret_key"><?php _e('Live Secret Key', 'opn-to-crm'); ?></label></th>
                    <td>
                        <input type="password" id="opn_live_secret_key" name="opn_live_secret_key" value="<?php echo esc_attr(get_option('opn_live_secret_key')); ?>" class="regular-text">
                        <button type="button" class="button toggle-password" data-target="#opn_live_secret_key">
                            <?php _e('Show', 'opn-to-crm'); ?>
                        </button>
                    </td>
                </tr>
            </table>
        </div>
        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
   // Handle test mode toggle
   $('#opn_test_mode').on('change', function() {
       const isTestMode = $(this).is(':checked');
       $('.test-mode-keys').toggleClass('hidden', !isTestMode);
       $('.live-mode-keys').toggleClass('hidden', isTestMode);
   });
   
   // Handle password visibility toggle
   $('.toggle-password').on('click', function() {
       const $this = $(this);
       const $input = $($this.data('target'));
       
       if ($input.attr('type') === 'password') {
           $input.attr('type', 'text');
           $this.text('<?php _e('Hide', 'opn-to-crm'); ?>');
       } else {
           $input.attr('type', 'password');
           $this.text('<?php _e('Show', 'opn-to-crm'); ?>');
       }
   });
});
</script>

<style>
.hidden {
   display: none;
}
.toggle-password {
   margin-left: 10px;
}
</style>