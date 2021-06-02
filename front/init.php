<?php
namespace BeansWoo\Front;

defined('ABSPATH') or die;

include_once('block.php');
include_once('liana/init.php');
include_once('bamboo/init.php');
include_once ('arrow/init.php');

use BeansWoo\Front\Liana\Main as LianaMain;
use BeansWoo\Front\Bamboo\Main as BambooMain;
use BeansWoo\Front\Arrow\Main as ArrowMain;

class Main {
    public static $card;
    public static function init(){
        Block::init();
        LianaMain::init();
        BambooMain::init();
        ArrowMain::init();
    }
}
