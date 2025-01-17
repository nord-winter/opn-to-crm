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

        try {
            $response = $this->request('POST', $endpoint, $data);

            //error_log('OPN API Response: ' . print_r($response, true));

            return $response;

        } catch (Exception $e) {
            error_log('OPN API Error: ' . $e->getMessage());
            return new WP_Error('opn_api_error', $e->getMessage());
        }
    }

    /**
     * Create source for payment
     * 
     * @param array $data Source data
     * @return array|WP_Error
     */
    public function create_source($data)
    {
        try {
            $source = [
                'type' => 'promptpay',
                'amount' => $data['amount'],
                'currency' => 'THB',
                'platform_type' => 'WEB',
            ];

            $source_response = $this->request('POST', '/sources', $source);

            if (empty($source_response['id'])) {
                throw new Exception('Failed to create source');
            }

            $charge = $this->create_charge([
                'amount' => $data['amount'],
                'currency' => 'THB',
                'source' => $source_response['id']
            ]);

            if (empty($charge['source']['scannable_code']['image']['download_uri'])) {
                throw new Exception('QR code not available');
            }

            return wp_send_json_success([
                'source' => $source_response,
                'charge' => $charge,
                'qr_code_uri' => $charge['source']['scannable_code']['image']['download_uri'] // TODO: Неверный путь к QR коду
            ]);

        } catch (Exception $e) {
            error_log('PromptPay error: ' . $e->getMessage());
            return wp_send_json_error(['message' => $e->getMessage()]);
        }
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
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->secret_key . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Omise-Version' => '2019-05-29'
            ],
            'body' => $body ? json_encode($body) : null,
            'timeout' => 30,
            'sslverify' => true
        ];


        $url = $this->api_base . $endpoint;
        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);

        if ($code >= 400) {
            throw new Exception(isset($body['message']) ? $body['message'] : 'Unknown error');
        }

        return $body;
    }

    /**
     * Check source by ID
     * 
     * @param string $source_id
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