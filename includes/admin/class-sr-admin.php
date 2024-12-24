<?php
if (!defined('ABSPATH')) {
    exit;
}

class SR_Admin {
    /**
     * @var SR_Settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new SR_Settings();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_menu_items'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_notices', array($this, 'show_setup_notices'));
    }

    /**
     * Add menu items
     */
    public function add_menu_items() {
        add_menu_page(
            __('OPN To CRM', 'opn-to-crm'),
            __('OPN To CRM', 'opn-to-crm'),
            'manage_options',
            'opn-to-crm',
            array($this, 'render_settings_page'),
            'dashicons-cart',
            55
        );
    }

    /**
     * Initialize plugin settings
     */
    public function init_settings() {
        register_setting('opn_to_crm_settings', 'sr_company_id');
        register_setting('opn_to_crm_settings', 'sr_api_token');
        register_setting('opn_to_crm_settings', 'sr_project_id');
        register_setting('opn_to_crm_settings', 'sr_default_status_id');
        
        register_setting('opn_to_crm_settings', 'opn_test_mode');
        register_setting('opn_to_crm_settings', 'opn_test_public_key');
        register_setting('opn_to_crm_settings', 'opn_test_secret_key');
        register_setting('opn_to_crm_settings', 'opn_live_public_key');
        register_setting('opn_to_crm_settings', 'opn_live_secret_key');
    }

    /**
     * Show setup notices if required settings are missing
     */
    public function show_setup_notices() {
        if (!$this->is_plugin_configured()) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <?php
                    printf(
                        __('OPN To CRM requires setup. Please %sconfigure the plugin%s.', 'opn-to-crm'),
                        '<a href="' . admin_url('admin.php?page=opn-to-crm') . '">',
                        '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Check if plugin is configured
     *
     * @return boolean
     */
    private function is_plugin_configured() {
        $required_settings = array(
            'sr_company_id',
            'sr_api_token',
            'sr_project_id',
            'opn_test_mode'
        );

        foreach ($required_settings as $setting) {
            if (!get_option($setting)) {
                return false;
            }
        }

        $test_mode = get_option('opn_test_mode');
        
        if ($test_mode) {
            return get_option('opn_test_public_key') && get_option('opn_test_secret_key');
        }

        return get_option('opn_live_public_key') && get_option('opn_live_secret_key');
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'opn_to_crm_messages',
                'opn_to_crm_message',
                __('Settings Saved', 'opn-to-crm'),
                'updated'
            );
        }

        settings_errors('opn_to_crm_messages');

        include OPN_TO_CRM_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    /**
     * Get settings instance
     *
     * @return SR_Settings
     */
    public function get_settings() {
        return $this->settings;
    }
}