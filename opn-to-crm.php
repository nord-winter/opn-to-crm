<?php
/**
 * Plugin Name: OPN To CRM
 * Description: Integration with OPN Payments and SalesRender CRM
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

class OPN_To_CRM
{
    /**
     * @var null|OPN_To_CRM
     */
    private static $instance = null;

    /**
     * @var string
     */
    private $version = '1.0.0';

    /**
     * @return OPN_To_CRM
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->define_constants();
        $this->init_hooks();
        $this->includes();
    }

    /**
     * Define plugin constants
     */
    private function define_constants()
    {
        define('OPN_TO_CRM_VERSION', $this->version);
        define('OPN_TO_CRM_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('OPN_TO_CRM_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks()
    {
        add_action('plugins_loaded', array($this, 'init_plugin'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
    }

    /**
     * Include required files
     */
    private function includes()
    {
        // API Classes
        require_once OPN_TO_CRM_PLUGIN_DIR . 'includes/api/class-sr-api.php';
        require_once OPN_TO_CRM_PLUGIN_DIR . 'includes/api/class-opn-api.php';

        // Admin Classes
        require_once OPN_TO_CRM_PLUGIN_DIR . 'includes/admin/class-sr-admin.php';
        require_once OPN_TO_CRM_PLUGIN_DIR . 'includes/admin/class-sr-settings.php';
        require_once OPN_TO_CRM_PLUGIN_DIR . 'includes/admin/class-sr-package-admin.php';

        // Checkout Classes
        require_once OPN_TO_CRM_PLUGIN_DIR . 'includes/checkout/class-sr-checkout.php';
        require_once OPN_TO_CRM_PLUGIN_DIR . 'includes/checkout/class-sr-payment.php';
    }

    /**
     * Initialize plugin
     */
    public function init_plugin()
    {
        // Initialize admin
        if (is_admin()) {
            new SR_Admin();
        }

        if (is_admin()) {
            new SR_Package_Admin();
        }

        // Initialize checkout
        new SR_Checkout();
    }

    /**
     * Enqueue admin scripts
     */
    public function admin_scripts()
    {
        wp_enqueue_style(
            'opn-to-crm-admin',
            OPN_TO_CRM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            OPN_TO_CRM_VERSION
        );

        wp_enqueue_script(
            'opn-to-crm-admin',
            OPN_TO_CRM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            OPN_TO_CRM_VERSION,
            true
        );
    }

    /**
     * Enqueue frontend scripts
     */
    public function frontend_scripts()
    {
        $opn_api = new OPN_API();

        wp_enqueue_style(
            'opn-to-crm-frontend',
            OPN_TO_CRM_PLUGIN_URL . 'assets/css/style.css',
            array(),
            OPN_TO_CRM_VERSION
        );

        wp_enqueue_script(
            'opn-js',
            'https://cdn.omise.co/omise.js',
            array(),
            OPN_TO_CRM_VERSION,
            true
        );

        wp_enqueue_script(
            'opn-to-crm-checkout',
            OPN_TO_CRM_PLUGIN_URL . 'assets/js/checkout.js',
            array('jquery'),
            OPN_TO_CRM_VERSION,
            true
        );

        wp_enqueue_script(
            'opn-to-crm-payments',
            OPN_TO_CRM_PLUGIN_URL . 'assets/js/payments.js',
            array('jquery', 'opn-js', 'opn-to-crm-checkout'),
            OPN_TO_CRM_VERSION,
            true
        );

        wp_localize_script('opn-to-crm-payments', 'srCheckoutParams', array(
            'opnPublicKey' => $opn_api->get_public_key(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sr_checkout_nonce')
        ));

        $package_manager = new SR_Package_Admin();
        $packages = $package_manager->get_packages();
        wp_localize_script('opn-to-crm-checkout', 'srPackages', $packages);

    }

    /**
     * Получение публичного ключа OPN Payments
     *
     * @return string
     */
    private function get_opn_public_key()
    {
        $opn_api = new OPN_API();
        return $opn_api->get_public_key();
    }
}

// Initialize plugin
function OPN_To_CRM()
{
    return OPN_To_CRM::instance();
}

// Start the plugin
OPN_To_CRM();