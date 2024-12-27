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
        try {
            // Проверяем nonce
            if (!wp_verify_nonce($_POST['nonce'], 'sr_checkout_nonce')) {
                throw new Exception(__('Invalid security token', 'opn-to-crm'));
            }

            // Проверяем обязательные параметры
            if (empty($_POST['token']) || empty($_POST['amount'])) {
                throw new Exception(__('Missing required parameters', 'opn-to-crm'));
            }

            // Получаем и валидируем данные
            $token = sanitize_text_field($_POST['token']);
            $amount = absint($_POST['amount']);

            if ($amount <= 0) {
                throw new Exception(__('Invalid amount', 'opn-to-crm'));
            }

            // Создаем платёж через OPN API
            $charge_data = array(
                'amount' => $amount,
                'currency' => 'THB',
                'source' => $token,
                'return_uri' => add_query_arg('order_id', 'temp', home_url('/checkout/complete/')),
                'metadata' => array(
                    'payment_type' => 'card',
                    'website' => get_site_url()
                )
            );

            $result = $this->opn_api->create_charge($charge_data);

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            // Проверяем результат создания платежа
            if (!isset($result['id'])) {
                throw new Exception(__('Invalid payment response', 'opn-to-crm'));
            }

            // Формируем ответ
            $response = array(
                'id' => $result['id'],
                'status' => $result['status']
            );

            // Добавляем URL для 3D Secure если требуется
            if (isset($result['authorize_uri']) && !empty($result['authorize_uri'])) {
                $response['authorizeUri'] = $result['authorize_uri'];
            }

            wp_send_json_success($response);

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
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
    private function process_promptpay_payment($data)
    {
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
    public function ajax_create_promptpay_source()
    {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'sr_checkout_nonce')) {
                throw new Exception(__('Invalid security token', 'opn-to-crm'));
            }

            $amount = isset($_POST['amount']) ? absint($_POST['amount']) : 0;
            if (!$amount) {
                throw new Exception(__('Invalid amount', 'opn-to-crm'));
            }

            $source_data = array(
                'type' => 'promptpay',
                'amount' => $amount,
                'currency' => 'THB'
            );

            $result = $this->opn_api->create_source($source_data);

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            wp_send_json_success(array(
                'id' => $result['id'],
                'qr_image' => $result['qr']['image']['download_uri']
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Проверка статуса платежа
     */
    public function ajax_check_payment_status()
    {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'sr_checkout_nonce')) {
                throw new Exception(__('Invalid security token', 'opn-to-crm'));
            }

            $source_id = isset($_POST['source_id']) ? sanitize_text_field($_POST['source_id']) : '';
            if (!$source_id) {
                throw new Exception(__('Invalid source ID', 'opn-to-crm'));
            }

            $result = $this->opn_api->check_source($source_id);

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            // Add detailed logging
            error_log('Payment status check - Source ID: ' . $source_id . ', Status: ' . $result['status']);

            wp_send_json_success(array(
                'paid' => $result['status'] === 'used',
                'expired' => $result['status'] === 'expired',
                'status' => $result['status']
            ));

        } catch (Exception $e) {
            error_log('Payment status check error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

}