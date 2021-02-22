<?php


namespace BeansWoo\Front;

defined('ABSPATH') or die;

use BeansWoo\Front\Liana\Observer;
use BeansWoo\Front\Liana\Block as BlockLiana;
use BeansWoo\Helper;


class Block
{
    static $card;

    public static function init()
    {
        add_action('wp_head', array(__CLASS__, 'render_head'), 10, 1);

        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'), 10, 1);

        if (current_user_can('administrator') and is_null(Helper::getConfig('is_admin_account')) ) {
            do_action('woocommerce_new_customer', get_current_user_id());  // force customer webhook for admin
            Helper::setConfig('is_admin_account', true);
        }

        add_action('wp_footer', array(__CLASS__, 'render_footer'), 10, 1);

    }

    public static function render_head()
    {
        ?>
        <script>
            window.beans_cart_page = "<?php echo wc_get_cart_url(); ?>";
            window.beans_checkout_page = "<?php echo wc_get_checkout_url(); ?>";
            window.beans_current_page = "<?php echo Helper::getCurrentPage(); ?>";
            window.beans_shop_page = "<?php echo wc_get_page_permalink( 'shop' ); ?>";
            window.beans_login_page = "<?php echo wc_get_page_permalink('myaccount'); ?>";
            window.beans_register_page = "<?php echo wc_get_page_permalink('myaccount'); ?>";
            window.beans_cjs_id = "<?php echo is_user_logged_in() ? wp_get_current_user()->ID : ''; ?>";
            window.beans_reward_page = "<?php echo get_permalink(Helper::getConfig('liana_page')); ?>";
            window.beans_referral_page = "<?php echo get_permalink(Helper::getConfig('bamboo_page')); ?>";
            window.beans_cjs_email = "<?php echo is_user_logged_in() ? wp_get_current_user()->user_email : ''; ?>";
        </script>
        <?php
    }

    public static function enqueue_scripts()
    {
        wp_enqueue_script(
            'beans-ultimate-js',
            'https://'. Helper::getDomain("STATIC").
            '/lib/ultimate/3.2/js/woocommerce/ultimate.beans.js?radix=woocommerce&id='.Helper::getConfig('card'),
            array(),
            time(),
            false
        );

        wp_enqueue_style('beans-style', plugins_url('assets/css/beans.css', BEANS_PLUGIN_FILENAME));
    }

    public static function render_footer()
    {
        $token = array();
        $debit = array();

        if (is_user_logged_in() and !isset($_SESSION["liana_account"])) {
            Observer::customerRegister(get_current_user_id());
        }

        if (isset($_SESSION['liana_token'])) $token = $_SESSION['liana_token'];
        if (isset($_SESSION['liana_debit'])) $debit = $_SESSION['liana_debit'];

        ?>

        <script>
            window.bamboo_init_data = {
                currentPage: window.beans_current_page,
                loginPage: window.beans_login_page,
                registerPage: window.beans_register_page,
                rewardPage: window.beans_reward_page,
                aboutPage: window.beans_referral_page,
            };

            window.arrow_init_data = {
                currentPage: window.beans_current_page,
                loginPage: window.beans_login_page,
                registerPage: window.beans_register_page,
            };

            window.liana_init_data = {
                currentPage: window.beans_current_page,
                loginPage: window.beans_login_page,
                registerPage: window.beans_register_page,
                accountToken: "<?php  echo isset($token['key']) ? $token['key'] : ''; ?>",
                aboutPage: window.beans_reward_page,
                cartPage: window.beans_cart_page,
                debit: {
                    <?php
                    Helper::getAccountData($debit, 'beans', 0);
                    Helper::getAccountData($debit, 'message', '');
                    echo "uid: '" . BEANS_LIANA_COUPON_UID . "'";?>
                },
            };

            if (window.liana_init_data.debit.beans === "") {
                delete window.liana_init_data.debit;
            }
        </script>
            <?php BlockLiana::get_redeem_button_value(); ?>
        <script>
            window.Beans3.Liana.Radix.init();
            window.Beans3.Bamboo.Radix.init();
            window.Beans3.Poppy.Radix.init();
            window.Beans3.Snow.Radix.init();
            window.Beans3.Arrow.Radix.init();
        </script>
        <?php
    }
}