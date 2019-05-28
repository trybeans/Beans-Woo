<?php

namespace BeansWoo\Liana;

use BeansWoo\Helper;

class Block {

    public static function init(){
        add_filter('wp_enqueue_scripts',                             array(__CLASS__, 'enqueue_scripts'), 10, 1);
        add_filter('wp_head',                                        array(__CLASS__, 'render_head'),     10, 1);
        add_filter('woocommerce_after_cart_table',                   array(__CLASS__, 'render_cart'),     10, 1);
        add_filter('woocommerce_before_checkout_form',               array(__CLASS__, 'render_cart'),     15, 1);
        add_filter('woocommerce_register_form_start',                array(__CLASS__, 'render_register'), 15, 1);
        add_filter('wp_footer',                                      array(__CLASS__, 'render_init'),     10, 1);
        add_filter('the_content',                                    array(__CLASS__, 'render_page'),     10, 1);
    }

    public static function enqueue_scripts(){
        // wp_enqueue_script('beans-script', 'https://trybeans.s3.amazonaws.com/static-v3/liana/lib/3.0/js/woocommerce/liana.beans.js');
        wp_enqueue_style( 'beans-style', plugins_url( 'assets/beans.css' , BEANS_PLUGIN_FILENAME ));
    }

    public static function render_head(){
        /* Issue with wp_enqueue_script not always loading, prefered using wp_head for a quick fix
           Also the Beans script does not have any dependency so there is no that much drawback on using wp_head
        */
        ?>
          <script src='https://trybeans.s3.amazonaws.com/static-v3/liana/lib/3.0/js/woocommerce/liana.beans.js' type="text/javascript"></script>
<!--          <script src='http://localhost:8002/assets/static/liana/lib/3.0/js/woocommerce/liana.beans.js' type="text/javascript"></script>-->
        <?php
    }

    public static function render_cart(){
        $cart_subtotal  = Helper::getCart()->cart_contents_total;

        ?>
        <div class="beans-cart" beans-btn-class="button" beans-cart_total="<?php echo $cart_subtotal; ?>"></div>
        <?php
    }

    public static function render_init($force=false){
        if (!$force && get_the_ID() === Helper::getConfig('page')) return;

        $account = array();
        $token = array();
        $debit = array();

        if (is_user_logged_in() and !isset($_SESSION['liana_account'])){
            Observer::customerRegister(get_current_user_id());
        }

        if(isset($_SESSION['liana_account'])) $account = $_SESSION['liana_account'];
        if(isset($_SESSION['liana_token'])) $token = $_SESSION['liana_token'];
        if(isset($_SESSION['liana_debit'])) $debit = $_SESSION['liana_debit'];

        ?>
        <div></div>
        <script>
            Beans3.Liana.Snow = Beans3.Liana.Snow || {
                toast: function () {}
            };
            var beans_init_data = {
                address: '<?php echo Helper::getConfig('card'); ?>',
                domainAPI: '<?php echo Helper::getDomain('API'); ?>',
                reward_page: '<?php echo get_permalink( Helper::getConfig('page') ); ?>',
                login_page: '<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>',
                account_token: '<?php  echo isset($token['key'])? $token['key'] : ''; ?>',
                account: {<?php Helper::getAccountData($account, 'id', '');  Helper::getAccountData($account, 'beans'); ?>}
            };
            Beans3.init(beans_init_data);
            Beans3.Orchard.register("liana", ['snow']).then(function () {
                Beans3.Liana.init(beans_init_data);
            });
            Beans3.Liana.Redemption = {
                <?php Helper::getAccountData($debit, 'beans', 0);  Helper::getAccountData($debit, 'message', ''); ?>
                apply: function(){Beans3.Liana.utils.formPost('', {beans_action: 'apply'});},
                cancel: function(){Beans3.Liana.utils.formPost('', {beans_action: 'cancel'});}
            };
        </script>
        <?php
    }

    public static function render_page($content, $vars=null){
        if (strpos($content,'[beans_page]') !== false) {
            ob_start();
            self::render_init(true);
            include( dirname( __FILE__ ) . '/block.page.php' );
            $page = ob_get_clean();
            $content = str_replace('[beans_page]', $page, $content);
        }
        return $content;
    }

    public static function render_register(){
       ?>
        <p class="form-row form-row-first">
            <label for="reg_first_name"><?php _e( 'First name', 'woocommerce' ); ?><span class="required">*</span></label>
            <input type="text" class="input-text" name="first_name" id="reg_first_name" value="<?php if ( ! empty( $_POST['first_name'] ) ) esc_attr_e( $_POST['first_name'] ); ?>" />
        </p>

        <p class="form-row form-row-last">
            <label for="reg_last_name"><?php _e( 'Last name', 'woocommerce' ); ?><span class="required">*</span></label>
            <input type="text" class="input-text" name="last_name" id="reg_last_name" value="<?php if ( ! empty( $_POST['last_name'] ) ) esc_attr_e( $_POST['last_name'] ); ?>" />
        </p>
        <?php
    }

}