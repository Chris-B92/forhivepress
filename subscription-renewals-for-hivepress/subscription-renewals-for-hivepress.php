<?php
/**
 * Plugin Name: Subscription Renewals for HivePress
 * Description: Automates listing renewals with WooCommerce Subscriptions in HivePress.
 * Version: 1.0.0
 * Author: Chris Bruce
 * Author URI: https://freestylr.co.uk
 * Text Domain: subscription-renewals-for-hivepress
 * Domain Path: /languages/
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

define('HPSR_VERSION', '1.0.0');
define('HPSR_DIR', plugin_dir_path(__FILE__));
define('HPSR_URL', plugin_dir_url(__FILE__));

// Autoload helper classes early
spl_autoload_register(function($class) {
    if (strpos($class, 'HivePress\SubscriptionRenewals\Helpers\\') === 0) {
        $class_name = str_replace('HivePress\SubscriptionRenewals\Helpers\\', '', $class);
        $file = HPSR_DIR . 'includes/helpers/class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Register extension directory with HivePress
add_filter('hivepress/v1/extensions', function($extensions) {
    $extensions[] = HPSR_DIR;
    return $extensions;
});

// Load textdomain early
add_action('init', function() {
    load_plugin_textdomain('subscription-renewals-for-hivepress', false, dirname(plugin_basename(__FILE__)) . '/languages');
}, 5);

// Activation hook to schedule daily sync
register_activation_hook(__FILE__, function() {
    if (!wp_next_scheduled('hpsr_daily_sync')) {
        wp_schedule_event(time(), 'daily', 'hpsr_daily_sync');
    }
});

// Deactivation hook to clear scheduled events
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('hpsr_daily_sync');
});

// Set default options
add_action('admin_init', function() {
    $defaults = [
        'admin_bypass' => 0,
        'debug_mode' => 1,
        'hide_toolbar' => 1,
        'reset_submit_on_renewal' => 0,
    ];
    if (!get_option('hpsr_settings')) {
        add_option('hpsr_settings', $defaults);
    }
});

// Initialize admin menu and settings
add_action('admin_menu', function() {
    add_menu_page(
        __('Subscription Renewals', 'subscription-renewals-for-hivepress'),
        __('Subscription Renewals', 'subscription-renewals-for-hivepress'),
        'manage_options',
        'subscription-renewals',
        'hpsr_render_settings_page',
        'dashicons-update',
        30
    );
});

add_action('admin_init', function() {
    register_setting('hpsr_settings', 'hpsr_settings', [
        'sanitize_callback' => 'hpsr_sanitize_settings',
        'default' => [
            'admin_bypass' => 0,
            'debug_mode' => 1,
            'hide_toolbar' => 1,
            'reset_submit_on_renewal' => 0,
        ],
    ]);

    add_settings_section('hpsr_general', __('General Settings', 'subscription-renewals-for-hivepress'), null, 'hpsr_settings');

    add_settings_field('hpsr_admin_bypass', __('Admin Bypass', 'subscription-renewals-for-hivepress'), 'hpsr_render_admin_bypass_field', 'hpsr_settings', 'hpsr_general');
    add_settings_field('hpsr_debug_mode', __('Debug Mode', 'subscription-renewals-for-hivepress'), 'hpsr_render_debug_mode_field', 'hpsr_settings', 'hpsr_general');
    add_settings_field('hpsr_hide_toolbar', __('Hide Toolbar', 'subscription-renewals-for-hivepress'), 'hpsr_render_hide_toolbar_field', 'hpsr_settings', 'hpsr_general');
    add_settings_field('hpsr_reset_submit_on_renewal', __('Reset Submit Limit on Renewal', 'subscription-renewals-for-hivepress'), 'hpsr_render_reset_submit_field', 'hpsr_settings', 'hpsr_general');
});

function hpsr_sanitize_settings($input) {
    return [
        'admin_bypass' => !empty($input['admin_bypass']) ? 1 : 0,
        'debug_mode' => !empty($input['debug_mode']) ? 1 : 0,
        'hide_toolbar' => !empty($input['hide_toolbar']) ? 1 : 0,
        'reset_submit_on_renewal' => !empty($input['reset_submit_on_renewal']) ? 1 : 0,
    ];
}

function hpsr_render_admin_bypass_field() {
    $options = get_option('hpsr_settings', []);
    ?>
    <input type="checkbox" name="hpsr_settings[admin_bypass]" value="1" <?php checked(!empty($options['admin_bypass'])); ?>>
    <p class="description"><?php esc_html_e('Allow administrators to publish listings without a subscription.', 'subscription-renewals-for-hivepress'); ?></p>
    <?php
}

function hpsr_render_debug_mode_field() {
    $options = get_option('hpsr_settings', []);
    ?>
    <input type="checkbox" name="hpsr_settings[debug_mode]" value="1" <?php checked(!empty($options['debug_mode'])); ?>>
    <p class="description"><?php esc_html_e('Enable detailed logging for debugging.', 'subscription-renewals-for-hivepress'); ?></p>
    <?php
}

function hpsr_render_hide_toolbar_field() {
    $options = get_option('hpsr_settings', []);
    ?>
    <input type="checkbox" name="hpsr_settings[hide_toolbar]" value="1" <?php checked(!empty($options['hide_toolbar'])); ?>>
    <p class="description"><?php esc_html_e('Hide WordPress toolbar for non-admins.', 'subscription-renewals-for-hivepress'); ?></p>
    <?php
}

function hpsr_render_reset_submit_field() {
    $options = get_option('hpsr_settings', []);
    ?>
    <input type="checkbox" name="hpsr_settings[reset_submit_on_renewal]" value="1" <?php checked(!empty($options['reset_submit_on_renewal'])); ?>>
    <p class="description"><?php esc_html_e('Reset the submit limit for packages upon subscription renewal.', 'subscription-renewals-for-hivepress'); ?></p>
    <?php
}

// Enqueue admin assets
add_action('admin_enqueue_scripts', function($hook) {
    if (in_array($hook, ['toplevel_page_subscription-renewals', 'index.php'])) {
        wp_enqueue_style('hpsr_admin_css', HPSR_URL . 'assets/css/admin.css', [], HPSR_VERSION);
    }
    if ($hook === 'toplevel_page_subscription-renewals') {
        wp_enqueue_script('hpsr_admin_js', HPSR_URL . 'assets/js/admin.js', ['jquery'], HPSR_VERSION, true);
        wp_localize_script('hpsr_admin_js', 'hpsr_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hpsr_nonce'),
            'i18n' => [
                'process' => __('Processing...', 'subscription-renewals-for-hivepress'),
                'search' => __('Search', 'subscription-renewals-for-hivepress'),
            ],
        ]);
    }
});

// Add dashboard widget
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'hpsr_dashboard_widget',
        __('Subscription Renewals Overview', 'subscription-renewals-for-hivepress'),
        'hpsr_render_dashboard_widget'
    );
});

function hpsr_render_dashboard_widget() {
    $hp_exists = class_exists('HivePress\Core') && function_exists('hivepress');
    $wc_exists = class_exists('WooCommerce');
    $wcs_exists = class_exists('WC_Subscriptions');

    if (!$hp_exists || !$wc_exists || !$wcs_exists) {
        echo '<p>' . esc_html__('Some dependencies are missing. Please check the System Status page.', 'subscription-renewals-for-hivepress') . '</p>';
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=subscription-renewals')) . '" class="button">' . esc_html__('Go to Settings', 'subscription-renewals-for-hivepress') . '</a></p>';
        return;
    }

    $next_sync = wp_next_scheduled('hpsr_daily_sync');
    $stats = hpsr_get_stats_summary();
    $active_subscriptions = $stats['subscriptions']['active'] + $stats['subscriptions']['pending-cancel'];
    $users_with_packages = \HivePress\Models\User_Listing_Package::query()->filter(['status' => 'publish'])->get()->count();
    $active_packages = $stats['packages']['active'];
    $published_listings = \HivePress\Models\Listing::query()->filter(['status' => 'publish'])->get()->count();

    include HPSR_DIR . 'templates/admin/dashboard-widget.php';
}

// Get statistics for reports using HivePress models
function hpsr_get_stats_summary() {
    $stats = [
        'subscriptions' => ['active' => 0, 'pending-cancel' => 0, 'cancelled' => 0, 'on-hold' => 0, 'expired' => 0, 'trash' => 0, 'total' => 0],
        'packages' => ['active' => 0, 'pending-cancel' => 0, 'admin_bypass' => 0, 'inactive' => 0, 'total' => 0],
        'listings' => ['publish' => 0, 'draft' => 0, 'pending' => 0, 'private' => 0, 'trash' => 0, 'subscription_drafted' => 0, 'total' => 0],
    ];

    $cached_stats = get_transient('hpsr_stats_summary');
    if ($cached_stats !== false) {
        return $cached_stats;
    }

    if (class_exists('WC_Subscriptions')) {
        foreach (['active', 'pending-cancel', 'cancelled', 'on-hold', 'expired', 'trash'] as $status) {
            $stats['subscriptions'][$status] = count(wcs_get_subscriptions(['subscription_status' => $status, 'subscriptions_per_page' => -1]));
            $stats['subscriptions']['total'] += $stats['subscriptions'][$status];
        }
    }

    if (class_exists('HivePress\Core') && class_exists('HivePress\Models\User_Listing_Package')) {
        $options = get_option('hpsr_settings', []);
        $stats['packages']['admin_bypass'] = !empty($options['admin_bypass']) ? count(get_users(['role' => 'administrator', 'fields' => 'ID'])) : 0;
        $stats['packages']['active'] = \HivePress\Models\User_Listing_Package::query()->filter(['_meta' => ['_hp_subscription_id' => ['EXISTS']]])->get()->count();
        $stats['packages']['total'] = \HivePress\Models\User_Listing_Package::query()->get()->count();
        $stats['packages']['inactive'] = $stats['packages']['total'] - $stats['packages']['active'];
        $stats['listings'] = array_reduce(['publish', 'draft', 'pending', 'private', 'trash'], function($carry, $status) {
            $carry[$status] = \HivePress\Models\Listing::query()->filter(['status' => $status])->get()->count();
            $carry['total'] += $carry[$status];
            return $carry;
        }, $stats['listings']);
        $stats['listings']['subscription_drafted'] = \HivePress\Models\Listing::query()->filter(['_meta' => ['_hpsr_subscription_drafted' => '1']])->get()->count();
    }

    set_transient('hpsr_stats_summary', $stats, 15 * MINUTE_IN_SECONDS);
    return $stats;
}

add_action('hpsr_daily_sync', 'hpsr_clear_stats_cache');
add_action('hpsr_sync_user', 'hpsr_clear_stats_cache');
add_action('woocommerce_subscription_status_updated', 'hpsr_clear_stats_cache');
function hpsr_clear_stats_cache() {
    delete_transient('hpsr_stats_summary');
}

function hpsr_render_settings_page() {
    $hp_exists = class_exists('HivePress\Core') && function_exists('hivepress');
    $wc_exists = class_exists('WooCommerce');
    $wcs_exists = class_exists('WC_Subscriptions');
    $deps_ok = $hp_exists && $wc_exists && $wcs_exists;
    $next_sync = wp_next_scheduled('hpsr_daily_sync');
    $stats = hpsr_get_stats_summary();

    $logger = new \HivePress\SubscriptionRenewals\Helpers\Logger();

    include HPSR_DIR . 'templates/admin/settings-page.php';
}

// AJAX handlers
add_action('wp_ajax_hpsr_sync_all', function() {
    check_ajax_referer('hpsr_nonce', 'nonce') && current_user_can('manage_options') || wp_send_json_error(__('Invalid request.', 'subscription-renewals-for-hivepress'));
    do_action('hpsr_daily_sync');
    wp_send_json_success(__('All data synchronized successfully.', 'subscription-renewals-for-hivepress'));
});

add_action('wp_ajax_hpsr_process_stuck', function() {
    check_ajax_referer('hpsr_nonce', 'nonce') && current_user_can('manage_options') || wp_send_json_error(__('Invalid request.', 'subscription-renewals-for-hivepress'));
    do_action('hpsr_process_stuck_orders');
    wp_send_json_success(__('Stuck orders processed successfully.', 'subscription-renewals-for-hivepress'));
});

add_action('wp_ajax_hpsr_clear_logs', function() {
    check_ajax_referer('hpsr_nonce', 'nonce') && current_user_can('manage_options') || wp_send_json_error(__('Invalid request.', 'subscription-renewals-for-hivepress'));
    (new \HivePress\SubscriptionRenewals\Helpers\Logger())->clear_logs();
    wp_send_json_success(__('Logs cleared successfully.', 'subscription-renewals-for-hivepress'));
});

add_action('wp_ajax_hpsr_user_lookup', function() {
    check_ajax_referer('hpsr_nonce', 'nonce') && current_user_can('manage_options') || wp_send_json_error(__('Invalid request.', 'subscription-renewals-for-hivepress'));
    $search = sanitize_text_field($_POST['search'] ?? '');
    empty($search) && wp_send_json_error(__('Please enter a search term.', 'subscription-renewals-for-hivepress'));

    $user = is_numeric($search) ? get_user_by('ID', $search) : (is_email($search) ? get_user_by('email', $search) : get_user_by('login', $search));
    !$user && wp_send_json_error(__('User not found.', 'subscription-renewals-for-hivepress'));

    $user_id = $user->ID;
    $subscription_details = array_map(function($sub) {
        return [
            'id' => $sub->get_id(),
            'status' => $sub->get_status(),
            'is_active' => in_array($sub->get_status(), ['active', 'pending-cancel'], true),
            'next_payment' => $sub->get_date('next_payment') ? date_i18n('d/m/Y H:i', strtotime($sub->get_date('next_payment'))) : 'N/A',
            'end_date' => $sub->get_date('end') ? date_i18n('d/m/Y H:i', strtotime($sub->get_date('end'))) : 'N/A',
        ];
    }, wcs_get_users_subscriptions($user_id));

    $package_details = array_map(function($package) {
        return [
            'id' => $package->comment_ID,
            'name' => get_the_title($package->comment_post_ID),
            'submit_limit' => get_post_meta($package->comment_post_ID, 'hp_submit_limit', true),
            'expire_period' => get_post_meta($package->comment_post_ID, 'hp_expire_period', true),
            'subscription_id' => get_comment_meta($package->comment_ID, '_hp_subscription_id', true),
        ];
    }, get_comments(['type' => 'hp_user_package', 'user_id' => $user_id, 'number' => -1]));

    $listings = \HivePress\Models\Listing::query()
        ->filter([
            'user' => $user_id,
            'status__in' => ['publish', 'draft', 'pending'],
        ])
        ->get();

    $listing_details = [];
    foreach ($listings as $listing) {
        $expired_time = get_post_meta($listing->get_id(), 'hp_expired_time', true);
        $listing_details[] = [
            'id' => $listing->get_id(),
            'title' => $listing->get_title(),
            'status' => $listing->get_status(),
            'expired_time' => $expired_time ? date_i18n('d/m/Y', $expired_time) : 'N/A',
            'subscription_drafted' => get_post_meta($listing->get_id(), '_hpsr_subscription_drafted', true) ? true : false,
        ];
    }

    $options = get_option('hpsr_settings', []);
    $admin_bypass = user_can($user_id, 'manage_options') && !empty($options['admin_bypass']);

    ob_start();
    include HPSR_DIR . 'templates/admin/user-details.php';
    wp_send_json_success(ob_get_clean());
});

add_action('wp_ajax_hpsr_reschedule_cron', function() {
    check_ajax_referer('hpsr_nonce', 'nonce') && current_user_can('manage_options') || wp_send_json_error(__('Invalid request.', 'subscription-renewals-for-hivepress'));
    wp_clear_scheduled_hook('hpsr_daily_sync');
    wp_schedule_event(time(), 'daily', 'hpsr_daily_sync');
    wp_send_json_success(__('Cron jobs rescheduled successfully.', 'subscription-renewals-for-hivepress'));
});

add_action('wp_ajax_hpsr_sync_user', function() {
    check_ajax_referer('hpsr_nonce', 'nonce') && current_user_can('manage_options') || wp_send_json_error(__('Invalid request.', 'subscription-renewals-for-hivepress'));
    $user_id = absint($_POST['user_id'] ?? 0);
    !$user_id && wp_send_json_error(__('User ID is required.', 'subscription-renewals-for-hivepress'));

    $subscriptions = wcs_get_users_subscriptions($user_id);
    $subscription_count = 0;
    $active_count = 0;
    $manager = hivepress()->subscription_renewals_manager;

    foreach ($subscriptions as $subscription) {
        $subscription_count++;
        $status = $subscription->get_status();
        if (in_array($status, ['active', 'pending-cancel'], true)) {
            $manager->handle_subscription_activated($subscription);
            $active_count++;
        } elseif (in_array($status, ['cancelled', 'expired'], true)) {
            $manager->handle_subscription_cancelled($subscription);
        }
    }

    if ($active_count === 0 && !$manager->is_admin_bypass_enabled($user_id)) {
        $manager->draft_user_listings($user_id);
    }

    hpsr_clear_stats_cache();
    wp_send_json_success(sprintf(__('User sync completed successfully. Processed %1$d subscriptions (%2$d active).', 'subscription-renewals-for-hivepress'), $subscription_count, $active_count));
});

add_action('wp_ajax_hpsr_debug_expiry', function() {
    check_ajax_referer('hpsr_nonce', 'nonce') && current_user_can('manage_options') || wp_send_json_error(__('Invalid request.', 'subscription-renewals-for-hivepress'));
    $user_id = absint($_POST['user_id'] ?? 0);
    !$user_id && wp_send_json_error(__('User ID is required.', 'subscription-renewals-for-hivepress'));

    $debug_output = hivepress()->subscription_renewals_manager->debug_listing_expiry_dates($user_id);
    wp_send_json_success('<h3>' . esc_html__('Listing Expiry Debug Info', 'subscription-renewals-for-hivepress') . '</h3><pre style="background: #f5f5f5; padding: 15px; overflow: auto; max-height: 400px;">' . esc_html($debug_output) . '</pre>');
});

// Hide admin toolbar for non-admins if enabled
add_filter('show_admin_bar', function($show) {
    $options = get_option('hpsr_settings', []);
    return (!current_user_can('manage_options') && !empty($options['hide_toolbar'])) ? false : $show;
}, 999);