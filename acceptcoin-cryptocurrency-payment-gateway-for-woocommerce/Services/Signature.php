<?php


namespace AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Services;

/**
 * Class Signature
 * @package App\Utils
 */
class Signature
{
    /**
     * @param string $data
     * @param string $signature
     * @param string $key
     * @return bool
     */
    public static function check(string $data, string $signature, string $key)
    {
        return base64_encode(hash_hmac('sha256', $data, $key, true)) === $signature;
    }
}