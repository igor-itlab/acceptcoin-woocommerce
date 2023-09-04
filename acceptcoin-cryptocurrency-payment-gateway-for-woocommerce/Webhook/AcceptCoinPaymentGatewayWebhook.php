<?php

namespace AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Webhook;


use AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\API\API;
use AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Services\ACUtils;
use AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Services\MailHelper;
use AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Services\Signature;
use Exception;
use Throwable;

class AcceptCoinPaymentGatewayWebhook
{
    public const RESOLUTION_STATUSES = [
        'PROCESSED'      => 'processing',
        'FAIL'           => 'failed',
        'PENDING'        => 'pending',
        'FROZEN_DUE_AML' => 'failed'
    ];

    public const EMAIL_STATUSES = [
        "FROZEN_DUE_AML"
    ];

    private $projectSecret;

    /**
     * @param $projectSecret
     */
    public function __construct($projectSecret)
    {
        $this->projectSecret = $projectSecret;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function webhook(): void
    {
        $body = file_get_contents("php://input");

        $response = json_decode($body, true);

        if (!isset($response['data'])) {
            return;
        }

        if (!is_array($response['data'])) {
            $response['data'] = json_decode($response['data'], true);
        }

        if (!isset($response['data']['referenceId'])) {
            return;
        }

        if (!Signature::check(
            wp_json_encode($response['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response['signature'],
            $this->projectSecret
        )) {
            return;
        }

        $referenceArray = explode('-', $response['data']['referenceId']);

        if (!isset($referenceArray[2])) {
            return;
        }

        $orderId = $referenceArray[2];

        $order = wc_get_order($orderId);

        if (!$order) {
            return;
        }

        if ($order->get_status() !== 'pending') {
            return;
        }

        if (isset(self::RESOLUTION_STATUSES[$response['data']['status']['value']])) {
            $order->update_status(self::RESOLUTION_STATUSES[$response['data']['status']['value']]);
        }

        if (in_array($response['data']['status']['value'], self::EMAIL_STATUSES)) {
            $emailContent = [
                "name"          => $order->get_billing_first_name(),
                "lastname"      => $order->get_billing_last_name(),
                "transactionId" => $response['data']['id'],
                "date"          => date("Y-m-d H:i:s", $response['data']['createdAt'])
            ];

            MailHelper::sendMessage(
                $order->get_billing_email(),
                get_option('woocommerce_email_from_address'),
                $response['data']['status']['value'],
                $emailContent
            );
        }


        if ($order->get_status() === self::RESOLUTION_STATUSES['PROCESSED']) {
            update_post_meta(
                $order->get_id(),
                API::PROCESSED_AMOUNT_NAME,
                ACUtils::getProcessedAmount($response['data'])
            );
        }
        echo var_export(123, true);
die();
        update_option('webhook_debug', $_POST);
    }

}