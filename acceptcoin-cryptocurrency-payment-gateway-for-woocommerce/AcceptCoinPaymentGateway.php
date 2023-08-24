<?php

namespace AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce;

use AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\API\API;
use AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Filters\AcceptCoinPaymentGatewayFilters;
use AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Webhook\AcceptCoinPaymentGatewayWebhook;
use Exception;
use Throwable;
use WC_Payment_Gateway;

class AcceptCoinPaymentGateway extends WC_Payment_Gateway
{
    /**
     * @var
     */
    private $project_id;

    /**
     * @var
     */
    private $project_secret;

    /**
     * @var
     */
    private $return_url_success;

    /**
     * @var
     */
    private $return_url_fail;

    /**
     * AcceptcoinPaymentGateway constructor
     */
    public function __construct()
    {
        $this->id = 'acceptcoin';
        $this->icon = 'https://acceptcoin.io/assets/images/logo50.png';
        $this->title = 'Acceptcoin';
        $this->description = 'Pay with cryptocurrency Acceptcoin';
        $this->has_fields = true;
        $this->method_title = 'Acceptcoin';
        $this->method_description = 'Please add ID and Secret ID from your project settings';

        $this->init_form_fields();

        $this->init_settings();

        $this->project_id = $this->get_option('project_id');
        $this->project_secret = $this->get_option('project_secret');
        $this->return_url_success = $this->get_option('return_url_success');
        $this->return_url_fail = $this->get_option('return_url_fail');
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        wp_enqueue_style('stylePayment', plugins_url('assets/css/style.css', __FILE__));

        if (is_checkout()) {
            wp_enqueue_script('scriptPayment', plugins_url('assets/js/checkout.js', __FILE__), array('jquery'), null);
        }

        add_filter('woocommerce_order_button_html', [new AcceptCoinPaymentGatewayFilters($this->id), 'removePlaceOrderButton']);

        add_action('woocommerce_api_acceptcoin', [new AcceptCoinPaymentGatewayWebhook($this->project_secret), 'webhook']);
    }

    /**
     * Plugin options
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'project_id'         => [
                'title' => 'Project ID',
                'type'  => 'text'
            ],
            'project_secret'     => [
                'title' => 'Project Secret ID',
                'type'  => 'text'
            ],
            'return_url_success' => [
                'title'       => 'Successful status URL',
                'type'        => 'text',
                'description' => "Redirect URL after successful payment",
            ],
            'return_url_fail'    => [
                'title'       => 'Failed status URL',
                'type'        => 'text',
                'description' => "Redirect URL after failed payment",

            ]
        ];
    }

    /**
     * We're showing the payments fields here
     */
    public function payment_fields()
    {
        $acceptcoinIframeLink = WC()->session->get('acceptcoinIframeLink');

        if (!$acceptcoinIframeLink) {
            echo apply_filters('woocommerce_order_accept_coin_frame', '<div id="acceptcoin-frame" class="frame-body"></div>');
            echo "<p id='acceptcoin-description-title' class='description-title'>" . esc_html($this->description) . "</p>";
            echo "<button id='accept-coin-pay-button' class='pay-btn'>Pay</button>";
        }

        if ($acceptcoinIframeLink) {
            WC()->session->set('acceptcoinIframeLink', null);

            echo apply_filters('woocommerce_order_accept_coin_frame', '<div id="acceptcoin-frame" class="frame-body"><iframe class="iframe" sandbox="allow-top-navigation allow-scripts allow-same-origin" src=' . esc_url($acceptcoinIframeLink) . '></iframe></div>');
        }
    }

    /**
     * We're processing the payments here
     * @throws Exception
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $order->set_status('pending');

        try {
            $link = API::createPayment(
                $order_id,
                $this->project_id,
                $this->project_secret,
                $this->id,
                $this->return_url_success,
                $this->return_url_fail
            );

            WC()->session->set('acceptcoinIframeLink', $link);
            WC()->session->set('acceptcoinOrderPlaced', true);

            wc_add_notice('Order successfully created.');

        } catch (Throwable $exception) {
            wc_add_notice($exception->getMessage(), 'error');

            return [
                'reload'  => false,
                'refresh' => false,
                'result'  => 'failure'
            ];
        }

        return [
            'reload'   => false,
            'refresh'  => false,
            'result'   => 'success',
            'messages' => ' '
        ];
    }
}