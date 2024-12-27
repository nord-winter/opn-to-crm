<?php
if (!defined('ABSPATH')) {
    exit;
}

class OPN_API
{
    private $secret_key;
    private $public_key;
    private $api_base = 'https://api.omise.co';
    private $is_test;

    public function __construct()
    {
        $this->is_test = get_option('opn_test_mode', true);
        $this->secret_key = $this->is_test ? get_option('opn_test_secret_key') : get_option('opn_live_secret_key');
        $this->public_key = $this->is_test ? get_option('opn_test_public_key') : get_option('opn_live_public_key');
    }

    /**
     * Create a charge
     * 
     * @param array $data Charge data
     * @return array|WP_Error
     */
    public function create_charge($data)
    {
        error_log('Charge data: ' . print_r($data, true));
        $endpoint = '/charges';

        $body = array(
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'THB',
            'source' => $data['source'] ?? $data['token'],
            'metadata' => array(
                'order_id' => $data['order_id'] ?? 'temp',
                'payment_type' => $data['payment_type'] ?? 'card',
                'website' => $data['website'] ?? get_site_url()
            )
        );

        if (isset($data['customer'])) {
            $body['customer'] = $data['customer'];
        }

        if (isset($data['return_uri'])) {
            $body['return_uri'] = $data['return_uri'];
        }

        return $this->request('POST', $endpoint, $body);
    }

    /**
     * Create source for payment
     * 
     * @param array $data Source data
     * @return array|WP_Error
     */
    public function create_source($data)
    {
        $endpoint = '/sources';

        $body = array(
            'type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => 'THB'
        );

        return $this->request('POST', $endpoint, $body);
    }

    /**
     * Retrieve a charge
     * 
     * @param string $charge_id
     * @return array|WP_Error
     */
    public function get_charge($charge_id)
    {
        $endpoint = '/charges/' . $charge_id;
        return $this->request('GET', $endpoint);
    }

    /**
     * Create refund
     * 
     * @param string $charge_id
     * @param array $data Refund data
     * @return array|WP_Error
     */
    public function create_refund($charge_id, $data)
    {
        $endpoint = '/charges/' . $charge_id . '/refunds';

        $body = array(
            'amount' => $data['amount'],
            'metadata' => array(
                'reason' => $data['reason'] ?? ''
            )
        );

        return $this->request('POST', $endpoint, $body);
    }

    /**
     * Make request to OPN API
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $body Request body
     * @return array|WP_Error
     */
    private function request($method, $endpoint, $body = null)
    {
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->secret_key . ':'),
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );

        if ($body !== null) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($this->api_base . $endpoint, $args);
        error_log('API Response: ' . wp_remote_retrieve_body($response));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            return new WP_Error(
                'opn_api_error',
                isset($body['message']) ? $body['message'] : 'Unknown error',
                array('status' => $code)
            );
        }

        return $body;
    }

    /**
     * Проверка статуса источника платежа
     * 
     * @param string $source_id ID источника платежа
     * @return array|WP_Error
     */
    public function check_source($source_id)
    {
        $endpoint = '/sources/' . $source_id;
        return $this->request('GET', $endpoint);
    }

    /**
     * Get public key for frontend
     * 
     * @return string
     */
    public function get_public_key()
    {
        return $this->public_key;
    }

    /**
     * Check if test mode is enabled
     * 
     * @return boolean
     */
    public function is_test_mode()
    {
        return $this->is_test;
    }
}