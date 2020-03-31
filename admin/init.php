<?php

namespace BeansWoo\Admin;

defined('ABSPATH') or die;

define( 'BEANS_WOO_BASE_MENU_SLUG', 'beans-woo' );
define( 'BEANS_WOO_API_ENDPOINT', get_site_url().'/wp-json/wc/v2/' );
define( 'BEANS_WOO_API_AUTH_ENDPOINT', get_site_url().'/wc-auth/v1/authorize/' );

include_once( "observer.php" );

include_once ("connector/ultimate-connector.php");

use BeansWoo\Admin\Connector\UltimateConnector;

class Main {

    public static function init() {

    	UltimateConnector::init();
        Observer::init();
    }
}
