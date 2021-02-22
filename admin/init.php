<?php

namespace BeansWoo\Admin;

defined('ABSPATH') or die;

define( 'BEANS_WOO_BASE_MENU_SLUG', 'beans-woo' );
define( 'BEANS_WOO_API_ENDPOINT', get_site_url().'/wp-json/wc/v3/' );
define( 'BEANS_WOO_API_AUTH_ENDPOINT', get_site_url().'/wc-auth/v1/authorize/' );

include_once( "observer.php" );

include_once( "connector/abstract-connector.php" );
include_once ("connector/liana-connector.php");
include_once ("connector/bamboo-connector.php");
include_once ("connector/poppy-connector.php");
include_once ("connector/ultimate-connector.php");
include_once ("connector/snow-connector.php");
include_once ("connector/lotus-connector.php");
include_once ("connector/foxx-connector.php");
include_once ("connector/arrow-connector.php");

use BeansWoo\Admin\Connector\ArrowConnector;
use BeansWoo\Admin\Connector\BambooConnector;
use BeansWoo\Admin\Connector\LianaConnector;
use BeansWoo\Admin\Connector\PoppyConnector;
use BeansWoo\Admin\Connector\SnowConnector;
use BeansWoo\Admin\Connector\FoxxConnector;
use BeansWoo\Admin\Connector\UltimateConnector;

class Main {

    public static function init() {

    	LianaConnector::init();
    	BambooConnector::init();
    	PoppyConnector::init();
        SnowConnector::init();
        FoxxConnector::init();
        ArrowConnector::init();
        UltimateConnector::init();
        Observer::init();
    }
}
