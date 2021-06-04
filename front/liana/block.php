<?php

namespace BeansWoo\Front\Liana;

defined('ABSPATH') or die;

use BeansWoo\Helper;

class Block {

    public static function init(){

        add_filter('the_content',                                   array(__CLASS__, 'render_page'),     10, 1);
        add_action('woocommerce_after_cart_totals',                 array(__CLASS__, 'render_cart'),     10, 1);

        add_filter('woocommerce_add_to_cart_fragments',             array(__CLASS__, 'render_cart_fragment'), 15, 1 );

	    if (get_option('beans-liana-display-redemption-checkout')){
            add_action('woocommerce_review_order_after_payment',      array(__CLASS__, 'render_cart'),     15, 1);
        }
    }

    public static function render_cart_fragment( $fragments ) {
        $cart  = Helper::getCart();
        ob_start();
        if(count($cart->get_cart()) == 0){
            Observer::cancel_redemption();
            ?>
            <script>
                delete window.liana_init_data.debit
            </script>
            <?php
        }
        self::render_cart();
        ?>
        <script>
            window.Beans3.Liana.Radix.init();
        </script>
        <?php
        if ($fragments){
            self::get_redeem_button_value();
        }
         $fragments['div.beans-cart'] = ob_get_clean();
         return $fragments;
    }

    public static function render_cart(){
        $cart_subtotal  = Helper::getCart()->cart_contents_total;

        ?>
        <div id="beans-cart-redeem-button" class="beans-cart" beans-btn-class="checkout-button button" beans-cart_total="<?php echo $cart_subtotal; ?>"></div>
        <?php
    }

    public static function render_page($content, $vars=null){
        if (strpos($content,'[beans_page]') !== false && Helper::isSetupApp('liana')) {
            ob_start();
            include(dirname(__FILE__) . '/html-page.php');
            $page = ob_get_clean();
            $content = str_replace('[beans_page]', $page, $content);
        }
        return $content;
    }

    public static function get_redeem_button_value(){
        ?>
        <script>
            <?php if (Helper::getCart()->cart_contents_count != 0): ?>
            window.Beans3.Liana.storage.cart = {
                item_count: "<?php echo Helper::getCart()->cart_contents_count; ?>",
                // to avoid the decimal numbers for the points.
                total_price: "<?php echo Helper::getCart()->subtotal * 100; ?>", // DON'T TOUCH
            };
            <?php endif; ?>
        </script>
        <?php
    }

}