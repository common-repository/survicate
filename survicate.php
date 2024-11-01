<?php
/**
 * @package Survicate
 */
/*
Plugin Name: Survicate
Plugin URI: https://survicate.com/
Description: Survicate helps you understand your Customers with targeted website surveys, drive conversions via notifications and profile visitors for better marketing campaigns.
Version: 4.1.3
Author: Survicate
Author URI: https://survicate.com/
*/


if (!function_exists('add_action')) {
    exit;
}

/* MENU */
add_action('admin_menu', 'survicate_admin_menu');
function survicate_admin_menu()
{
    add_options_page('Survicate', 'Survicate', 'manage_options', 'survicate', 'survicate_options_page');
}

/* OPTIONS PAGE */

function survicate_custom_admin_css()
{
    $plugin_url = plugin_dir_url(__FILE__);
    wp_enqueue_style('style', $plugin_url . "css/style.css");
}

add_action('admin_print_styles', 'survicate_custom_admin_css');
function survicate_options_page()
{
    if (isset($_GET['settings-updated'])) : ?>

        <?php if (get_option('survicate-tracking-code') == ''): ?>
            <div id="message" class="notice notice-warning is-dismissible">
                <p><strong><?php _e('Survicate is disabled.', 'survicate'); ?></strong></p>
            </div>
        <?php else: ?>
            <?php survicate_verify_install() ?>
        <?php endif; ?>

    <?php endif; ?>
    <div id="survicate-padding">
        <form action="options.php" method="POST">
            <img src="//survicate.com/wp-content/themes/survicate_theme/dist/img/survicate-logo-wb.svg" width="150"
                 style="padding-top: 20px; padding-bottom: 20px;" alt="Survicate"/>
            <h1>Where leading software companies collect customer feedback</h1>
            <div id="survicate-form-area">
                <p>Go to <a href="https://panel.survicate.com" target="_blank">Survicate panel settings</a> to find your
                    unique
                    Survicate Workspace Key.</p>
                <p>Input your Survicate Workspace Key into the field below to connect your Survicate and WordPress
                    accounts.</p>

                <?php settings_fields('survicate-settings'); ?>
                <?php do_settings_sections('survicate-settings'); ?>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="survicate-tracking-code">Survicate Workspace Key</label>
                        </th>
                        <td>
                            <input type="text" name="survicate-tracking-code" id="survicate-tracking-code"
                                   value="<?php echo esc_attr(get_option('survicate-tracking-code')); ?>"
                                   autocomplete="off" style="width:100%;">
                            <p class="description" id="wp_survicate_key_description">(Leave blank to disable)</p>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php submit_button('Save changes'); ?>
            </div>
        </form>
    </div>
    <?php
}

/*==================================== ACTIONS ===================================================*/

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'survicate_add_plugin_page_settings_link');
function survicate_add_plugin_page_settings_link($links)
{
    $links[] = '<a href="' .
        admin_url('options-general.php?page=survicate') .
        '">' . __('Settings') . '</a>';
    return $links;
}

function register_survicate_settings()
{
    register_setting('survicate-settings', 'survicate-tracking-code');
}

add_action('admin_init', 'register_survicate_settings');

/* INSTALL VERIFY */
function survicate_verify_install()
{
    if (empty($workspaceKey = esc_attr(get_option('survicate-tracking-code')))) {
        return;
    }
    $urlPattern = 'https://respondent.survicate.com/workspaces/%s/installed.json';
    $url = sprintf($urlPattern, $workspaceKey);
    $response = wp_remote_post($url, [
        'headers' => [
            'content-type' => 'application/json'
        ],
        'body' => '{"platform":"web"}'
    ]);
    if ($response['response']['code'] == 200) {
        survicate_install_success_notification();
    } else {
        survicate_install_failure_notification();
    }
}

function survicate_install_success_notification()
{
    ?>
    <div id="message" class="notice notice-success is-dismissible">
        <p>
            <strong>Survicate installation complete! The Survicate tracking code has been successfully installed on your
                website.</strong>
        </p>
    </div>
    <?php
}

function survicate_install_failure_notification()
{
    ?>
    <div id="message" class="notice notice-error is-dismissible">
        <p>
            <strong>Survicate installation failed! Please double check the workspace key you provided below.</strong>
        </p>
    </div>
    <?php

}

/* TRACKING CODE ACTION */

add_action('admin_post_survicate_tracking_code', 'survicate_tracking_code_action');
function survicate_tracking_code_action()
{
    if (get_option('survicate-tracking-code') !== false) {
        update_option('survicate-tracking-code', $_POST['survicate-tracking-code']);
    } else {
        add_option('survicate-tracking-code', '');
    }
    wp_redirect(admin_url('/options-general.php?page=survicate'), 301);
}

/* FOOTER */
add_action('wp_footer', 'survicate_script');
function survicate_script()
{
    $tracking_code = esc_attr(get_option('survicate-tracking-code'));

    if (strlen($tracking_code) != 32) {
        return;
    }

    ?>
    <script type="text/javascript">
        (function (w) {
            var s = document.createElement('script');
            s.src = '//survey.survicate.com/workspaces/<?php echo $tracking_code; ?>/web_surveys.js';
            s.async = true;
            var e = document.getElementsByTagName('script')[0];
            e.parentNode.insertBefore(s, e);
        })(window);
    </script>
    <?php
}
