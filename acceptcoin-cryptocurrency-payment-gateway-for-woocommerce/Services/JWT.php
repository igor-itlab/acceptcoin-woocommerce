<?php

namespace AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\Services;


class JWT
{
    /**
     * @param string $projectId
     * @param string $secret
     * @return string
     */
    public static function createToken(string $projectId, string $secret): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $payload = json_encode([
            'iat' => time(),
            'exp' => time() + 3600,
            "projectId" => $projectId
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
}