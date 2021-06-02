<?php


namespace BeansWoo\Front\Liana;


use BeansWoo\Helper;

class ProductCategoryObserver
{
    public static $display;
    public static $redemption;
    public static $i18n_strings;
    public static $category_names;
    public static $pay_with_point_categories_ids;

    public static function init($display)
    {
        self::$display = $display;
        self::$redemption = $display['redemption'];
        self::$i18n_strings = self::$display['i18n_strings'];

        if (empty(self::$redemption['exclusive_collection_cms_ids'])) {
            return;
        }
        self::$pay_with_point_categories_ids = array_map(function ($value) {
            return (int)$value;
        }, self::$redemption['exclusive_collection_cms_ids']);

        self::$category_names = array_map(function($category_id) {
            return get_term_by('id', $category_id, 'product_cat')->name;
        }, self::$pay_with_point_categories_ids);

        add_action('wp_loaded', array(__CLASS__, 'applyCategoryRedemption'), 99, 1);

    }

    public static function applyCategoryRedemption(){
        $cart = Helper::getCart();

        if (!isset($cart) || !isset($_SESSION['liana_account']) || ! isset( $_POST['beans_action'] )) return;

        $category_ids = array();

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $category_ids = array_merge($category_ids, $cart_item['data']->get_category_ids());
        }
        if(count(array_intersect($category_ids, self::$pay_with_point_categories_ids)) == 0){
            Observer::cancelRedemption();
            Observer::updateSession();
            wc_add_notice(
                self::$i18n_strings['redemption']['redeem_is_limited_to_collection'].
                ": ". implode(", ", self::$category_names),
                'notice'
            );
        }
        else{
            Observer::handleRedemptionForm();
        }
    }
}