<?php

namespace BeansWoo\Front\Liana;

defined('ABSPATH') or die;

use BeansWoo\Helper;

class Observer {
    public static $display;
    public static $redemption;
    public static $i18n_strings;

    public static function init($display) {

        self::$display = $display;
        self::$redemption = $display['redemption'];
        self::$i18n_strings = self::$display['i18n_strings'];

        add_action( 'wp_logout', array( __CLASS__, 'clearSession' ), 10, 1 );
        add_action( 'wp_login', array( __CLASS__, 'customerLogin' ), 10, 2 );

        add_action( 'wp_loaded', array( __CLASS__, 'handleRedemptionForm' ), 30, 1 );

        add_filter( 'woocommerce_get_shop_coupon_data', array( __CLASS__, 'getCoupon' ), 10, 2 );

        add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'orderPlaced' ), 10, 1 );
        add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'orderPaid' ), 10, 3 );
    }

    public static function clearSession() {
        unset( $_SESSION['liana_token'] );
        unset( $_SESSION['liana_account'] );
        unset( $_SESSION['liana_coupon'] );
        unset( $_SESSION['liana_debit'] );
    }

    public static function createBeansAccount( $email, $firstname, $lastname ) {
        try {
            return Helper::API()->post( '/liana/account', array(
                'email'      => $email,
                'first_name' => $firstname,
                'last_name'  => $lastname,
            ) );
        } catch ( \Beans\Error\BaseError $e ) {
            Helper::log( 'Unable to create account: ' . $e->getMessage() );
        }

        return null;
    }

    public static function customerLogin( $user_login, $user ) {
        self::customerRegister( $user->ID );
    }

    public static function customerRegister( $user_id ) {

        $user_data = get_userdata( $user_id );

        $first_name = get_user_meta( $user_id, 'first_name', true );
        if ( ! $first_name && isset( $_POST['first_name'] ) ) {
            $first_name = $_POST['first_name'];
        }
        if ( ! $first_name ) {
            $first_name = get_user_meta( $user_id, 'billing_first_name', true );
        }
        if ( ! $first_name ) {
            $first_name = get_user_meta( $user_id, 'shipping_first_name', true );
        }

        $last_name = get_user_meta( $user_id, 'last_name', true );
        if ( ! $last_name && isset( $_POST['last_name'] ) ) {
            $last_name = $_POST['last_name'];
        }
        if ( ! $last_name ) {
            $first_name = get_user_meta( $user_id, 'billing_last_name', true );
        }
        if ( ! $last_name ) {
            $first_name = get_user_meta( $user_id, 'shipping_last_name', true );
        }

        $email = $user_data->user_email;

        if ( $email ) {
            $account                   = self::createBeansAccount( $email, $first_name, $last_name );
            $_SESSION['liana_account'] = $account;
            if ( $account ) {
                try{
                    $_SESSION['liana_token'] = Helper::API()->post( '/liana/auth/consumer_token', array('account' => $account['id']) );
                }catch ( \Beans\Error\BaseError $e ) {
                    Helper::log( 'Getting Auth Token Failed: ' . $e->getMessage() );
                }
            }
        }
    }

    public static function handleRedemptionForm() {


        if ( ! isset( $_POST['beans_action'] ) ) {
            return;
        }

        $action = $_POST['beans_action'];

        if ( $action == 'apply' ) {
            self::applyRedemption();
        } else {
            self::cancelRedemption();
        }
    }

    public static function getCoupon( $coupon, $coupon_code ) {

        if ( $coupon_code != BEANS_LIANA_COUPON_UID ) {
            return $coupon;
        }
        if ( ! isset( $_SESSION['liana_account'] ) ||
             ! isset( $_SESSION['liana_account']['beans'] )
        ) {
            return $coupon;
        }
        if ( ! isset( $_SESSION['liana_debit'] ) ||
             ! isset( $_SESSION['liana_debit']['beans'] )
        ) {
            return $coupon;
        }

        if ( isset( $_SESSION['liana_coupon'] ) &&
             $_SESSION['liana_coupon']
        ) {
            return $_SESSION['liana_coupon'];
        }

        $cart = Helper::getCart();
        if ( empty( $cart ) ) {
            return $coupon;
        }

//        $quantity = $_SESSION['liana_debit']['beans'];
        $amount = $_SESSION['liana_debit']['value'];

        $coupon_data = array();

        $coupon_data['id']                         = true;
        $coupon_data['individual_use']             = false;
        $coupon_data['product_ids']                = array();
        $coupon_data['exclude_product_ids']        = array();
        $coupon_data['usage_limit']                = null;
        $coupon_data['usage_limit_per_user']       = null;
        $coupon_data['limit_usage_to_x_items']     = null;
        $coupon_data['usage_count']                = null;
        $coupon_data['apply_before_tax']           = null;
        $coupon_data['discount_cart_tax']          = null;
        $coupon_data['free_shipping']              = false;
        $coupon_data['product_categories']         = array();
        $coupon_data['exclude_product_categories'] = array();
        $coupon_data['exclude_sale_items']         = false;
        $coupon_data['minimum_amount']             = null;
        $coupon_data['maximum_amount']             = null;
        $coupon_data['customer_email']             = null;

        $coupon_data['type']          = 'fixed_cart';
        $coupon_data['discount_type'] = 'fixed_cart';
        $coupon_data['amount']        = $amount;
        $coupon_data['coupon_amount'] = $amount;
        $coupon_data['expiry_date']   = strtotime( '+1 day', time() );

        $_SESSION['liana_coupon'] = $coupon_data;

        return $coupon_data;
    }

    public static function applyRedemption() {

        if ( ! isset( $_SESSION['liana_account'] ) ) {
            return;
        }

        self::cancelRedemption();
        self::updateSession();

        $account = $_SESSION['liana_account'];

        $cart = Helper::getCart();

        $max_amount = $cart->subtotal;
        if ( isset( self::$redemption ) && isset( self::$redemption['min_beans'] ) &&
            isset( self::$redemption['max_percentage']) ) {
            $min_beans =  self::$redemption['min_beans'];
            if ($account['beans']  < $min_beans){
                wc_add_notice( Helper::replaceTags(
                    self::$i18n_strings['redemption']['condition_minimum_points'],
                    array(
                        'quantity' => $min_beans,
                        "beans_name" => self::$display['beans_name'],
                    )) , 'notice' );
                return;
            }

            $percent_discount =  self::$redemption['max_percentage'];
            if ( $percent_discount < 100) {
                $max_amount = ( 1.0 * $cart->subtotal * $percent_discount ) / 100;
                if($max_amount < $account['beans_value']){
                    wc_add_notice( Helper::replaceTags(
                        self::$i18n_strings['redemption']['condition_maximum_discount'],
                        array(
                            'max_discount' => $percent_discount,
                        )), 'notice' );
                }
            }
        }

        $amount = min( $max_amount, $account['beans_value'] );
        $amount = sprintf( '%0.2f', $amount );

        $_SESSION['liana_debit'] = array(
            'beans' => $amount * self::$display['beans_rate'],
            'value' => $amount
        );
        $cart->apply_coupon( BEANS_LIANA_COUPON_UID );
    }

    public static function cancelRedemption() {

        Helper::getCart()->remove_coupon( BEANS_LIANA_COUPON_UID );

        unset( $_SESSION['liana_coupon'] );
        unset( $_SESSION['liana_debit'] );
    }

    public static function orderPlaced( $order_id ) {
        $order = new \WC_Order( $order_id );

        $account = null;

        if ( isset( $_SESSION['liana_account'] ) ) {
            $account = $_SESSION['liana_account'];
        }

        $coupon_codes = $order->get_coupon_codes();

        foreach ( $coupon_codes as $code ) {

            if ( $code === BEANS_LIANA_COUPON_UID ) {

                if ( ! $account ) {
                    throw new \Exception( 'Trying to redeem beans without beans account.' );
                }

                $coupon = new \WC_Coupon( $code );
                $coupon->get_amount();
                $amount     = (double) $coupon->get_amount();
                $amount     = sprintf( '%0.2f', $amount );
                $amount_str = sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $amount );

                $data = array(
                    'quantity'    => $amount,
                    'rule'        => strtoupper( get_woocommerce_currency() ),
                    'account'     => $account['id'],
                    'description' => "Debited for a $amount_str discount",
                    'uid'         => 'wc_' . $order->get_id() . '_' . $order->get_order_key(),
                    'commit'      => true
                );

                try {
                    $debit = Helper::API()->post( '/liana/debit', $data );
                } catch ( \Beans\Error\BaseError $e ) {
                    if ( $e->getCode() != 409 ) {
                        Helper::log( 'Debiting failed: ' . $e->getMessage() );
                        throw new \Exception( 'Beans debit failed: ' . $e->getMessage() );
                    }
                }

            }
        }

        self::cancelRedemption();
        self::updateSession();
    }

    public static function orderPaid( $order_id, $old_status, $new_status ) {
        $order   = new \WC_Order( $order_id );
        $account = null;
        $email = $order->get_billing_email();
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        try {
            $account = Helper::API()->get( '/liana/account/' . $email );
        } catch ( \Beans\Error\ValidationError $e ) {
            if ( $e->getCode() == 404 && $order->customer_user ) {
                $account = self::createBeansAccount( $email, $first_name, $last_name );
            } else {
                Helper::log( 'Looking for Beans account for crediting failed with message: ' . $e->getMessage() );
            }
        } catch ( \Beans\Error\BaseError $e ) {
            Helper::log( 'Looking for Beans account for crediting failed with message: ' . $e->getMessage() );
        }

        if ( ! $account ) {
            return;
        }

        $total = $order->get_total() - $order->get_shipping_total();

        if ( $new_status == 'cancelled' ) {
            $order_key = 'wc_' . $order->get_id() . '_' . $order->order_key;
            try {
                Helper::API()->post( "/liana/debit/$order_key/cancel" );
            } catch ( \Beans\Error\BaseError $e ) {
                Helper::log( 'Cancelling debit failed with message: ' . $e->getMessage() );
            }
            try {
                Helper::API()->post( "/liana/credit/$order_key/cancel" );
            } catch ( \Beans\Error\BaseError $e ) {
                Helper::log( 'Cancelling credit failed with message: ' . $e->getMessage() );
            }
        }
        self::updateSession();
    }

    public static function updateSession() {
        $account = null;
        if ( ! empty( $_SESSION['liana_account'] ) ) {
            $account = $_SESSION['liana_account'];
        }
        if ( ! $account ) {
            return;
        }
        try {
            $_SESSION['liana_account'] = Helper::API()->get( 'liana/account/' . $account['id'] );
        } catch ( \Beans\Error\BaseError $e ) {
            Helper::log( 'Unable to get account: ' . $e->getMessage() );
        }
    }

}
