<?php
if (!defined('ABSPATH')) {
    exit;
}

class SR_Checkout
{
    /**
     * @var SR_API
     */
    private $sr_api;

    /**
     * @var OPN_API
     */
    private $opn_api;

    /**
     * @var SR_Payment
     */
    private $payment;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->sr_api = new SR_API();
        $this->opn_api = new OPN_API();
        $this->payment = new SR_Payment($this->opn_api);

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        add_action('wp_ajax_sr_process_checkout', array($this, 'process_checkout'));
        add_action('wp_ajax_nopriv_sr_process_checkout', array($this, 'process_checkout'));
        add_shortcode('sr_checkout_form', array($this, 'render_checkout_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_scripts'));
    }

    public function enqueue_checkout_scripts()
    {
        // Проверяем только наличие шорткода на странице
        global $post;

        if ($post && has_shortcode($post->post_content, 'sr_checkout_form')) {
            wp_enqueue_script('omise-js', 'https://cdn.omise.co/omise.js', array(), null, true);
            wp_localize_script('sr-checkout', 'srCheckoutParams', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sr_checkout_nonce'),
                'opnPublicKey' => (new OPN_API())->get_public_key()
            ));
        }
    }

    /**
     * Process checkout form
     */
    public function process_checkout()
    {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'sr_checkout_nonce')) {
                throw new Exception(__('Invalid security token', 'opn-to-crm'));
            }

            $order_data = $this->validate_checkout_data($_POST);

            // Calculate amount based on package
            $amount = $this->calculate_amount($order_data['package_id'], $order_data['quantity']);

            // Create order in SalesRender
            $sr_order_data = $this->sr_api->format_order_data($order_data);
            $sr_response = $this->sr_api->create_order($sr_order_data);

            if (is_wp_error($sr_response)) {
                throw new Exception($sr_response->get_error_message());
            }

            // Process payment
            $payment_data = array(
                'amount' => $amount,
                'currency' => 'THB',
                'order_id' => $sr_response['orderMutation']['addOrder']['id'],
                'payment_method' => $order_data['payment_method'],
                'customer' => array(
                    'email' => $order_data['email'],
                    'name' => $order_data['first_name'] . ' ' . $order_data['last_name'],
                    'phone' => $order_data['phone']
                ),
                'return_uri' => add_query_arg('order_id', $sr_response['orderMutation']['addOrder']['id'], home_url('/checkout/complete/'))
            );

            $payment_result = $this->payment->process_payment($payment_data);

            if (is_wp_error($payment_result)) {
                throw new Exception($payment_result->get_error_message());
            }

            wp_send_json_success($payment_result);

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Validate checkout form data
     * 
     * @param array $data Form data
     * @return array Validated data
     * @throws Exception
     */
    private function validate_checkout_data($data)
    {
        $required_fields = array(
            'first_name' => __('First name is required', 'opn-to-crm'),
            'last_name' => __('Last name is required', 'opn-to-crm'),
            'email' => __('Email is required', 'opn-to-crm'),
            'phone' => __('Phone is required', 'opn-to-crm'),
            'address' => __('Address is required', 'opn-to-crm'),
            'city' => __('City is required', 'opn-to-crm'),
            'postcode' => __('Postal code is required', 'opn-to-crm'),
            'package_id' => __('Please select a package', 'opn-to-crm'),
            'payment_method' => __('Please select a payment method', 'opn-to-crm')
        );

        $validated = array();

        foreach ($required_fields as $field => $error) {
            if (empty($data[$field])) {
                throw new Exception($error);
            }
            $validated[$field] = sanitize_text_field($data[$field]);
        }

        // Validate email
        if (!is_email($validated['email'])) {
            throw new Exception(__('Invalid email address', 'opn-to-crm'));
        }

        // Validate phone (Thai format)
        if (!preg_match('/^(\+66|0)\d{9}$/', $validated['phone'])) {
            throw new Exception(__('Invalid phone number format', 'opn-to-crm'));
        }

        // Validate postal code (Thai format)
        if (!preg_match('/^\d{5}$/', $validated['postcode'])) {
            throw new Exception(__('Invalid postal code format', 'opn-to-crm'));
        }

        // Additional data
        $validated['quantity'] = isset($data['quantity']) ? absint($data['quantity']) : 1;
        $validated['region'] = isset($data['region']) ? sanitize_text_field($data['region']) : '';
        $validated['referer'] = wp_get_referer();
        $validated['ip'] = $_SERVER['REMOTE_ADDR'];

        return $validated;
    }

    /**
     * Calculate amount based on package and quantity
     * 
     * @param int $package_id Package ID
     * @param int $quantity Quantity
     * @return int Amount in cents
     */
    private function calculate_amount($package_id, $quantity)
    {
        // Get package prices and discounts from settings
        $packages = array(
            1 => array('price' => 1000, 'discount' => 0),     // 1x package
            2 => array('price' => 1000, 'discount' => 5),     // 2x package, 5% off
            3 => array('price' => 1000, 'discount' => 10),    // 3x package, 10% off
            4 => array('price' => 1000, 'discount' => 15)     // 4x package, 15% off
        );

        if (!isset($packages[$package_id])) {
            throw new Exception(__('Invalid package selected', 'opn-to-crm'));
        }

        $package = $packages[$package_id];
        $base_amount = $package['price'] * $quantity;
        $discount = $base_amount * ($package['discount'] / 100);

        // Convert to cents
        return ($base_amount - $discount) * 100;
    }

    /**
     * Render checkout form
     * 
     * @return string
     */
    public function render_checkout_form()
    {
        ob_start();

        // Load form template
        include OPN_TO_CRM_PLUGIN_DIR . 'templates/checkout/form.php';

        return ob_get_clean();
    }
}