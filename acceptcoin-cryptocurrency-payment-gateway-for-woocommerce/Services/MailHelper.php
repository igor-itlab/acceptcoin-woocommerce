<?php

namespace AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Services;

use Throwable;

class MailHelper
{
    public const TYPE_NEW = "NEW";
    public const TYPE_FROZEN_DUE_AML = "FROZEN_DUE_AML";

    /**
     * @param string $recipient
     * @param string $sender
     * @param string $status
     * @param array $emailContent
     * @return void
     */
    public static function sendMessage(string $recipient, string $sender, string $status, array $emailContent): void
    {
        try {
            $body = self::getEmailBody($status, $emailContent);

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
    private static function getEmailBody(string $type, array $emailContent): ?array
    {
        switch ($type) {
            case self::TYPE_FROZEN_DUE_AML:
            {
                return [
                    "subject" => "Dirty coins were identified through AML checks",
                    "body"    => '<div
                    style="display:block;width:100%;table-layout:fixed;font-size:13px;line-height:1.4;background:#135A30; text-align: center">
                    <div style="padding:10px 15px;display:inline-block;text-align: left">
                        <div style="padding:10px 15px;display:inline-block;">
                            <div style="width:100px;padding:0;vertical-align:middle">
                                <a
                                    href="https://acceptcoin.io"
                                    style="display:inline-block;vertical-align:middle;color:#333"
                                    target="_blank"
                                >
                                    <img
                                        height="32"
                                        src="https://acceptcoin.io/assets/images/logo-white.png"
                                        style="display:inline-block;vertical-align:middle;border:none"
                                        alt="Acceptcoin"
                                    >
                                </a>
                            </div>
                        </div>
                        <div
                            style="margin:0 auto;padding:35px 15px;background:#fff;font-size:13px;color:#333333;line-height:1.5;text-align: left;">
                            <p style="margin:0 0 10px">
                                Dear '. $emailContent['name'] .' ' . $emailContent['lastname'] .'
                            </p>
                            <p style="margin:0 0 10px;background-color:#F69E55;padding:15px;border-radius:25px">
                                <span
                                    style="width:20px;height:20px;background:#EA7B29;border-radius:50%;color:#ffffff;margin-right:5px;font-size:14px;text-align:center;display:inline-block;">!</span>
                                Your transaction '. $emailContent['transactionId'] .' from '. $emailContent['date'] .' was blocked
                            </p>
                            <p><b>To confirm the origin of funds, we ask that you fully answer the following questions:</b></p>
                            <p>
                                1. Through which platform did the funds come?
                                If possible, please provide screenshots from the wallet/sender platform\'s withdrawal history, as well as
                                links to both transactions on the explorer.
                            </p>
                            <p>
                            2. For what service were the funds received?
                                What was the transaction amount, as well as the date and time it was received?
                            </p>
                            <p>
                            3. Through which contact person does your client communicate with the sender of the funds?
                                If possible, please provide screenshots of your correspondence with the sender, where we can see
                                confirmation of the transfer of funds.
                            </p>
                
                            <p>Additionally, we ask that you provide the following materials:</p>
                            <ul>
                                <li>Photo of one of your documents (passport, ID card, or driver\'s license).</li>
                                <li>A selfie with this document and a sheet of paper on which today\'s date and signature will be
                                    handwritten.
                                </li>
                            </ul>
                
                            <p>Please carefully write down the answers to these questions and email to <a
                                href="">support@acceptcoin.io</a>
                
                            <p style="margin:0 0 10px;background-color: #6FA5D3;padding:15px;border-radius:25px">
                                <span
                                    style="width:20px;height:20px;background:#1890ff;border-radius:50%;color:#ffffff;margin-right:5px;font-size:14px;text-align:center;display:inline-block;">i</span>
                            Please, don’t answer this mail, send your answer only to
                                support@acceptcoin.io</p>
                            <p>
                            We appreciate you choosing us!
                            </p>
                            <div>
                                If you have any questions, please contact acceptcoin.io administration or write to us.
                            </div>
                        </div>
                    </div>
                </div>'
                ];
            }
            case self::TYPE_NEW:
            {
                return [
                    'subject' => "Payment created for " . $emailContent['vendorName'],
                    'body' => '
                    <div style="display:block;width:100%;table-layout:fixed;font-size:13px;line-height:1.4;background:#135A30; text-align: center">
                    <div style="padding:10px 15px;display:inline-block;text-align: left">
                        <div style="padding:10px 15px;display:inline-block;">
                            <div style="width:100px;padding:0;vertical-align:middle">
                                <a
                                    href="https://acceptcoin.io"
                                    style="display:inline-block;vertical-align:middle;color:#333"
                                    target="_blank"
                                >
                                    <img
                                        height="32"
                                        src="https://acceptcoin.io/assets/images/logo-white.png"
                                        style="display:inline-block;vertical-align:middle;border:none"
                                        alt="Acceptcoin"
                                    >
                                </a>
                            </div>
                        </div>
                
                        <div
                            style="table-layout:fixed;margin:0;padding:35px 15px;background:#fff;font-size:13px;color:#333333;line-height:1.5;">
                            <p style="margin:0 0 10px">
                                Hello ' . $emailContent['name'] . ' ' . $emailContent['lastname'] . '
                            </p>
                            <p>
                                Want to complete your payment for ' . $emailContent['amount'] . ' ' . $emailContent['currency'] . '?
                            </p>
                            <p>
                                To finish up, go back to ' . $emailContent['vendorName'] . ' payment page or use the button below.
                            </p>
                
                            <p style="width:fit-content;margin:0 auto 10px;padding:20px">
                                <a style="text-decoration:none;background-color:#016E3B;width:fit-content;border-radius:5px;padding:17px 80px;color:#fff"
                                   href="' . $emailContent['link'] . '" target="_blank">
                                    Pay
                                </a>
                            </p>
                            <p>
                                We appreciate you choosing us!
                            </p>
                            <div>
                                If you have any questions, please contact acceptcoin.io administration or write to us.
                            </div>
                        </div>
                    </div>
                </div>'
                ];
            }
            default:
            {
                return null;
            }
        }
    }
}