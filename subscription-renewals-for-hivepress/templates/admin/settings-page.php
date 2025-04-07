<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

// Ensure the Logger class is loaded
if (!class_exists('HivePress\\SubscriptionRenewals\\Helpers\\Logger')) {
    require_once HPSR_DIR . 'includes/helpers/class-logger.php';
}

// Initialize logger
$logger = new \HivePress\SubscriptionRenewals\Helpers\Logger();
?>
<div class="wrap">
    <h1><?php esc_html_e('Subscription Renewals', 'subscription-renewals-for-hivepress'); ?></h1>
    <?php settings_errors('hpsr_settings'); ?>
    
    <?php if (!$deps_ok): ?>
    <div class="notice notice-warning">
        <p><?php esc_html_e('Some dependencies are missing. Status:', 'subscription-renewals-for-hivepress'); ?></p>
        <ul>
            <li><?php esc_html_e('HivePress: ', 'subscription-renewals-for-hivepress'); ?><?php echo $hp_exists ? '✓' : '✗'; ?></li>
            <li><?php esc_html_e('WooCommerce: ', 'subscription-renewals-for-hivepress'); ?><?php echo $wc_exists ? '✓' : '✗'; ?></li>
            <li><?php esc_html_e('WooCommerce Subscriptions: ', 'subscription-renewals-for-hivepress'); ?><?php echo $wcs_exists ? '✓' : '✗'; ?></li>
        </ul>
        <p><?php esc_html_e('Limited functionality is available in admin. Full functionality requires all dependencies.', 'subscription-renewals-for-hivepress'); ?></p>
    </div>
    <?php endif; ?>
    
    <h2 class="nav-tab-wrapper">
        <a href="#settings" class="nav-tab nav-tab-active"><?php esc_html_e('Settings', 'subscription-renewals-for-hivepress'); ?></a>
        <a href="#tools" class="nav-tab"><?php esc_html_e('Tools', 'subscription-renewals-for-hivepress'); ?></a>
        <a href="#reports" class="nav-tab"><?php esc_html_e('Reports', 'subscription-renewals-for-hivepress'); ?></a>
        <a href="#system-status" class="nav-tab"><?php esc_html_e('System Status', 'subscription-renewals-for-hivepress'); ?></a>
        <?php if ($deps_ok): ?>
        <a href="#user-search" class="nav-tab"><?php esc_html_e('User Search', 'subscription-renewals-for-hivepress'); ?></a>
        <a href="#logs" class="nav-tab"><?php esc_html_e('Logs', 'subscription-renewals-for-hivepress'); ?></a>
        <?php endif; ?>
    </h2>

    <div id="settings" class="tab-content">
        <form method="post" action="options.php">
            <?php
            settings_fields('hpsr_settings');
            do_settings_sections('hpsr_settings');
            submit_button();
            ?>
        </form>
    </div>

    <div id="tools" class="tab-content" style="display:none;">
        <h2><?php esc_html_e('Subscription Management Tools', 'subscription-renewals-for-hivepress'); ?></h2>
        
        <?php if (!$deps_ok): ?>
        <div class="notice notice-warning inline">
            <p><?php esc_html_e('These tools require HivePress, WooCommerce, and WooCommerce Subscriptions to function properly.', 'subscription-renewals-for-hivepress'); ?></p>
        </div>
        <?php endif; ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Sync All Data', 'subscription-renewals-for-hivepress'); ?></th>
                <td>
                    <button type="button" id="hpsr-sync-all-data" class="button" data-action="hpsr_sync_all" <?php echo !$deps_ok ? 'disabled' : ''; ?>>
                        <?php esc_html_e('Sync All Data', 'subscription-renewals-for-hivepress'); ?>
                    </button>
                    <p class="description"><?php esc_html_e('Manually trigger synchronization of all users\' subscription data with HivePress packages.', 'subscription-renewals-for-hivepress'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Process Stuck Orders', 'subscription-renewals-for-hivepress'); ?></th>
                <td>
                    <button type="button" id="hpsr-process-stuck-orders" class="button" data-action="hpsr_process_stuck" <?php echo !$deps_ok ? 'disabled' : ''; ?>>
                        <?php esc_html_e('Process Stuck Orders', 'subscription-renewals-for-hivepress'); ?>
                    </button>
                    <p class="description"><?php esc_html_e('Check for and complete any stuck virtual orders that should be completed.', 'subscription-renewals-for-hivepress'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Cron Health Check', 'subscription-renewals-for-hivepress'); ?></th>
                <td>
                    <p>
                        <strong><?php esc_html_e('Next Scheduled Sync:', 'subscription-renewals-for-hivepress'); ?></strong>
                        <?php echo $next_sync ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_sync)) : esc_html__('Not scheduled', 'subscription-renewals-for-hivepress'); ?>
                    </p>
                    <button type="button" id="hpsr-reschedule-cron" class="button" data-action="hpsr_reschedule_cron">
                        <?php esc_html_e('Reschedule Cron Jobs', 'subscription-renewals-for-hivepress'); ?>
                    </button>
                    <p class="description"><?php esc_html_e('Reschedules the cron jobs to ensure they are running correctly.', 'subscription-renewals-for-hivepress'); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div id="reports" class="tab-content" style="display:none;">
    <h2><?php esc_html_e('Detailed Reports', 'subscription-renewals-for-hivepress'); ?></h2>
    
    <?php if (!$deps_ok): ?>
    <div class="notice notice-warning inline">
        <p><?php esc_html_e('Reports require HivePress, WooCommerce, and WooCommerce Subscriptions to be accurate.', 'subscription-renewals-for-hivepress'); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Subscription Summary Section (unchanged) -->
    <div class="hpsr-report-section">
        <h3><?php esc_html_e('Subscription Summary', 'subscription-renewals-for-hivepress'); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Status', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Count', 'subscription-renewals-for-hivepress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e('Active', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['subscriptions']['active']) ? esc_html($stats['subscriptions']['active']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Pending Cancel', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['subscriptions']['pending-cancel']) ? esc_html($stats['subscriptions']['pending-cancel']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Cancelled', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['subscriptions']['cancelled']) ? esc_html($stats['subscriptions']['cancelled']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('On-Hold', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['subscriptions']['on-hold']) ? esc_html($stats['subscriptions']['on-hold']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Expired', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['subscriptions']['expired']) ? esc_html($stats['subscriptions']['expired']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Trash', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['subscriptions']['trash']) ? esc_html($stats['subscriptions']['trash']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e('Total', 'subscription-renewals-for-hivepress'); ?></strong></td>
                    <td><strong><?php echo isset($stats['subscriptions']['total']) ? esc_html($stats['subscriptions']['total']) : '0'; ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Updated Package Summary Section -->
    <div class="hpsr-report-section">
        <h3><?php esc_html_e('Package Summary', 'subscription-renewals-for-hivepress'); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Status', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Count', 'subscription-renewals-for-hivepress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e('Active (Linked to Active Subscriptions)', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['packages']['active']) ? esc_html($stats['packages']['active']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Pending-Cancel (Linked to Pending-Cancel Subscriptions)', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['packages']['pending-cancel']) ? esc_html($stats['packages']['pending-cancel']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Inactive (Not Linked or Linked to Cancelled/Expired Subscriptions)', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['packages']['inactive']) ? esc_html($stats['packages']['inactive']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Users with Admin Bypass Enabled', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['packages']['admin_bypass']) ? esc_html($stats['packages']['admin_bypass']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e('Total Packages', 'subscription-renewals-for-hivepress'); ?></strong></td>
                    <td><strong><?php echo isset($stats['packages']['total']) ? esc_html($stats['packages']['total']) : '0'; ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Listing Summary Section (unchanged) -->
    <div class="hpsr-report-section">
        <h3><?php esc_html_e('Listing Summary', 'subscription-renewals-for-hivepress'); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Status', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Count', 'subscription-renewals-for-hivepress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e('Published', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['listings']['publish']) ? esc_html($stats['listings']['publish']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Draft', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['listings']['draft']) ? esc_html($stats['listings']['draft']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Pending', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['listings']['pending']) ? esc_html($stats['listings']['pending']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Private', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['listings']['private']) ? esc_html($stats['listings']['private']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Trashed', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['listings']['trash']) ? esc_html($stats['listings']['trash']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Subscription-Drafted', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo isset($stats['listings']['subscription_drafted']) ? esc_html($stats['listings']['subscription_drafted']) : '0'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e('Total', 'subscription-renewals-for-hivepress'); ?></strong></td>
                    <td><strong><?php echo isset($stats['listings']['total']) ? esc_html($stats['listings']['total']) : '0'; ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

    <div id="system-status" class="tab-content" style="display:none;">
        <h2><?php esc_html_e('System Status', 'subscription-renewals-for-hivepress'); ?></h2>
        
        <h3><?php esc_html_e('WordPress Environment', 'subscription-renewals-for-hivepress'); ?></h3>
        <table class="widefat" style="margin-bottom: 15px;">
            <tbody>
                <tr>
                    <td><?php esc_html_e('WordPress Version:', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('PHP Version:', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo esc_html(phpversion()); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Plugin Version:', 'subscription-renewals-for-hivepress'); ?></td>
                    <td><?php echo esc_html(HPSR_VERSION); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h3><?php esc_html_e('Dependencies', 'subscription-renewals-for-hivepress'); ?></h3>
        <table class="widefat" style="margin-bottom: 15px;">
            <tbody>
                <tr>
                    <td><?php esc_html_e('HivePress Core:', 'subscription-renewals-for-hivepress'); ?></td>
                    <td>
                        <?php echo class_exists('HivePress\\Core') ? 
                            '<span style="color:green;">✓ ' . esc_html__('Available', 'subscription-renewals-for-hivepress') . '</span>' : 
                            '<span style="color:red;">✗ ' . esc_html__('Not Available', 'subscription-renewals-for-hivepress') . '</span>'; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php esc_html_e('HivePress Function:', 'subscription-renewals-for-hivepress'); ?></td>
                    <td>
                        <?php echo function_exists('hivepress') ? 
                            '<span style="color:green;">✓ ' . esc_html__('Available', 'subscription-renewals-for-hivepress') . '</span>' : 
                            '<span style="color:red;">✗ ' . esc_html__('Not Available', 'subscription-renewals-for-hivepress') . '</span>'; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php esc_html_e('WooCommerce:', 'subscription-renewals-for-hivepress'); ?></td>
                    <td>
                        <?php echo class_exists('WooCommerce') ? 
                            '<span style="color:green;">✓ ' . esc_html__('Available', 'subscription-renewals-for-hivepress') . '</span>' : 
                            '<span style="color:red;">✗ ' . esc_html__('Not Available', 'subscription-renewals-for-hivepress') . '</span>'; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php esc_html_e('WooCommerce Subscriptions:', 'subscription-renewals-for-hivepress'); ?></td>
                    <td>
                        <?php echo class_exists('WC_Subscriptions') ? 
                            '<span style="color:green;">✓ ' . esc_html__('Available', 'subscription-renewals-for-hivepress') . '</span>' : 
                            '<span style="color:red;">✗ ' . esc_html__('Not Available', 'subscription-renewals-for-hivepress') . '</span>'; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h3><?php esc_html_e('Database Tables', 'subscription-renewals-for-hivepress'); ?></h3>
        <table class="widefat">
            <tbody>
                <?php
                global $wpdb;
                $tables = [
                    $wpdb->prefix . 'hpsr_tracked_listings',
                    $wpdb->prefix . 'hpsr_user_subscriptions'
                ];
                
                foreach ($tables as $table) :
                    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;
                ?>
                <tr>
                    <td><?php echo esc_html($table); ?></td>
                    <td>
                        <?php echo $table_exists ? 
                            '<span style="color:green;">✓ ' . esc_html__('Exists', 'subscription-renewals-for-hivepress') . '</span>' : 
                            '<span style="color:red;">✗ ' . esc_html__('Missing', 'subscription-renewals-for-hivepress') . '</span>'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p>
            <button type="button" id="hpsr-reschedule-cron" class="button" data-action="hpsr_reschedule_cron">
                <?php esc_html_e('Reschedule Cron Jobs', 'subscription-renewals-for-hivepress'); ?>
            </button>
        </p>
    </div>

    <?php if ($deps_ok): ?>
    <div id="user-search" class="tab-content" style="display:none;">
        <h2><?php esc_html_e('User Lookup Tool', 'subscription-renewals-for-hivepress'); ?></h2>
        <p><?php esc_html_e('Enter a user ID, email address, or username to view their subscription details.', 'subscription-renewals-for-hivepress'); ?></p>
        <div class="hpsr-search-form">
            <input type="text" id="hpsr-user-search-input" placeholder="<?php esc_attr_e('User ID, email, or username', 'subscription-renewals-for-hivepress'); ?>" class="regular-text">
            <button type="button" id="hpsr-user-lookup" class="button button-primary" data-action="hpsr_user_lookup">
                <?php esc_html_e('Search', 'subscription-renewals-for-hivepress'); ?>
            </button>
        </div>
        <div id="hpsr-user-results"></div>
    </div>

    <div id="logs" class="tab-content" style="display:none;">
        <h2><?php esc_html_e('Debug Logs', 'subscription-renewals-for-hivepress'); ?></h2>
        <textarea id="hpsr-logs" readonly><?php echo esc_textarea($logger->get_logs()); ?></textarea>
        <p>
            <button type="button" id="hpsr-save-logs" class="button"><?php esc_html_e('Save', 'subscription-renewals-for-hivepress'); ?></button>
            <button type="button" id="hpsr-copy-logs" class="button"><?php esc_html_e('Copy', 'subscription-renewals-for-hivepress'); ?></button>
            <button type="button" id="hpsr-clear-logs" class="button" data-action="hpsr_clear_logs"><?php esc_html_e('Clear', 'subscription-renewals-for-hivepress'); ?></button>
        </p>
    </div>
    <?php endif; ?>
</div>