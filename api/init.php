<?php

namespace BeansWoo\API;

include_once ('api-beans-rest-woocommerce.php');

defined('ABSPATH') or die;

class Main {
    public static function init() {
        BeansRestWoocommerce::init();
    }
}
