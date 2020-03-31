<?php


namespace BeansWoo\Front\Poppy;

use BeansWoo\Helper;

class Block {

    static public $app_name = 'poppy';
    static $card;

    public static function init(){
        self::$card = Helper::getCard( 'ultimate' );

        if (! isset(self::$card[self::$app_name])){
            return ;
        }

        self::$card = self::$card[self::$app_name];


        if ( empty( self::$card ) || !self::$card['is_active'] || ! Helper::isSetupApp(self::$app_name)) {
            return;
        }

        add_filter('wp_footer',         array(__CLASS__, 'render_init'), 10, 1);
	}


    public static function render_init(){
        ?>
        <script>
            window.Beans3.Poppy.init();
        </script>
        <?php
    }
}