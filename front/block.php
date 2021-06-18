<?php


namespace BeansWoo\Front;

defined('ABSPATH') or die;

use BeansWoo\Front\Liana\Observer;
use BeansWoo\Helper;


class Block
{
    static $card;

    public static function init()
    {
        add_action('wp_head', array(__CLASS__, 'render_head'), 10, 1);

        add_action('woocommerce_register_form_start',   array(__CLASS__, 'render_register'));
        add_action( 'woocommerce_created_customer',     array(__CLASS__, 'register_save_name_fields') );
        add_filter( 'woocommerce_registration_errors',  array(__CLASS__, 'register_validate_name_fields'), 10, 3 );

        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'), 10, 1);
        $user_id = get_current_user_id();
        if (current_user_can('administrator') and !get_user_meta($user_id,   'beans_is_admin_account')) {
            do_action('woocommerce_new_customer', $user_id);  // force customer webhook for admin
            add_user_meta( $user_id, 'beans_is_admin_account', 'true', true );
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
            window.beans_reward_page = "<?php echo get_permalink(Helper::getConfig('liana_page')); ?>";
            window.beans_referral_page = "<?php echo get_permalink(Helper::getConfig('bamboo_page')); ?>";

            window.beans_cjs_id = "<?php echo is_user_logged_in() ? wp_get_current_user()->ID : ''; ?>";
            window.beans_cjs_email = "<?php echo is_user_logged_in() ? wp_get_current_user()->user_email : ''; ?>";
        </script>
        <?php
    }

    public static function enqueue_scripts()
    {
        wp_enqueue_script(
            'beans-ultimate-js',
            'https://'. Helper::getDomain("CDN").
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
            Observer::customer_register(get_current_user_id());
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

            <?php if (Helper::getCart()->cart_contents_count != 0): ?>
            window.Beans3.Liana.storage.cart = {
                item_count: "<?php echo Helper::getCart()->cart_contents_count; ?>",
                // to avoid the decimal numbers for the points.
                total_price: "<?php echo Helper::getCart()->subtotal * 100; ?>", // DON'T TOUCH
            };
            <?php endif; ?>

            window.Beans3.Liana.Radix.init();
            window.Beans3.Bamboo.Radix.init();
            window.Beans3.Poppy.Radix.init();
            window.Beans3.Snow.Radix.init();
            window.Beans3.Arrow.Radix.init();
        </script>
        <?php
    }

    public static function register_validate_name_fields( $errors, $username, $email ) {
        if ( isset( $_POST['first_name'] ) && empty( $_POST['first_name'] ) ) {
            $errors->add( 'first_name_error', __( '<strong>Error</strong>: First name is required!', 'woocommerce' ) );
        }
        if ( isset( $_POST['last_name'] ) && empty( $_POST['last_name'] ) ) {
            $errors->add( 'last_name_error', __( '<strong>Error</strong>: Last name is required!.', 'woocommerce' ) );
        }
        return $errors;
    }


    public static function register_save_name_fields( $customer_id ) {
        if ( isset( $_POST['first_name'] ) ) {
            update_user_meta( $customer_id, 'billing_first_name', sanitize_text_field( $_POST['first_name'] ) );
            update_user_meta( $customer_id, 'first_name', sanitize_text_field($_POST['first_name']) );
        }
        if ( isset( $_POST['last_name'] ) ) {
            update_user_meta( $customer_id, 'billing_last_name', sanitize_text_field( $_POST['last_name'] ) );
            update_user_meta( $customer_id, 'last_name', sanitize_text_field($_POST['last_name']) );
        }

    }

    public static function render_register(){
        ?>
        <p class="form-row form-row-first">
            <label for="reg_first_name"><?php _e( 'First name', 'woocommerce' ); ?><span class="required">*</span></label>
            <input type="text" class="input-text" name="first_name" id="reg_first_name"
                   value="<?php if ( ! empty( $_POST['first_name'] ) ) esc_attr_e( $_POST['first_name'] ); ?>" />
        </p>

        <p class="form-row form-row-last">
            <label for="reg_last_name"><?php _e( 'Last name', 'woocommerce' ); ?><span class="required">*</span></label>
            <input type="text" class="input-text" name="last_name" id="reg_last_name"
                   value="<?php if ( ! empty( $_POST['last_name'] ) ) esc_attr_e( $_POST['last_name'] ); ?>" />
        </p>
        <?php
    }

}