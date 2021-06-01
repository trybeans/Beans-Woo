<?php

namespace BeansWoo\Front\Liana;

defined('ABSPATH') or die;

use BeansWoo\Helper;

include_once('observer.php');
include_once('observer-product.php');
include_once('block.php');

define( 'BEANS_LIANA_COUPON_UID', 'redeem_points' );


class Main {

    public static function init() {
        Block::init();

        $display = Helper::getBeansObject('liana', 'display');
        if ( empty( $display ) || ! $display['is_active']) {
            return;
        }

        Observer::init($display);
        ProductObserver::init($display);
    }
}
