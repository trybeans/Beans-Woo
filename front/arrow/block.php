<?php


namespace BeansWoo\Front\Arrow;

defined('ABSPATH') or die;

class Block {

    public static function init(){
        add_action("woocommerce_login_form_start",               array(__CLASS__, 'render_button_container'), 10);
    }

    public static function render_button_container(){
        ?>
        <div class="beans-arrow"></div>
        <?php
    }

}