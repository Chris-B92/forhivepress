<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;
?>

<div class="hpsr-dashboard-widget">
    <div class="stats-grid">
        <div class="stat-tile">
            <div class="label"><?php esc_html_e('ACTIVE SUBSCRIPTIONS', 'subscription-renewals-for-hivepress'); ?></div>
            <div class="value"><?php echo esc_html($active_subscriptions); ?></div>
        </div>
        <div class="stat-tile">
            <div class="label"><?php esc_html_e('USERS WITH PACKAGES', 'subscription-renewals-for-hivepress'); ?></div>
            <div class="value"><?php echo esc_html($users_with_packages); ?></div>
        </div>
        <div class="stat-tile">
            <div class="label"><?php esc_html_e('ACTIVE PACKAGES', 'subscription-renewals-for-hivepress'); ?></div>
            <div class="value"><?php echo esc_html($active_packages); ?></div>
        </div>
        <div class="stat-tile">
            <div class="label"><?php esc_html_e('PUBLISHED LISTINGS', 'subscription-renewals-for-hivepress'); ?></div>
            <div class="value"><?php echo esc_html($published_listings); ?></div>
        </div>
    </div>
    <?php if ($next_sync) : ?>
    <div class="sync-info">
        <?php esc_html_e('Next Automatic Sync:', 'subscription-renewals-for-hivepress'); ?> <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_sync)); ?>
    </div>
    <?php endif; ?>
    <a href="<?php echo esc_url(admin_url('admin.php?page=subscription-renewals')); ?>" class="button"><?php esc_html_e('Go to Settings', 'subscription-renewals-for-hivepress'); ?></a>
</div>