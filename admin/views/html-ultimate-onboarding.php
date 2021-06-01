<?php

defined('ABSPATH') or die;

use BeansWoo\Helper;

$base_banner_url = 'https://'.Helper::getDomain('STATIC'). '/static-v3/connect/img/app/';
$base_asset_path = '/assets/img/admin/onboarding';
?>


<img class="beans-admin-logo"  src="<?php echo $base_banner_url; ?>logo-full-ultimate.svg"  alt="ultimate-logo">
<div class="welcome-panel-ultimate beans-admin-content-ultimate" style="max-width: 600px; margin: auto">
    <div>
        <div style="background-color:white; padding-top: 10px; padding-bottom: 60px;
        box-shadow: 0 15px 30px 0 rgba(0,0,0,.11), 0 5px 15px 0 rgba(0,0,0,.08); width: 600px; height: auto; position: relative;
        min-height: 400px;">
            <div id="beans-core" >
                <div style="display: none; text-align: center;" id="app-image"  class="app-image">
                    <div>
                        <img width="100px" height="30px" id="beans-app-img"
                             src="<?php echo $base_banner_url; ?>logo-full-liana.svg"
                             alt="beans-app-logo">
                    </div>
                </div>
                <div style="text-align: center;">
                    <h1 id="beans-welcome-text">Welcome to Beans</h1>
                    <p id="beans-app-title">Create a unified marketing experience for your online shop.</p>
                </div>
                <div style="display: flex; width: 100%; justify-content: center">
                    <div style="text-align: center">
                        <img id="beans-app-hero" src="<?php echo plugins_url($base_asset_path.'/ultimate-hero.svg',
                            BEANS_PLUGIN_FILENAME) ?>" alt="" width="auto" height="280px">
                    </div>
                </div>
                <div style="height: auto">
                    <button class="beans-bg-primary-ultimate" id="beans-step" data-step="0"  style="margin-right: 30px; float:right; border-width: 0; font-size: 13px; cursor: pointer; color: #fff; border-color: transparent; text-transform: uppercase; display: flex; justify-content: center; padding-top: .5rem; padding-bottom: .5rem; margin-top: .75rem; border-radius: .25rem; box-shadow: 0 15px 30px 0 rgba(0,0,0,.11), 0 5px 15px 0 rgba(0,0,0,.08); font-weight: 900;">next
                        <div style=" margin-left: .5rem; margin-top: 2px;">
                            <svg width="8" height="11" viewBox="0 0 8 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M1.69525 0.113281L7.0838 5.50183L1.69525 10.8904L0.292725
                                      9.48785L4.27875 5.50183L0.292725 1.51581L1.69525 0.113281Z" fill="white">
                                </path>
                            </svg>
                        </div>
                    </button>
                </div>
                <div id="beans-ultimate-connect" style="display: none">
                    <p class="wc-setup-actions step" style="justify-content: center; display: flex" id="beans-ultimate-submit-button">
                        <?php if ( $beans_is_supported || $force ): ?>
                        <button type="submit" class="btn beans-bg-primary beans-bg-primary-ultimate
                            shadow-md" value="Connect to Beans Ultimate">
                            <?php else: ?>
                            <button type="submit"
                                    class="button button-disabled beans-bg-primary beans-bg-primary-ultimate
                                shadow-md " value="Connect to Beans Ultimate"
                                    disabled>
                                <?php endif; ?>
                                Connect
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script>

        jQuery(function ($) {
            let info = [];
            <?php
                $apps = ['liana', 'bamboo', 'foxx', 'poppy', 'snow', 'lotus', 'arrow', 'ultimate'];
                foreach ($apps as $app){
                    if (in_array($app, ['snow', 'foxx'])){
                        $heroImage =  $app .'-hero.png';
                    }else{
                        $heroImage = $app .'-hero.svg';
                    }
                ?>
                    info.push({
                    hero: "<?php echo plugins_url($base_asset_path.'/'.$heroImage, BEANS_PLUGIN_FILENAME); ?>",
                    banner: "<?php echo $base_banner_url. 'logo-full-'. $app .'.svg'; ?>",
                    title: "<?php echo Helper::getApps()[$app]['title']; ?>",
                    role: "<?php echo Helper::getApps()[$app]['role']; ?>",
                });
            <?php
                }
            ?>

            $("#beans-step").on('click', function(){
                let step = $(this).attr("data-step");
                if($(this).text() === "connect") {
                    $("#beans-connect-form").submit();
                }
                const data = info[step];
                let next_step = parseInt(step) + 1;
                if ( next_step === info.length ){
                    $(this).text("connect");
                    $(this).attr("id", "ultimate-submit-button");
                    $(this).attr("type", "submit");
                    $("#beans-welcome-text").text(data.title);
                    $("#beans-app-title").hide();
                    $("#beans-app-img").hide();
                    $("#beans-ultimate-connect").show();
                    $(this).hide();
                    $("#beans-app-hero").attr("width", "75%");
                }

                if ( ! data ) return "";
                $("#beans-welcome-text").text(data.role);
                $("#beans-welcome-text").attr("style", "margin: 0 0;");

                $("#beans-app-img").attr("src", data.banner);
                $("#beans-app-title").text(data.title);
                $("#beans-app-hero").attr( "src", data.hero);
                $(this).attr("data-step", next_step);
                $("#app-image").attr("style", "text-align:center;");
            });
            $("#beans-ultimate-submit-button").on('click', function(){
                $("#beans-connect-form").submit();
            })
        });

    </script>
</div>
