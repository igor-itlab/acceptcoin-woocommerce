<?php

namespace AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Webhook;


use AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\API\API;
use AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Services\ACUtils;
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
        var_dump(json_encode($response['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        die();
        if (!is_array($response['data'])) {
            $response['data'] = json_decode($response['data'], true);
        }

        if (!isset($response['data']['referenceId'])) {
            return;
        }

        var_dump([Signature::check(
            wp_json_encode($response['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response['signature'],
            $this->projectSecret
        ),
                  wp_json_encode($response['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                  $response['signature'],
                  $this->projectSecret,
                  base64_encode(hash_hmac('sha256', wp_json_encode($response['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $this->projectSecret, true))
            ]);

        die();
        if (!Signature::check(
            wp_json_encode($response['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response['signature'],
            $this->projectSecret
        )) {
            return;
        }
        var_dump(1);
        die();
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
                "name"        => $order->get_billing_first_name(),
                "lastname"    => $order->get_billing_last_name(),
                "referenceId" => $response['data']['referenceId'],
                "amount"      => $response['data']['amount'],
                "currency"    => $response['data']['projectPaymentMethods']['paymentMethod']['currency']['asset']
            ];

            $this->sendMessage(
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
        var_dump(7);
        die();
        update_option('webhook_debug', $_POST);
    }

    /**
     * @param string $recipient
     * @param string $sender
     * @param string $status
     * @param array $emailContent
     * @return void
     */
    private function sendMessage(string $recipient, string $sender, string $status, array $emailContent): void
    {
        try {
            $body = $this->getEmailBody($status, $emailContent);

            if (!$body) {
                return;
            }

            $headers = [
                "From: <$sender>",
                'Content-Type: text/html; charset=UTF-8'
            ];

            wp_mail($recipient, $body['subject'], $body['body'], $headers);
        } catch (Throwable $exception) {
            echo var_export($exception->getMessage(), true);
        }

    }

    /**
     * @param string $type
     * @param array $emailContent
     * @return string[]|null
     */
    private function getEmailBody(string $type, array $emailContent): ?array
    {
        switch ($type) {
            case "FROZEN_DUE_AML":
            {
                return [
                    "subject" => "Dirty coins were identified through AML checks",
                    "body"    => "
                       <div>
                          <p>
                            Dear " . $emailContent['name'] . " " . $emailContent['lastname'] . ". Your transaction for " . $emailContent['amount'] . " " . $emailContent['currency'] . " was blocked. 
                            Transaction ID " . $emailContent['referenceId'] . ".
                          </p>
                          <p><b>To confirm the origin of funds, we ask that you fully answer the following questions:</b></p>
                          <ol>
                            <li>Through which platform did the funds come to you? If possible, please provide screenshots from the wallet/sender platform's withdrawal history, as well as links to both transactions on the explorer.</li>
                            <li>For what service did you receive the funds? - What was the transaction amount, as well as the date and time it was recieved?</li>
                            <li>Through which contact person did you communicate with the sender of the funds? If possible, please provide screenshots of your correspondence with the sender, where we can see confirmation of the transfer of funds.</li>
                          </ol>
  
                          <br>
                          <p>Additionally, we ask that you provide the following materials:</p>
                          <ul>
                            <li>Photo of one of your documents (passport, ID card or driver's license).</li>
                            <li>A selfie with this document and a sheet of paper on which today's date and signature will be handwritten.</li>
                          </ul>
                          <p><b>Please carefully write down the answers to these questions and email to support@acceptcoin.io</b></p>
                          <hr>
                          NOTE: <i>Please, don’t answer this mail, send your answer only to support@acceptcoin.io</i>.
                          </div>"
                ];
            }
            default:
            {
                return null;
            }
        }
    }

}