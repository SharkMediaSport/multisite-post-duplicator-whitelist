<?php
/*
Plugin Name: 	Multisite Post Duplicator Whitelist
Description:    Addon for Multisite Post Duplicator which disables the Restrict Sites plugin, which provides blacklisting, and changes the mechanism to a whitelist.
Author: 		Jasmin Auger
Author URI:  	https://www.sharkmediasport.com
Text Domain: 	multisite-post-duplicator
Domain Path: 	/lang
*/

class MPDWhitelistSites
{
    static $settings_field_prefix = "mpd_allow_share_to_site_";
    static $settings_field_prefix_length = 24;

    public static function init()
    {
        add_action('plugins_loaded', array(get_called_class(), 'deactivate_restrictSites_plugin'), 11);

        add_action('mdp_end_plugin_setting_page', array(get_called_class(), 'addHooks'));
        add_filter('mpd_is_active', array(get_called_class(), "is_site_active"), 100);
        add_filter('mpd_allowed_sites', array(get_called_class(), "filter_sites"), 100, 1);
    }

    public static function deactivate_restrictSites_plugin() {

        remove_action('update_option_mdp_settings', 'mpd_globalise_settings', 10);
        remove_action('mdp_end_plugin_setting_page', 'restrict_addon_mpd_settings', 10);
        remove_filter('mpd_allowed_sites', 'mpd_filter_restricted_sites', 10);
        remove_action('admin_head', 'mpd_add_addon_script_to_settings_page', 10);
        remove_filter('mpd_is_active', 'mpd_is_site_active', 10);
    }

    public static function is_site_active() {

        $mdp_settings = get_option('mdp_settings');

        return count(mpd_get_allowed_sites()) > 1;
    }

    public static function filter_sites($sites) {

        $mdp_settings = get_option('mdp_settings');
        $settings_keys = array_keys($mdp_settings);

        $allow_site_settings = array_filter($settings_keys, function($key) {

            return substr($key, 0, MPDWhiteListSites::$settings_field_prefix_length) == MPDWhiteListSites::$settings_field_prefix;
        });

        array_walk($allow_site_settings, function(&$setting) {

            $setting = substr($setting, MPDWhiteListSites::$settings_field_prefix_length);
        });

        $allowed_sites = array_values($allow_site_settings);

        return array_filter($sites, function($site) use ($allowed_sites) {

            return in_array($site->blog_id, $allowed_sites);
        });
    }

    public static function addHooks()
    {
        mpd_settings_field('allow_option_setting', '<i class="fa fa-user-times" aria-hidden="true"></i> ' . __('Whitelist MPD on specified sites', 'multisite-post-duplicator'),  array(get_called_class(), 'allow_option_setting_render'));
        mpd_settings_field('allow_share_to_sites_settings', '<i class="fa fa-user-plus" aria-hidden="true"></i> ' . __('Allow sharing to the following sites', 'multisite-post-duplicator'),  array(get_called_class(), 'allow_share_to_sites_render'));

        add_action('admin_head', array(get_called_class(), 'add_settings_page_script'));
    }

    public static function allow_option_setting_render()
    {
        $options = get_option('mdp_settings');

        $mdp_allow_radio_label_value =
        $options && !empty($options['allow_option_setting'])
        ? $options['allow_option_setting']
        : 'none';

        ?>

          <div id="mpd_allow_radio_choice_wrap">
              <div class="mdp-inputcontainer">
                  <input type="radio" class="mdp_radio" name='mdp_settings[allow_option_setting]' id="mpd_allow_none" <?php checked($mdp_allow_radio_label_value, 'none');?> value="none">

                  <label class="mdp_radio_label" for="mpd_allow_none"><?php _e('No site', 'multisite-post-duplicator')?></label>

                  <input type="radio" class="mdp_radio" name='mdp_settings[allow_option_setting]' id="mpd_allow_some" <?php checked($mdp_allow_radio_label_value, 'some');?> value="some">

                  <label class="mdp_radio_label" for="mpd_allow_some"><?php _e('Some sites', 'multisite-post-duplicator')?></label>

                  <?php mpd_information_icon('Select to which sites the current site is allowed to share posts.');?>
              </div>
          </div>

        <?php
    }

    public static function allow_share_to_sites_render()
    {
        $allowed_sites = mpd_get_allowed_sites();
        $allowed_site_ids = array_map(function($site) {

            return $site->blog_id;
        }, $allowed_sites);
        $sites = mpd_wp_get_sites();

        ?>
            <?php foreach ($sites as $site): ?>

                <?php

                $blog_details = get_blog_details($site->blog_id);
                $checkme = '';

                if (in_array($site->blog_id, $allowed_site_ids)) {
                    $checkme = 'checked="checked"';
                }
                ?>

                <input type='checkbox' class="allow-some-checkbox" name='mdp_settings[<?php echo MPDWhiteListSites::$settings_field_prefix . $site->blog_id ?>]' <?php echo $checkme; ?> value='<?php echo $site->blog_id; ?>'> <?php echo $blog_details->blogname; ?> <br >

        <?php endforeach;?>

        <p class="mpdtip"><?php _e('Select some sites where you do not want MDP functionality. Note: You should not select all sites here as this will result in no MPD functionality.', 'multisite-post-duplicator')?></p>
    <?php

    }

    public static function add_settings_page_script()
    {

        $screenid = get_current_screen()->id;

        if ($screenid == 'settings_page_multisite_post_duplicator') {
        ?>
        <script>
            jQuery(document).ready(function() {

                var allowSitesWrapper = jQuery(".allow-some-checkbox").parent().parent();

                allowSitesWrapper.hide();

                if(jQuery('#mpd_allow_some').is(':checked')) {

                    allowSitesWrapper.show();
                }

                jQuery('#mpd_allow_radio_choice_wrap .mdp_radio').change(function() {

                    jQuery(this).val() == 'some'
                        ? allowSitesWrapper.show('fast')
                        : allowSitesWrapper.hide('fast');
                });
            });

        </script>
        <?php
        }
    }
}

MPDWhitelistSites::init();
