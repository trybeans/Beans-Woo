<?php

namespace BeansWoo\Front\Liana;

use BeansWoo\Helper;

include_once('observer.php');

class ProductObserver
{
    public static $display;
    public static $redemption;

    public static $pay_with_point_product_ids;

    public static function init($display)
    {

        self::$display = $display;
        self::$redemption = $display['redemption'];

        if (empty(self::$redemption['reward_exclusive_product_cms_ids'])) {
            return;
        }
        self::$pay_with_point_product_ids = array_map(function ($value) {
            return (int)$value;
        }, self::$redemption['reward_exclusive_product_cms_ids']);

        add_action('wp_loaded', array(__CLASS__, 'applyPayWithPointRedemption'), 99, 1);

        add_filter('woocommerce_is_purchasable', array(__CLASS__, 'isPurchasableProduct'), 99, 2);
        add_filter('woocommerce_is_sold_individually', array(__CLASS__, 'isSoldIndividuallyProduct'), 99, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', array(__CLASS__, 'addToCartButtonText'), 99, 2);
        add_filter('woocommerce_add_to_cart_validation', array(__CLASS__, 'addToCartValidation'), 99, 2);
    }

    public static function addToCartButtonText($button_text, $product)
    {

        if (in_array($product->get_id(), self::$pay_with_point_product_ids)) {
            # todo update translation after merging 3.2.3/update-translation
            $button_text = __("Pay with " . $product->get_price() * self::$display['beans_rate'] . " " . self::$display['beans_name'], "woocommerce");
        }
        return $button_text;
    }

    public static function isSoldIndividuallyProduct($is_sol_individually, $product)
    {
        if (in_array($product->get_id(), self::$pay_with_point_product_ids)) {
            $is_sol_individually = true;
        }
        return $is_sol_individually;
    }

    public static function isPurchasableProduct($is_purchasable, $product)
    {
        $product_id = $product->get_id();
        $product_in_cart_ids = array();
        $product_with_points = array();

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product_in_cart_ids[] = $cart_item['product_id'];
            if (in_array($cart_item['product_id'], self::$pay_with_point_product_ids)) {
                $product_with_points[] = $cart_item['product_id'];
            }
        }

        if (in_array($product_id, self::$pay_with_point_product_ids) && !in_array($product_id, $product_in_cart_ids) && count($product_with_points) != 0) {
            $is_purchasable = false;
        }

        return $is_purchasable;
    }

    public static function addToCartValidation($result, $product_id)
    {
        if (!is_user_logged_in()) {
            # todo update translation after merging 3.2.3/update-translation
            wc_add_notice(__('Join our rewards program to get this product.', 'woocommerce'), 'error');
            $result = false;
        } else if (is_user_logged_in() && isset($_SESSION['liana_account']) && in_array($product_id, self::$pay_with_point_product_ids)) {
            $product = wc_get_product($product_id);
            Observer::updateSession();
            $account = $_SESSION['liana_account'];

            if ($product->get_price() * self::$display['beans_rate'] > $account['beans']) {
                # todo update translation after merging 3.2.3/update-translation
                wc_add_notice(__('You don\'t have enough ' . self::$display["beans_name"] . ' to purchase ' . $product->get_title() . '', 'woocommerce'), 'error');
                $result = false;
            }
        }

        return $result;
    }

    public static function applyPayWithPointRedemption()
    {
        $cart = Helper::getCart();

        if (!isset($cart) || !isset($_SESSION['liana_account'])) return;

        $amount = 0;
        $product_ids = array();
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (in_array($cart_item['product_id'], self::$pay_with_point_product_ids)) {
                $product_ids[$cart_item_key] = $cart_item['product_id'];
                $amount += $cart_item['data']->get_price() * $cart_item['quantity'];
            }
        }
        $beans_amount = $amount * self::$display['beans_rate'];

        $account = $_SESSION['liana_account'];

        if (count($product_ids) != 0 && $beans_amount > $account['beans']){
            if ($cart->has_discount(BEANS_LIANA_COUPON_UID)){
                $cart->remove_coupon(BEANS_LIANA_COUPON_UID);
            }

            foreach ($product_ids as $cart_item_key => $pay_with_point_product_id){
                $cart->remove_cart_item($cart_item_key);
            }
            return ;
        }

        if (count($product_ids) != 0 && !$cart->has_discount(BEANS_LIANA_COUPON_UID)) {
            Observer::cancelRedemption();
            Observer::updateSession();
            if ($beans_amount > $account['beans']) {
                # todo update translation after merging 3.2.3/update-translation
                wc_add_notice(__('You don\'t have enough ' . self::$display["beans_name"], 'woocommerce'), 'error');
                return;
            }
            $_SESSION['liana_debit'] = array(
                'beans' => $amount * self::$display['beans_rate'],
                'value' => $amount
            );

            $cart->apply_coupon(BEANS_LIANA_COUPON_UID);
        }
    }
}
