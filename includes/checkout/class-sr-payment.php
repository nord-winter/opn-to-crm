<?php
if (!defined('ABSPATH')) {
    exit;
}

class SR_Payment
{
    /**
     * @var OPN_API
     */
    private $opn_api;

    /**
     * Constructor
     * 
     * @param OPN_API $opn_api
     */
    public function __construct($opn_api)
    {
        $this->opn_api = $opn_api;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        add_action('wp_ajax_sr_create_source', array($this, 'ajax_create_source'));
        add_action('wp_ajax_nopriv_sr_create_source', array($this, 'ajax_create_source'));
        add_action('init', array($this, 'handle_opn_webhook'));

        add_action('wp_ajax_sr_create_promptpay_source', array($this, 'ajax_create_promptpay_source'));
        add_action('wp_ajax_nopriv_sr_create_promptpay_source', array($this, 'ajax_create_promptpay_source'));

        add_action('wp_ajax_sr_check_payment_status', array($this, 'ajax_check_payment_status'));
        add_action('wp_ajax_nopriv_sr_check_payment_status', array($this, 'ajax_check_payment_status'));

        add_action('wp_ajax_sr_process_payment', array($this, 'ajax_process_payment'));
        add_action('wp_ajax_nopriv_sr_process_payment', array($this, 'ajax_process_payment'));
    }

    /**
     * Обработчик AJAX для процесса оплаты
     */
    public function ajax_process_payment()
    {
        error_log('POST data: ' . print_r($_POST, true));
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'sr_checkout_nonce')) {
                throw new Exception('Invalid nonce');
            }
            error_log('Payment data: ' . print_r($_POST, true));

            // Создаем заказ в CRM
            $sr_api = new SR_API();
            $order_data = $this->prepare_order_data($_POST);
            $order_result = $sr_api->create_order($order_data);

            if (is_wp_error($order_result)) {
                throw new Exception($order_result->get_error_message());
            }

            $order_id = $order_result['orderMutation']['addOrder']['id'];
            $amount = absint($_POST['amount']);
            $payment_type = sanitize_text_field($_POST['payment_type']);
            $return_uri = home_url('/complete/');

            if ($payment_type === 'card') {
                $token = sanitize_text_field($_POST['card']);
                $charge_data = [
                    'amount' => $amount,
                    'currency' => 'THB',
                    'card' => $token,
                    'return_uri' => $return_uri,
                    'metadata' => ['order_id' => $order_id]
                ];
            } else {
                $source = $this->opn_api->create_source([
                    'type' => 'promptpay',
                    'amount' => $amount,
                    'currency' => 'THB'
                ]);

                if (is_wp_error($source)) {
                    throw new Exception($source->get_error_message());
                }

                $charge_data = [
                    'amount' => $amount,
                    'currency' => 'THB',
                    'source' => $source['id'],
                    'return_uri' => $return_uri,
                    'metadata' => ['order_id' => $order_id]
                ];
            }

            $result = $this->opn_api->create_charge($charge_data);
            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            wp_send_json_success($result);

        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function prepare_order_data($post_data)
    {
        $phone = preg_replace('/[^0-9]/', '', $post_data['phone']);
        if (strlen($phone) > 9) {
            $phone = substr($phone, -9); // Берем последние 9 цифр
        }
        $phone = '0' . $phone;


        return [
            'statusId' => get_option('sr_default_status_id', '19'),
            'projectId' => get_option('sr_project_id'),
            'orderData' => [
                'humanNameFields' => [
                    [
                        'field' => 'name1',
                        'value' => [
                            'firstName' => sanitize_text_field($post_data['first_name']),
                            'lastName' => sanitize_text_field($post_data['last_name'])
                        ]
                    ]
                ],
                'phoneFields' => [
                    [
                        'field' => 'phone',
                        'value' => $phone
                    ]
                ],
                'addressFields' => [
                    [
                        'field' => 'adress',
                        'value' => [
                            'postcode' => sanitize_text_field($post_data['postal_code']),
                            'region' => sanitize_text_field($post_data['country']),
                            'city' => sanitize_text_field($post_data['city']),
                            'address_1' => sanitize_text_field($post_data['address'])
                        ]
                    ]
                ]
            ],
            'cart' => [
                'items' => [
                    [
                        'itemId' => absint($post_data['package_id']),
                        'quantity' => 1,
                        'variation' => 1
                    ]
                ]
            ],
            'source' => [
                'refererUri' => wp_get_referer(),
                'ip' => $_SERVER['REMOTE_ADDR']
            ]
        ];
    }

    /**
     * Process payment
     * 
     * @param array $data Payment data
     * @return array|WP_Error Payment result
     */
    public function process_payment($data)
    {
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
    private function process_card_payment($data)
    {
        $charge_data = array(
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'card' => $data['token'], // Было source
            'customer' => $data['customer'],
            'return_uri' => $data['return_uri'],
            'metadata' => array(
                'order_id' => $data['order_id']
            )
        );

        if (isset($data['customer'])) {
            $charge_data['customer'] = $data['customer'];
        }

        $result = $this->opn_api->create_charge($charge_data);

        if (is_wp_error($result)) {
            return $result;
        }

        return array(
            'id' => $result['id'],
            'status' => $result['status'],
            'authorizeUri' => isset($result['authorize_uri']) ? $result['authorize_uri'] : null
        );
    }

    /**
     * Process PromptPay payment
     * 
     * @param array $data Payment data
     * @return array Payment result
     */

    private function process_promptpay_payment($data)
    {
        // Создаем source
        $source_data = array(
            'type' => 'promptpay',
            'amount' => $data['amount'],
            'currency' => $data['currency']
        );

        $source = $this->opn_api->create_source($source_data);

        if (is_wp_error($source)) {
            return $source;
        }

        // Создаем charge
        $charge_data = array(
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'source' => $source['id'],
            'return_uri' => $data['return_uri'],
            'metadata' => $data['metadata']
        );

        if (isset($data['customer'])) {
            $charge_data['customer'] = $data['customer'];
        }

        $result = $this->opn_api->create_charge($charge_data);

        if (is_wp_error($result)) {
            return $result;
        }

        return array(
            'id' => $result['id'],
            'status' => $result['status'],
            'source' => array(
                'id' => $source['id'],
                'type' => $source['type'],
                'qr_code' => isset($source['qr_code']) ? $source['qr_code'] : null
            )
        );
    }

    /**
     * Create payment source (AJAX handler)
     */
    public function ajax_create_source()
    {
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
    public function handle_opn_webhook()
    {
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
    private function handle_charge_complete($data)
    {
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
    private function handle_charge_expire($data)
    {
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
    private function handle_charge_fail($data)
    {
        $sr_api = new SR_API();
        $order_id = $data['metadata']['order_id'];

        // Update order status in SalesRender
        $sr_api->update_order_status($order_id, 'failed');
    }

    /**
     * Создание PromptPay QR-кода
     */
    public function ajax_create_promptpay_source() {
        try {
            check_ajax_referer('sr_checkout_nonce', 'nonce');
            
            $amount = absint($_POST['amount'] ?? 0);
            if (!$amount) {
                throw new Exception('Invalid amount');
            }
    
            $source = $this->opn_api->create_source([
                'amount' => $amount
            ]);
    
            if (is_wp_error($source)) {
                throw new Exception($source->get_error_message());
            }
    
            error_log('OPN Source Result: ' . print_r($source, true));
    
            if (empty($source['scannable_code']['image']['uri'])) {
                throw new Exception('QR code not available');
            }
    
            wp_send_json_success([
                'id' => $source['id'],
                'amount' => $amount,
                'qr_image' => $source['scannable_code']['image']['uri']
            ]);
    
        } catch (Exception $e) {
            error_log('PromptPay error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    /**
     * Проверка статуса платежа
     */
    public function ajax_check_payment_status() {
        try {
            check_ajax_referer('sr_checkout_nonce', 'nonce');
            
            $source_id = sanitize_text_field($_POST['source_id'] ?? '');
            if (!$source_id) {
                throw new Exception('Invalid source ID');
            }
    
            $result = $this->opn_api->check_source($source_id);
            error_log('Payment status check - Source ID: ' . $source_id . ', Status: ' . ($result['charge_status'] ?? 'unknown'));
    
            wp_send_json_success([
                'paid' => ($result['charge_status'] ?? '') === 'successful',
                'expired' => ($result['charge_status'] ?? '') === 'expired',
                'status' => $result['charge_status'] ?? 'unknown'
            ]);
    
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function check_payment_status($sourceId)
    {
        $result = $this->opn_api->check_source($sourceId);

        if (is_wp_error($result)) {
            return [
                'paid' => false,
                'expired' => false,
                'status' => 'error'
            ];
        }

        return [
            'paid' => ($result['status'] ?? '') === 'used',
            'expired' => ($result['status'] ?? '') === 'expired',
            'status' => $result['status'] ?? 'unknown'
        ];
    }

}