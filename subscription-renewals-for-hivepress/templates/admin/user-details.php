<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;
?>

<div class="hpsr-user-details">
    <h3><?php esc_html_e('User Information', 'subscription-renewals-for-hivepress'); ?></h3>
    <table class="widefat">
        <tr>
            <th><?php esc_html_e('ID', 'subscription-renewals-for-hivepress'); ?></th>
            <td><?php echo esc_html($user_id); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Name', 'subscription-renewals-for-hivepress'); ?></th>
            <td><?php echo esc_html($user->display_name); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Email', 'subscription-renewals-for-hivepress'); ?></th>
            <td><?php echo esc_html($user->user_email); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Admin Bypass', 'subscription-renewals-for-hivepress'); ?></th>
            <td><?php echo $admin_bypass ? '<span style="color:green">✓</span> ' . esc_html__('Enabled', 'subscription-renewals-for-hivepress') : '<span style="color:red">✕</span> ' . esc_html__('Disabled', 'subscription-renewals-for-hivepress'); ?></td>
        </tr>
    </table>
    
    <h3><?php esc_html_e('Subscriptions', 'subscription-renewals-for-hivepress'); ?></h3>
    <?php if (empty($subscription_details)) : ?>
        <p><?php esc_html_e('No subscriptions found.', 'subscription-renewals-for-hivepress'); ?></p>
    <?php else : ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Status', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Next Payment', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('End Date', 'subscription-renewals-for-hivepress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscription_details as $subscription) : ?>
                <tr>
                    <td>
                        <?php if (current_user_can('edit_posts')) : ?>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $subscription['id'] . '&action=edit')); ?>" target="_blank">
                                <?php echo esc_html($subscription['id']); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html($subscription['id']); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $status_class = $subscription['is_active'] ? 'active' : 'inactive';
                        echo '<span class="subscription-status ' . esc_attr($status_class) . '">' . esc_html(ucfirst($subscription['status'])) . '</span>';
                        ?>
                    </td>
                    <td><?php echo esc_html($subscription['next_payment']); ?></td>
                    <td><?php echo esc_html($subscription['end_date']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <h3><?php esc_html_e('Packages', 'subscription-renewals-for-hivepress'); ?></h3>
    <?php if (empty($package_details)) : ?>
        <p><?php esc_html_e('No packages found.', 'subscription-renewals-for-hivepress'); ?></p>
    <?php else : ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Name', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Submit Limit', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Expire Period', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Subscription', 'subscription-renewals-for-hivepress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($package_details as $package) : ?>
                <tr>
                    <td><?php echo esc_html($package['id']); ?></td>
                    <td><?php echo esc_html($package['name']); ?></td>
                    <td><?php echo esc_html($package['submit_limit']); ?></td>
                    <td><?php echo esc_html($package['expire_period']); ?></td>
                    <td>
                        <?php if ($package['subscription_id']) : ?>
                            <?php if (current_user_can('edit_posts')) : ?>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $package['subscription_id'] . '&action=edit')); ?>" target="_blank">
                                    <?php echo esc_html($package['subscription_id']); ?>
                                </a>
                            <?php else : ?>
                                <?php echo esc_html($package['subscription_id']); ?>
                            <?php endif; ?>
                        <?php else : ?>
                            <?php esc_html_e('Not linked', 'subscription-renewals-for-hivepress'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <h3><?php esc_html_e('Listings', 'subscription-renewals-for-hivepress'); ?></h3>
    <?php if (empty($listing_details)) : ?>
        <p><?php esc_html_e('No listings found.', 'subscription-renewals-for-hivepress'); ?></p>
    <?php else : ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Title', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Status', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Expiry Date', 'subscription-renewals-for-hivepress'); ?></th>
                    <th><?php esc_html_e('Subscription Drafted', 'subscription-renewals-for-hivepress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listing_details as $listing) : ?>
                <tr>
                    <td>
                        <?php if (current_user_can('edit_posts')) : ?>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $listing['id'] . '&action=edit')); ?>" target="_blank">
                                <?php echo esc_html($listing['id']); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html($listing['id']); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($listing['title']); ?></td>
					<td>
						<?php 
						$status = $listing['status'] ?? 'unknown';
						$status_class = $status === 'publish' ? 'published' : $status;
						echo '<span class="listing-status ' . esc_attr($status_class) . '">' . esc_html(ucfirst($status)) . '</span>';
						?>
					</td>
                    <td><?php echo esc_html($listing['expired_time']); ?></td>
                    <td>
                        <?php echo $listing['subscription_drafted'] ? '<span style="color:orange">✓</span> ' . esc_html__('Yes', 'subscription-renewals-for-hivepress') : '<span style="color:blue">✕</span> ' . esc_html__('No', 'subscription-renewals-for-hivepress'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <p class="hpsr-user-actions">
        <button type="button" id="hpsr-sync-user" class="button button-primary" data-user-id="<?php echo esc_attr($user_id); ?>">
            <?php esc_html_e('Sync User Data', 'subscription-renewals-for-hivepress'); ?>
        </button>
        <button type="button" id="hpsr-debug-expiry" class="button" data-user-id="<?php echo esc_attr($user_id); ?>">
            <?php esc_html_e('Debug Expiry Dates', 'subscription-renewals-for-hivepress'); ?>
        </button>
    </p>
</div>

<style>
.subscription-status.active {
    color: green;
    font-weight: bold;
}
.subscription-status.inactive {
    color: red;
}
.listing-status.published {
    color: green;
}
.listing-status.draft {
    color: orange;
}
.hpsr-user-details h3 {
    margin-top: 20px;
}
.hpsr-user-actions {
    margin-top: 15px;
}
.hpsr-debug-info {
    margin-top: 30px;
    border: 1px solid #ddd;
    padding: 15px;
    background: #f9f9f9;
}
.hpsr-debug-info pre {
    margin: 0;
    max-height: 500px;
    overflow: auto;
}
</style>