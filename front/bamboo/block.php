<?php

namespace BeansWoo\Front\Bamboo;

defined('ABSPATH') or die;

class Block {

    public static function init(){
	    add_filter('the_content',                                   array(__CLASS__, 'render_page'),     10, 1);
    }

    public static function render_page($content, $vars=null){
        if (strpos($content,'[beans_referral_page]') !== false) {
            ob_start();
            include(dirname(__FILE__) . '/html-page.php');
            $page = ob_get_clean();
            $content = str_replace('[beans_referral_page]', $page, $content);
        }
        return $content;
    }
}
