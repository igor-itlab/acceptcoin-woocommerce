<?php

/**
 * @link              https://acceptcoin.io
 * @since             1.0.0
 * @package           Acceptcoin_Cryptocurrency_Payment_Gateway_for_WooCommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Acceptcoin
 * Plugin URI:        https://acceptcoin.io
 * Description:       Acceptcoin is an innovative integrated payment gateway for accepting cryptocurrencies as payment for the purchase of goods and services on the seller's website. Powered by IT Lab Studio.
 * Version:           1.0.0
 * Author:            Softile Limited
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       acceptcoin
 * Domain Path:       /languages
 *
 * WC requires at least: 3.5.0
 * WC tested up to: 6.0
 */

use AcceptCoin_Cryptocurrency_Payment_Gateway_for_WooCommerce\AcceptCoinPaymentGateway;

if (!defined('ABSPATH')) {
    exit;
}

require 'vendor/autoload.php';

add_action('plugins_loaded', 'accept_coin_init', 0);

function accept_coin_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    add_filter('woocommerce_payment_gateways', 'accept_coin_add_gateway_class');

    function accept_coin_add_gateway_class($gateways)
    {
        $gateways[] = new AcceptCoinPaymentGateway();
        return $gateways;
    }

    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'accept_coin_action_links');

    function accept_coin_action_links($links): array
    {
        $plugin_links = array('<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Settings', 'accept-coin') . '</a>',);

        return array_merge($plugin_links, $links);
    }

    add_action('wp_head', 'wc_clear_cart');

    function wc_clear_cart()
    {
        $acceptcoinOrderPlaced = WC()->session->get('acceptcoinOrderPlaced');

        if (wc_get_page_id('cart') == get_the_ID() || wc_get_page_id('checkout') == get_the_ID() || !$acceptcoinOrderPlaced) {
            return;
        }

        WC()->session->set('acceptcoinOrderPlaced', null);
        WC()->cart->empty_cart();
    }

    add_action('phpmailer_init', 'acc_mailing_data');

    /**
     * @param $phpmailer
     * @return void
     */
    function acc_mailing_data($phpmailer): void
    {
        $accOptions = get_option('woocommerce_acceptcoin_settings');

        $phpmailer->isSMTP();
        $phpmailer->SMTPAuth = true;
        $phpmailer->Host = get_option('smtp_host') ? get_option('smtp_host') : $accOptions['smtp_host'];
        $phpmailer->Port = get_option('smtp_port') ? get_option('smtp_port') : $accOptions['smtp_port'];
        $phpmailer->Username = get_option('smtp_username') ? get_option('smtp_username') : $accOptions['smtp_username'];
        $phpmailer->Password = get_option('smtp_password') ? get_option('smtp_password') : $accOptions['smtp_password'];
        $phpmailer->SMTPSecure = get_option('smtp_encryption') ?? $accOptions['smtp_encryption'];
    }
}
