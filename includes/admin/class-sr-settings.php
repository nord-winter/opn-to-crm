<?php
if (!defined('ABSPATH')) {
    exit;
}

class SR_Settings {
    /**
     * @var array
     */
    private $settings = array();

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'init_settings'));
    }

    /**
     * Initialize settings
     */
    public function init_settings() {
        // SalesRender Settings Section
        add_settings_section(
            'sr_api_settings',
            __('SalesRender API Settings', 'opn-to-crm'),
            array($this, 'render_sr_section'),
            'opn_to_crm_settings'
        );

        // OPN Settings Section
        add_settings_section(
            'opn_api_settings',
            __('OPN Payments Settings', 'opn-to-crm'),
            array($this, 'render_opn_section'),
            'opn_to_crm_settings'
        );

        // Register SalesRender settings
        $this->add_sr_settings();

        // Register OPN settings
        $this->add_opn_settings();
    }

    /**
     * Add SalesRender settings fields
     */
    private function add_sr_settings() {
        // Company ID
        add_settings_field(
            'sr_company_id',
            __('Company ID', 'opn-to-crm'),
            array($this, 'render_text_field'),
            'opn_to_crm_settings',
            'sr_api_settings',
            array('id' => 'sr_company_id')
        );

        // API Token
        add_settings_field(
            'sr_api_token',
            __('API Token', 'opn-to-crm'),
            array($this, 'render_text_field'),
            'opn_to_crm_settings',
            'sr_api_settings',
            array('id' => 'sr_api_token')
        );

        // Project ID
        add_settings_field(
            'sr_project_id',
            __('Project ID', 'opn-to-crm'),
            array($this, 'render_text_field'),
            'opn_to_crm_settings',
            'sr_api_settings',
            array('id' => 'sr_project_id')
        );

        // Default Status ID
        add_settings_field(
            'sr_default_status_id',
            __('Default Status ID', 'opn-to-crm'),
            array($this, 'render_text_field'),
            'opn_to_crm_settings',
            'sr_api_settings',
            array('id' => 'sr_default_status_id')
        );
    }

    /**
     * Add OPN settings fields
     */
    private function add_opn_settings() {
        // Test Mode
        add_settings_field(
            'opn_test_mode',
            __('Test Mode', 'opn-to-crm'),
            array($this, 'render_checkbox_field'),
            'opn_to_crm_settings',
            'opn_api_settings',
            array('id' => 'opn_test_mode')
        );

        // Test Public Key
        add_settings_field(
            'opn_test_public_key',
            __('Test Public Key', 'opn-to-crm'),
            array($this, 'render_text_field'),
            'opn_to_crm_settings',
            'opn_api_settings',
            array('id' => 'opn_test_public_key')
        );

        // Test Secret Key
        add_settings_field(
            'opn_test_secret_key',
            __('Test Secret Key', 'opn-to-crm'),
            array($this, 'render_text_field'),
            'opn_to_crm_settings',
            'opn_api_settings',
            array(
                'id' => 'opn_test_secret_key',
                'type' => 'password'
            )
        );

        // Live Public Key
        add_settings_field(
            'opn_live_public_key',
            __('Live Public Key', 'opn-to-crm'),
            array($this, 'render_text_field'),
            'opn_to_crm_settings',
            'opn_api_settings',
            array('id' => 'opn_live_public_key')
        );

        // Live Secret Key
        add_settings_field(
            'opn_live_secret_key',
            __('Live Secret Key', 'opn-to-crm'),
            array($this, 'render_text_field'),
            'opn_to_crm_settings',
            'opn_api_settings',
            array(
                'id' => 'opn_live_secret_key',
                'type' => 'password'
            )
        );
    }

    /**
     * Render SalesRender section description
     */
    public function render_sr_section() {
        echo '<p>' . __('Configure your SalesRender API settings.', 'opn-to-crm') . '</p>';
    }

    /**
     * Render OPN section description
     */
    public function render_opn_section() {
        echo '<p>' . __('Configure your OPN Payments settings.', 'opn-to-crm') . '</p>';
    }

    /**
     * Render text field
     */
    public function render_text_field($args) {
        $id = $args['id'];
        $type = isset($args['type']) ? $args['type'] : 'text';
        $value = get_option($id);
        
        printf(
            '<input type="%s" id="%s" name="%s" value="%s" class="regular-text">',
            esc_attr($type),
            esc_attr($id),
            esc_attr($id),
            esc_attr($value)
        );
    }

    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $id = $args['id'];
        $checked = get_option($id);
        
        printf(
            '<input type="checkbox" id="%s" name="%s" value="1" %s>',
            esc_attr($id),
            esc_attr($id),
            checked(1, $checked, false)
        );
    }

    /**
     * Get setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get_setting($key, $default = false) {
        return get_option($key, $default);
    }

    /**
     * Get all settings
     *
     * @return array
     */
    public function get_all_settings() {
        return array(
            'sr_company_id' => $this->get_setting('sr_company_id'),
            'sr_api_token' => $this->get_setting('sr_api_token'),
            'sr_project_id' => $this->get_setting('sr_project_id'),
            'sr_default_status_id' => $this->get_setting('sr_default_status_id'),
            'opn_test_mode' => $this->get_setting('opn_test_mode'),
            'opn_test_public_key' => $this->get_setting('opn_test_public_key'),
            'opn_test_secret_key' => $this->get_setting('opn_test_secret_key'),
            'opn_live_public_key' => $this->get_setting('opn_live_public_key'),
            'opn_live_secret_key' => $this->get_setting('opn_live_secret_key')
        );
    }
}