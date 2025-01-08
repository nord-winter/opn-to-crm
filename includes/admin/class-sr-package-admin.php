<?php
if (!defined('ABSPATH')) {
    exit;
}

class SR_Package_Admin
{

    private $default_packages = [
        [
            'id' => 4,
            'name' => '4x',
            'units' => 40,
            'discount' => 15,
            'price' => 3400,
            'image' => ''
        ],
        [
            'id' => 3,
            'name' => '3x',
            'units' => 30,
            'discount' => 10,
            'price' => 2700,
            'image' => ''
        ],
        [
            'id' => 2,
            'name' => '2x',
            'units' => 20,
            'discount' => 5,
            'price' => 1900,
            'image' => ''
        ],
        [
            'id' => 1,
            'name' => '1x',
            'units' => 10,
            'discount' => 0,
            'price' => 1000,
            'image' => ''
        ]
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_menu_item'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));


        add_action('wp_ajax_sr_save_package', array($this, 'ajax_save_package'));
        add_action('wp_ajax_sr_delete_package', array($this, 'ajax_delete_package'));
    }

    /**
     * Add submenu item
     */
    public function add_menu_item()
    {
        add_submenu_page(
            'opn-to-crm',                // Parent slug
            __('Packages', 'opn-to-crm'), // Page title
            __('Packages', 'opn-to-crm'), // Menu title
            'manage_options',             // Capability
            'opn-to-crm-packages',        // Menu slug
            array($this, 'render_page')   // Callback function
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook)
    {
        if ('opn-to-crm_page_opn-to-crm-packages' !== $hook) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_script(
            'opn-to-crm-admin',
            OPN_TO_CRM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            OPN_TO_CRM_VERSION,
            true
        );

        wp_localize_script('opn-to-crm-admin', 'srPackageParams', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sr_package_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this package?', 'opn-to-crm'),
                'saveSuccess' => __('Package saved successfully!', 'opn-to-crm'),
                'saveFail' => __('Failed to save package.', 'opn-to-crm'),
                'deleteSuccess' => __('Package deleted successfully!', 'opn-to-crm'),
                'deleteFail' => __('Failed to delete package.', 'opn-to-crm')
            )
        ));

    }

    public function get_packages()
    {
        $packages = get_option('sr_packages');
        return $packages ?: $this->default_packages;
    }

    /**
     * AJAX handler for saving package data
     */
    public function ajax_save_package()
    {
        error_log('Save package request: ' . print_r($_POST, true));
        check_ajax_referer('sr_package_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $package_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $package_data = array(
            'id' => $package_id,
            'name' => sanitize_text_field($_POST['name']),
            'units' => absint($_POST['units']),
            'price' => floatval($_POST['price']),
            'discount' => floatval($_POST['discount']),
            'image' => esc_url_raw($_POST['image'])
        );

        $packages = $this->get_packages();
        $found = false;

        foreach ($packages as $key => $package) {
            if ($package['id'] === $package_id) {
                $packages[$key] = $package_data;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $packages[] = $package_data;
        }

        update_option('sr_packages', $packages);
        wp_send_json_success();
    }

    /**
     * AJAX handler for deleting a package
     */
    public function ajax_delete_package()
    {
        check_ajax_referer('sr_package_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $package_id = absint($_POST['id']);
        $packages = get_option('sr_packages', array());

        $packages = array_filter($packages, function ($package) use ($package_id) {
            return $package['id'] !== $package_id;
        });


        $packages = array_values($packages);

        update_option('sr_packages', $packages);
        wp_send_json_success();
    }

    /**
     * Render packages admin page
     */
    public function render_page()
    {
        $packages = $this->get_packages();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Packages', 'opn-to-crm'); ?></h1>
            <a href="#" class="page-title-action" id="add-new-package"><?php _e('Add New', 'opn-to-crm'); ?></a>

            <div class="sr-packages-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Image', 'opn-to-crm'); ?></th>
                            <th><?php _e('Name', 'opn-to-crm'); ?></th>
                            <th><?php _e('Units', 'opn-to-crm'); ?></th>
                            <th><?php _e('Price', 'opn-to-crm'); ?></th>
                            <th><?php _e('Discount', 'opn-to-crm'); ?></th>
                            <th><?php _e('Actions', 'opn-to-crm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $package): ?>
                            <tr data-id="<?php echo esc_attr($package['id']); ?>">
                                <td>
                                    <div class="package-image">
                                        <?php if (!empty($package['image'])): ?>
                                            <img src="<?php echo esc_url($package['image']); ?>" alt="">
                                        <?php else: ?>
                                            <button class="button upload-image"><?php _e('Upload Image', 'opn-to-crm'); ?></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><input type="text" class="package-name" value="<?php echo esc_attr($package['name']); ?>"></td>
                                <td><input type="number" class="package-units" value="<?php echo esc_attr($package['units']); ?>">
                                </td>
                                <td><input type="number" class="package-price" value="<?php echo esc_attr($package['price']); ?>">
                                </td>
                                <td><input type="number" class="package-discount"
                                        value="<?php echo esc_attr($package['discount']); ?>"></td>
                                <td>
                                    <button class="button save-package"><?php _e('Save', 'opn-to-crm'); ?></button>
                                    <button class="button delete-package"><?php _e('Delete', 'opn-to-crm'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <script type="text/template" id="package-row-template">
                        <tr data-id="{{id}}">
                            <td>
                                <div class="package-image">
                                    <button class="button upload-image"><?php _e('Upload Image', 'opn-to-crm'); ?></button>
                                </div>
                            </td>
                            <td><input type="text" class="package-name" value=""></td>
                            <td><input type="number" class="package-units" value=""></td>
                            <td><input type="number" class="package-price" value=""></td>
                            <td><input type="number" class="package-discount" value="0"></td>
                            <td>
                                <button class="button save-package"><?php _e('Save', 'opn-to-crm'); ?></button>
                                <button class="button delete-package"><?php _e('Delete', 'opn-to-crm'); ?></button>
                            </td>
                        </tr>
                    </script>
        </div>
        <?php
    }
}