<?php
namespace BeansWoo;
defined('ABSPATH') or die;

// if uninstall not called from WordPress exit
defined('WP_UNINSTALL_PLUGIN') or die;

include_once ('includes/beans.php');
include_once ( 'includes/helper.php' );

Helper::resetSetup('liana');
Helper::resetSetup('bamboo');

delete_option(Helper::CONFIG_NAME);
delete_option('beans-liana-display-redemption-checkout');

try{
    delete_user_meta(get_current_user_id(), 'beans_ultimate_notice_dismissed');
} catch (\Exception $e){}

Helper::postWebhookStatus('uninstalled');
