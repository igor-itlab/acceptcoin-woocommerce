<?php

namespace AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\API;

use AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Services\JWT;
use Exception;

class API
{
    private const SUCCESS_CREATE_CODE = 201;

    public const PREFIX                 = "ACWC";
    public const PROJECT_ID_SYMBOLS_NUM = 6;
    public const ERROR_MESSAGE          = "Acceptcoin payment method is not available at this moment.";

    public const PROCESSED_AMOUNT_NAME = "wc_acceptcoin_processed_amount";

    private const ACC_DOMAIN = "https://7cf5-195-69-221-149.ngrok-free.app";
//    private const ACC_DOMAIN = "https://dev7.itlab-studio.com";
//    private const DOMAIN = "https://acceptcoin.io";


    /**
     * @param $orderId
     * @param $projectId
     * @param $projectSecret
     * @param $id
     * @param $returnUrlSuccess
     * @param $returnUrlFailed
     * @return mixed
     * @throws Exception
     */
    public static function createPayment(
        $orderId,
        $projectId,
        $projectSecret,
        $id,
        $returnUrlSuccess,
        $returnUrlFailed
    ): string
    {
        $order = wc_get_order($orderId);

        $referenceId = self::PREFIX . "-" . substr($projectId, 0, self::PROJECT_ID_SYMBOLS_NUM) . "-" . $orderId;

        $body = [
            "amount"      => $order->get_total(),
            "referenceId" => $referenceId,
            "callBackUrl" => filter_var("https://" . sanitize_text_field($_SERVER['HTTP_HOST']) . "/wc-api/" . sanitize_text_field($id), FILTER_SANITIZE_STRING, FILTER_SANITIZE_URL)
        ];

        if ($returnUrlSuccess) {
            $body ["returnUrlSuccess"] = $returnUrlSuccess;
        }

        if ($returnUrlFailed) {
            $body ["returnUrlFail"] = $returnUrlFailed;
        }

        $response = wp_remote_post(self::ACC_DOMAIN . "/api/iframe-invoices", [
                'method'  => 'POST',
                'headers' => [
                    "Accept"        => "application/json",
                    "Content-Type"  => "application/json",
                    "Authorization" => "JWS-AUTH-TOKEN " . JWT::createToken($projectId, $projectSecret)
                ],
                'body'    => json_encode($body)
            ]
        );

        if (!$response['body']) {
            throw new Exception(self::ERROR_MESSAGE);
        }

        $responseData = json_decode($response['body'], true);

        if ($response['response']['code'] !== self::SUCCESS_CREATE_CODE) {
            throw new Exception(self::ERROR_MESSAGE);
        }

        if (!isset($responseData['link'])) {
            throw new Exception(self::ERROR_MESSAGE);
        }

        return $responseData['link'];
    }
}