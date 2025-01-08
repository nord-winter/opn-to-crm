<?php
if (!defined('ABSPATH')) {
    exit;
}

class SR_Checkout
{
    /**
     * @var SR_API
     * @var OPN_API
     * @var SR_Payment
     */
    private $sr_api;
    private $opn_api;
    private $payment;

    public function __construct()
    {
        $this->sr_api = new SR_API();
        $this->opn_api = new OPN_API();
        $this->payment = new SR_Payment($this->opn_api);

        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_action('wp_ajax_sr_create_order', array($this, 'ajax_create_order'));
        add_action('wp_ajax_nopriv_sr_create_order', array($this, 'ajax_create_order'));
        add_shortcode('sr_checkout_form', array($this, 'render_checkout_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_scripts'));
    }

    public function enqueue_checkout_scripts()
    {
        global $post;
        if ($post && has_shortcode($post->post_content, 'sr_checkout_form')) {
            wp_enqueue_script('omise-js', 'https://cdn.omise.co/omise.js', array(), null, true);
            wp_localize_script('sr-checkout', 'srCheckoutParams', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sr_checkout_nonce'),
                'opnPublicKey' => $this->opn_api->get_public_key()
            ));
        }
    }

    /**
     * Process checkout form
     */
    public function ajax_create_order()
    {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'sr_checkout_nonce')) {
                throw new Exception('Invalid security token');
            }

            $order_data = $this->validate_checkout_data($_POST);
            $sr_order_data = $this->sr_api->format_order_data($order_data);
            $sr_response = $this->sr_api->create_order($sr_order_data);
            
            if (is_wp_error($sr_response)) {
                throw new Exception($sr_response->get_error_message());
            }

            wp_send_json_success([
                'id' => $sr_response['orderMutation']['addOrder']['id']
            ]);

        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
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
            'first_name' => 'First name is required',
            'last_name' => 'Last name is required',
            'email' => 'Email is required',
            'phone' => 'Phone is required',
            'address' => 'Address is required',
            'country' => 'Country is required',
            'city' => 'City is required',
            'postal_code' => 'Postal code is required',
            'package_id' => 'Please select a package'
        );

        $validated = array();

        foreach ($required_fields as $field => $error) {
            if (empty($data[$field])) {
                throw new Exception($error);
            }
            $validated[$field] = sanitize_text_field($data[$field]);
        }

        if (!is_email($validated['email'])) {
            throw new Exception('Invalid email address');
        }

        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (strlen($phone) !== 9) {
            throw new Exception('Invalid phone number format' . $phone);
        }

        $phone = preg_replace('/^(?:66|0)/', '', $phone);
        $validated['phone'] = '0' . $phone;

        if (!preg_match('/^\d{5}$/', $validated['postal_code'])) {
            throw new Exception('Invalid postal code format');
        }

        $validated['quantity'] = 1;
        $validated['referer'] = wp_get_referer();
        $validated['ip'] = $_SERVER['REMOTE_ADDR'];

        return $validated;
    }

    /**
     * Render checkout form
     * 
     * @return string
     */
    public function render_checkout_form()
    {
        ob_start();


        include OPN_TO_CRM_PLUGIN_DIR . 'templates/checkout/form.php';

        return ob_get_clean();
    }
}