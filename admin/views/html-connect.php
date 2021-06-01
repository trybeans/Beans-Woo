<?php

defined('ABSPATH') or die;

use BeansWoo\Helper;
function plugin_version( $plugin_name = 'woocommerce' ) {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	$plugin_folder = get_plugins( "/$plugin_name" );

	return $plugin_folder["$plugin_name.php"]['Version'];
}

function get_supported_tag( $vs_is_supported ) {
	if ( $vs_is_supported ) {
		echo "&nbsp;&nbsp;<span>&#x2705</span>";
	} else {
		echo "&nbsp;&nbsp;<span>&#x274C</span>";
	}
}

function check_woo_api_v2_uri( &$http_status, &$content_type ) {
	$ch         = curl_init();
	$curlConfig = array(
		CURLOPT_URL            => BEANS_WOO_API_ENDPOINT,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST  => 'GET',
		CURLOPT_CONNECTTIMEOUT => 30,
		CURLOPT_TIMEOUT        => 80
	);
	curl_setopt_array( $ch, $curlConfig );
	curl_exec( $ch );
	$http_status  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	$content_type = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
	curl_close( $ch );

	return in_array($http_status, [200, 503]);
//    && strpos( $content_type, 'application/json' ) !== false
}

function check_woo_api_v2_auth( &$http_status, &$content_type ) {
	$ch         = curl_init();
	$curlConfig = array(
		CURLOPT_URL            => BEANS_WOO_API_AUTH_ENDPOINT,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST  => 'GET',
		CURLOPT_CONNECTTIMEOUT => 30,
		CURLOPT_TIMEOUT        => 80
	);
	curl_setopt_array( $ch, $curlConfig );
	curl_exec( $ch );
	$http_status  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	$content_type = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
	curl_close( $ch );

	return in_array($http_status, [401, 503]) && strpos( $content_type, 'text/html' ) !== false;
}

$woo_version           = plugin_version( 'woocommerce' );
$woo_version_supported = '3.9';
$woo_is_supported      = version_compare( $woo_version, $woo_version_supported ) >= 0;

global $wp_version;
$wp_version_supported = '5.0';
$wp_is_supported      = version_compare( $wp_version, $wp_version_supported ) >= 0;

$php_version           = phpversion();
$php_version_supported = '5.6.0';
$php_is_supported      = version_compare( $php_version, $php_version_supported ) >= 0;

$curl_is_supported = function_exists( 'curl_init' );

$json_is_supported = function_exists( 'json_decode' );

$permalink_is_supported = ! is_null( get_option( 'permalink_structure' ) );

$wp_permalink_is_supported = ! is_null(get_option('permalink_structure'));

$beans_is_supported = $woo_is_supported && $wp_is_supported && $php_is_supported &&
                      $curl_is_supported && $json_is_supported && $permalink_is_supported &&
                      $wp_permalink_is_supported;

$woo_api_v2_uri_http_status  = null;
$woo_api_v2_uri_content_type = null;
$woo_api_v2_uri_is_up        = $beans_is_supported ? check_woo_api_v2_uri( $woo_api_v2_uri_http_status, $woo_api_v2_uri_content_type ) : null;

$beans_is_supported = $woo_api_v2_uri_is_up;

$woo_api_v2_auth_http_status  = null;
$woo_api_v2_auth_content_type = null;
$woo_api_v2_auth_is_up        = $beans_is_supported ? check_woo_api_v2_auth( $woo_api_v2_auth_http_status, $woo_api_v2_auth_content_type ) : null;

$beans_is_supported = $woo_api_v2_auth_is_up;

$admin = wp_get_current_user();

$country_code = get_option('woocommerce_default_country');
if($country_code && strpos($country_code, ':') !== false){
	try {
		$country_parts = explode( ':', $country_code );
		$country_code = $country_parts[0];
	}catch(\Exception $e){}
}

$force = isset( $_GET['force_beans'] );

$connect = "https://". Helper::getDomain( 'CONNECT' ). "/radix/woocommerce/connect";
?>

<div class="beans-admin-container">
    <?php include "html-ultimate-onboarding.php"; ?>
</div>
<p>
    A complete suite to create a unified marketing experience for your online shop
    <a href="https://<?php echo Helper::getDomain('WWW');?>"  target="_blank">Learn more about Beans Ultimate</a>
</p>

<a href="javascript:void(0)" id="view-config">View configuration</a>
<ul class="wc-wizard-features" style="display: None" id="config-status">
    <h3> Configuration Checking </h3>
    <li>
        <p class="">
            Beans leverages <a href="https://docs.woocommerce.com/document/woocommerce-rest-api/"
                               target="_blank">WooCommerce
                REST API</a>
            to supercharge your online store with powerful features .
        </p>
    </li>
    <li>
        <p>
            <strong>WooCommerce Version</strong>: <?php echo $woo_version;
            get_supported_tag($woo_is_supported); ?>
        </p>
        <?php if (!$woo_is_supported): ?>
            <p class="beans-admin-check-warning">
                Please update your WooCommerce plugin:
                <a href="https://docs.woocommerce.com/document/how-to-update-woocommerce/" target="_blank">
                    How to update WooCommerce
                </a>
            </p>
        <?php endif; ?>
    </li>
    <li>
        <p>
            <strong>Wordpress Version</strong>: <?php echo $wp_version; get_supported_tag($wp_is_supported); ?>
        </p>
        <?php if (!$wp_is_supported): ?>
            <p class="beans-admin-check-warning">
                Please upgrade your Wordpress:
                <a href="https://codex.wordpress.org/Upgrading_WordPress" target="_blank">Upgrading Wordpress</a>
            </p>
        <?php endif; ?>
    </li>
    <li>
        <p>
            <strong>PHP Version</strong>: <?php echo $php_version; get_supported_tag($php_is_supported); ?>
        </p>
        <?php if (!$php_is_supported): ?>
            <p class="beans-admin-check-warning">
                Contact your web host to update your PHP:
                <a href="https://wordpress.org/support/update-php/" target="_blank">Learn more on PHP Update</a>
            </p>
        <?php endif; ?>
    </li>
    <li>
        <p>
            <strong>CURL Supported</strong>: <?php get_supported_tag($curl_is_supported); ?>
        </p>
        <?php if (!$curl_is_supported): ?>
            <p class="beans-admin-check-warning">Contact your web host to enable CURL support.</p>
        <?php endif; ?>
    </li>
    <li>
        <p>
            <strong>JSON Supported</strong>: <?php get_supported_tag($json_is_supported); ?>
        </p>
        <?php if (!$json_is_supported): ?>
            <p class="beans-admin-check-warning">Contact your web host to enable JSON support.</p>
        <?php endif; ?>
    </li>
    <li>
        <p>
            <strong>Permalink Enabled</strong>: <?php get_supported_tag($permalink_is_supported); ?>
        </p>
        <?php if (!$permalink_is_supported): ?>
            <p class="beans-admin-check-warning">
                Please enable Permalinks:
                <a href="https://codex.wordpress.org/Settings_Permalinks_Screen" target="_blank">
                    How to enable Permalink
                </a>
            </p>
        <?php endif; ?>
    </li>
    <li>
        <p>
            <strong>WordPress Permalink Enabled</strong>: <?php get_supported_tag($wp_permalink_is_supported); ?>
        </p>
        <?php if (!$wp_permalink_is_supported): ?>
            <p class="beans-admin-check-warning">
                Please enable pretty permalink:
                <a href="https://codex.wordpress.org/Using_Permalinks#Choosing_your_permalink_structure" target="_blank">
                    How to enable pretty permalink
                </a>
            </p>
        <?php endif; ?>
    </li>
    <?php if (!is_null($woo_api_v2_uri_is_up)): ?>
        <li>
            <p>
                <strong>WooCommerce API V2 URI
                    Test</strong>: <?php get_supported_tag($woo_api_v2_uri_is_up); ?>
            </p>
            <?php if (!$woo_api_v2_uri_is_up): ?>
                <p class="beans-admin-check-warning">
                    Unable to connect to the API using endpoint: <br/>
                    <?php echo getenv("BEANS_WOO_API_ENDPOINT"); ?> <br/>
                    HTTP Status: <?php echo $woo_api_v2_uri_http_status ?> <br/>
                    Content Type: <?php echo $woo_api_v2_uri_content_type ?> <br/>
                    Please:
                    <a href="mailto:hello@trybeans.com" target="_blank">Contact the Beans Team</a> for
                    assistance.
                    Attach a screenshot of this page to your email.
                </p>
            <?php endif; ?>
        </li>
    <?php endif; ?>
    <?php if (!is_null($woo_api_v2_auth_is_up)): ?>
        <li>
            <p>
                <strong>WooCommerce API V2 Authentication
                    Test</strong>: <?php get_supported_tag($woo_api_v2_auth_is_up); ?>
            </p>
            <?php if (!$woo_api_v2_auth_is_up): ?>
                <p class="beans-admin-check-warning">
                    Unable to connect to the API using authentication endpoint: <br/>
                    <?php echo BEANS_WOO_API_AUTH_ENDPOINT; ?> <br/>
                    HTTP Status: <?php echo $woo_api_v2_auth_http_status; ?> <br/>
                    Content Type: <?php echo $woo_api_v2_auth_content_type; ?> <br/>
                    Please:
                    <a href="mailto:hello@trybeans.com" target="_blank">Contact the Beans Team</a> for
                    assistance.
                    Attach a screenshot of this page to your email.
                </p>
            <?php endif; ?>
        </li>
    <?php endif; ?>
</ul>
<form method="get" class="beans-admin-form" id="beans-connect-form" action="<?php echo $connect; ?>">
    <input type="hidden" name="email" value="<?php echo $admin->user_email; ?>">
    <input type="hidden" name="first_name" value="<?php echo $admin->user_firstname; ?>">
    <input type="hidden" name="last_name" value="<?php echo $admin->user_lastname; ?>">
    <input type="hidden" name="country" value="<?php echo $country_code; ?>">
    <input type="hidden" name="company_name" value="<?php echo get_bloginfo('name'); ?>">
    <input type="hidden" name="currency" value="<?php echo strtoupper(get_woocommerce_currency()); ?>">
    <input type="hidden" name="website" value="<?php echo get_site_url(); ?>">
    <input type="hidden" name="api_uri" value="<?php echo BEANS_WOO_API_ENDPOINT; ?>">
    <input type="hidden" name="api_auth_uri" value="<?php echo BEANS_WOO_API_AUTH_ENDPOINT ?>">
    <input type="hidden" name="redirect" value="<?php echo BEANS_WOO_MENU_LINK; ?>">
</form>
<script>
    jQuery(function () {

        const woo_is_supported = "<?php echo $woo_is_supported; ?>";
        const wp_is_supported = "<?php echo $wp_is_supported; ?>";
        const php_is_supported = "<?php echo $php_is_supported; ?>";
        const curl_is_supported = "<?php echo $curl_is_supported; ?>";
        const json_is_supported = "<?php echo $json_is_supported; ?>";
        const permalink_is_supported = "<?php echo $permalink_is_supported; ?>";
        const wp_permalink_is_supported = "<?php echo $wp_permalink_is_supported; ?>";
        const woo_api_v2_uri_is_up = "<?php echo $woo_api_v2_uri_is_up; ?>";
        const woo_api_v2_auth_is_up = "<?php echo $woo_api_v2_auth_is_up; ?>";

        if (woo_is_supported !== '1' || wp_is_supported !== '1' || php_is_supported !== '1'
            || php_is_supported !== '1' || curl_is_supported !== '1' || json_is_supported !== '1'
            || permalink_is_supported !== '1' || wp_permalink_is_supported !== '1'
            || woo_api_v2_auth_is_up !== '1' || woo_api_v2_uri_is_up !== '1') {
            jQuery('#view-config').text('View less');
            jQuery('#config-status').slideDown('slow');
        }
        jQuery('#view-config').click(function () {
            if (jQuery("#config-status").is(':hidden')) {
                jQuery('#config-status').slideDown('slow');
                jQuery('#view-config').text('View less');
            } else {
                jQuery('#config-status').slideUp('slow');
                jQuery('#view-config').text('View configuration');
            }
        });
    })
</script>
