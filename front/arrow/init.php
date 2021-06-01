<?php


namespace BeansWoo\Front\Arrow;

defined('ABSPATH') or die;

include_once('block.php');

class Main {
	public static function init(){
        Block::init();
	}
}