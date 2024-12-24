<?php
if (!defined('ABSPATH')) {
    exit;
}

class SR_Payment {
    /**
     * @var OPN_API
     */
    private $opn_api;

    /**
     * Constructor
     * 
     * @param OPN_API $opn_api
     */
    public function __construct($opn_api) {
        $this->opn_api = $opn_api;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_sr_create_source', array($this, 'ajax_create_source'));
        add_action('wp_ajax_nopriv_sr_create_source', array($this, 'ajax_create_source'));
        add_action('init', array($this, 'handle_opn_webhook'));
    }

    /**
     * Process payment
     * 
     * @param array $data Payment data
     * @return array|WP_Error Payment result
     */
    public function process_payment($data) {
        try {
            switch ($data['payment_method']) {
                case 'credit_card':
                    return $this->process_card_payment($data);
                
                case 'promptpay':
                    return $this->process_promptpay_payment($data);
                
                default:
                    return new WP_Error('invalid_payment_method', __('Invalid payment method', 'opn-to-crm'));
            }
        } catch (Exception $e) {
            return new WP_Error('payment_error', $e->getMessage());
        }
    }

    /**
     * Process credit card payment
     * 
     * @param array $data Payment data
     * @return array Payment result
     */
    private function process_card_payment($data) {
        $charge_data = array(
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'source' => $data['token'],
            'customer' => $data['customer'],
            'return_uri' => $data['return_uri'],
            'order_id' => $data['order_id']
        );

        $result = $this->opn_api->create_charge($charge_data);
        
        if (is_wp_error($result)) {
            throw new Exception($result->get_error_message());
        }

        return array(
            'id' => $result['id'],
            'status' => $result['status'],
            'authorize_uri' => $result['authorize_uri'] ?? null
        );
    }

    /**
     * Process PromptPay payment
     * 
     * @param array $data Payment data
     * @return array Payment result
     */
    private function process_promptpay_payment($data) {
        // Create source first
        $source_data = array(
            'type' => 'promptpay',
            'amount' => $data['amount'],
            'currency' => $data['currency']
        );

        $source = $this->opn_api->create_source($source_data);
        
        if (is_wp_error($source)) {
            throw new Exception($source->get_error_message());
        }

        // Create charge with source
        $charge_data = array(
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'source' => $source['id'],
            'customer' => $data['customer'],
            'return_uri' => $data['return_uri'],
            'order_id' => $data['order_id']
        );

        $result = $this->opn_api->create_charge($charge_data);
        
        if (is_wp_error($result)) {
            throw new Exception($result->get_error_message());
        }

        return array(
            'id' => $result['id'],
            'status' => $result['status'],
            'source' => array(
                'id' => $source['id'],
                'type' => $source['type'],
                'qr_code' => $source['qr_code']
            )
        );
    }

    /**
     * Create payment source (AJAX handler)
     */
    public function ajax_create_source() {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'sr_payment_nonce')) {
                throw new Exception(__('Invalid security token', 'opn-to-crm'));
            }

            if (empty($_POST['amount']) || empty($_POST['type'])) {
                throw new Exception(__('Invalid request data', 'opn-to-crm'));
            }

            $source_data = array(
                'type' => sanitize_text_field($_POST['type']),
                'amount' => absint($_POST['amount']),
                'currency' => 'THB'
            );

            $result = $this->opn_api->create_source($source_data);
            
            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            wp_send_json_success($result);

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle OPN webhook
     */
    public function handle_opn_webhook() {
        if (empty($_GET['sr_opn_webhook'])) {
            return;
        }

        $payload = file_get_contents('php://input');
        $event = null;

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $event = json_decode($payload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid payload');
            }

            // Handle different event types
            switch ($event['type']) {
                case 'charge.complete':
                    $this->handle_charge_complete($event['data']);
                    break;
                
                case 'charge.expire':
                    $this->handle_charge_expire($event['data']);
                    break;
                
                case 'charge.fail':
                    $this->handle_charge_fail($event['data']);
                    break;
            }

            http_response_code(200);
            exit('Webhook handled');

        } catch (Exception $e) {
            http_response_code(400);
            exit($e->getMessage());
        }
    }

    /**
     * Handle completed charge
     * 
     * @param array $data Event data
     */
    private function handle_charge_complete($data) {
        $sr_api = new SR_API();
        $order_id = $data['metadata']['order_id'];
        
        // Update order status in SalesRender
        $sr_api->update_order_status($order_id, 'completed');
    }

    /**
     * Handle expired charge
     * 
     * @param array $data Event data
     */
    private function handle_charge_expire($data) {
        $sr_api = new SR_API();
        $order_id = $data['metadata']['order_id'];
        
        // Update order status in SalesRender
        $sr_api->update_order_status($order_id, 'expired');
    }

    /**
     * Handle failed charge
     * 
     * @param array $data Event data
     */
    private function handle_charge_fail($data) {
        $sr_api = new SR_API();
        $order_id = $data['metadata']['order_id'];
        
        // Update order status in SalesRender
        $sr_api->update_order_status($order_id, 'failed');
    }
}