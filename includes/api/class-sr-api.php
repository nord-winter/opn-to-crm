<?php
if (!defined('ABSPATH')) {
    exit;
}

class SR_API {
    /**
     * @var string
     */
    private $api_url = 'https://de.backend.salesrender.com/companies/';

    /**
     * @var string
     */
    private $company_id;

    /**
     * @var string
     */
    private $api_token;

    /**
     * Constructor
     */
    public function __construct() {
        $this->company_id = get_option('sr_company_id');
        $this->api_token = get_option('sr_api_token');
    }

        /**
     * Make GraphQL request
     * 
     * @param string $query GraphQL query
     * @param array $variables Query variables
     * @return array|WP_Error Response or error
     */
    public function graphql_request($query, $variables = []) {
        $company_id = get_option('sr_company_id');
        $endpoint = $this->api_url . $company_id . '/CRM';

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . get_option('sr_api_token'),
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'query' => $query,
                'variables' => $variables
            ]),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            error_log('API Error: ' . $response->get_error_message());
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['errors'])) {
            error_log('API Error: ' . json_encode($body['errors']));
            return new WP_Error('graphql_error', $body['errors'][0]['message']);
        }

        return $body['data'];
    }

    /**
     * Create order in SalesRender
     * 
     * @param array $order_data Order data
     * @return array|WP_Error Response or error
     */
    public function create_order($order_data) {
        $mutation = '
            mutation ($input: AddOrderInput!) {
                orderMutation {
                    addOrder(input: $input) {
                        id
                        status {
                            id
                            name
                        }
                        createdAt
                    }
                }
            }
        ';
    
        return $this->graphql_request($mutation, ['input' => $order_data]);
    }

    /**
     * Update order status
     * 
     * @param string $order_id Order ID
     * @param string $status_id New status ID
     * @return array|WP_Error Response or error
     */
    public function update_order_status($order_id, $status_id) {
        $mutation = '
            mutation ($input: UpdateOrderStatusInput!) {
                orderMutation {
                    updateStatus(input: $input) {
                        id
                        status
                    }
                }
            }
        ';

        $variables = array(
            'input' => array(
                'id' => $order_id,
                'statusId' => $status_id
            )
        );

        return $this->graphql_request($mutation, $variables);
    }



    /**
     * Format order data for SalesRender
     * 
     * @param array $data Raw order data
     * @return array Formatted order data
     */
    public function format_order_data($data) {
        return array(
            'statusId' => get_option('sr_default_status_id', '19'),
            'projectId' => get_option('sr_project_id'),
            'orderData' => array(
                'humanNameFields' => array(
                    array(
                        'field' => 'name1',
                        'value' => array(
                            'firstName' => $data['first_name'],
                            'lastName' => $data['last_name']
                        )
                    )
                ),
                'phoneFields' => array(
                    array(
                        'field' => 'phone',
                        'value' => $data['phone']
                    )
                ),
                'addressFields' => array(
                    array(
                        'field' => 'adress',
                        'value' => array(
                            'postcode' => $data['postal_code'],
                            'region' => $data['country'],
                            'city' => $data['city'],
                            'address_1' => $data['address']
                        )
                    )
                )
            ),
            'cart' => array(
                'items' => array(
                    array(
                        'itemId' => $data['package_id'],
                        'quantity' => $data['quantity'],
                        'variation' => 1
                    )
                )
            ),
            'source' => array(
                'refererUri' => $data['referer'] ?? '',
                'ip' => $data['ip'] ?? $_SERVER['REMOTE_ADDR']
            )
        );
    }
}