<?php

namespace AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Filters;

class AcceptCoinPaymentGatewayFilters
{

    private $acceptCoinPaymentId;

    /**
     * @param $acceptCoinPaymentId
     */
    public function __construct($acceptCoinPaymentId)
    {
        $this->acceptCoinPaymentId = $acceptCoinPaymentId;
    }

    /**
     * @param $button
     * @return mixed|string
     */
    public function removePlaceOrderButton($button)
    {
        $targeted_payments_methods = [$this->acceptCoinPaymentId];
        $chosen_payment_method = WC()->session->get('chosen_payment_method');

        if (in_array($chosen_payment_method, $targeted_payments_methods) && !is_wc_endpoint_url()) {
            $button = '';
        }

        return $button;
    }
}