<?php
namespace HivePress\Components;

use HivePress\Helpers as hp;
use HivePress\Models;

defined('ABSPATH') || exit;

final class Subscription_Renewals_Manager extends Component {
    public function __construct($args = []) {
        if (class_exists('WC_Subscriptions')) {
            add_action('woocommerce_subscription_status_active', [$this, 'handle_subscription_activated'], 10, 1);
            add_action('woocommerce_subscription_status_on-hold', [$this, 'handle_subscription_paused'], 10, 1);
            add_action('woocommerce_subscription_status_cancelled', [$this, 'handle_subscription_cancelled'], 10, 1);
            add_action('woocommerce_subscription_status_expired', [$this, 'handle_subscription_cancelled'], 10, 1);
            add_action('woocommerce_subscription_status_pending-cancel', [$this, 'handle_subscription_pending_cancel'], 10, 1);
            add_action('woocommerce_subscription_renewal_payment_complete', [$this, 'handle_subscription_renewal'], 10, 2);
            add_action('woocommerce_subscription_payment_complete', [$this, 'handle_subscription_payment'], 10, 1);
        }

        add_action('woocommerce_new_order', [$this, 'process_new_order'], 10, 1);
        add_filter('hivepress/v1/models/listing/errors', [$this, 'validate_listing_subscription'], 10, 2);
        add_action('hpsr_daily_sync', [$this, 'sync_all_subscriptions']);
        add_action('hpsr_process_stuck_orders', [$this, 'process_stuck_orders']);
        add_action('wp_footer', [$this, 'maybe_hide_visibility_controls']);

        // Add hook to unset subscription-drafted meta when a listing is manually published
        add_action('transition_post_status', function($new_status, $old_status, $post) {
            if ($post->post_type === 'hp_listing' && $new_status === 'publish' && get_post_meta($post->ID, '_hpsr_subscription_drafted', true)) {
                delete_post_meta($post->ID, '_hpsr_subscription_drafted');
            }
        }, 10, 3);

        parent::__construct($args);
    }

    public function process_new_order($order_id) {
        $this->log("Processing new order [{$order_id}]", 'info');
        $order = wc_get_order($order_id);
        if(!$order) {
            $this->log("Invalid order object for ID [{$order_id}]", 'error');
            return;
        }

        $is_virtual = true;
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && !$product->is_virtual()) {
                $is_virtual = false;
                break;
            }
        }

        if ($is_virtual || $this->is_subscription_order($order)) {
            try {
                $order->update_status('completed', __('Auto-completed virtual/subscription order.', 'subscription-renewals-for-hivepress'));
                $this->log("Auto-completed order [{$order_id}]", 'info');
            } catch (\Exception $e) {
                $this->log("Error auto-completing order [{$order_id}]: " . $e->getMessage(), 'error');
            }
        }
    }

    public function handle_subscription_activated($subscription) {
        if (!$this->validate_subscription($subscription, __FUNCTION__)) {
            return;
        }

        $user_id = $subscription->get_user_id();
        $subscription_id = $subscription->get_id();
        $this->log("Subscription [$subscription_id] activated for user [$user_id]", 'info');

        if ($this->is_admin_bypass_enabled($user_id)) {
            $this->log("Admin bypass enabled for user [$user_id], skipping activation", 'info');
            return;
        }

        $next_payment = $subscription->get_date('next_payment') ? strtotime($subscription->get_date('next_payment')) : null;
        $package_id = $this->find_package_for_subscription($subscription);

        if ($package_id) {
            $this->activate_package($user_id, $package_id, $subscription_id);
            $this->log("Linked package [$package_id] to subscription [$subscription_id]", 'info');
        } else {
            $this->log("No package found for subscription [$subscription_id]", 'warning');
        }

        $this->update_listings_expiration($user_id, $next_payment);
        $this->republish_drafted_listings($user_id);
    }

    public function handle_subscription_paused($subscription) {
        if (!$this->validate_subscription($subscription, __FUNCTION__)) {
            return;
        }
        $this->log("Subscription [{$subscription->get_id()}] paused for user [{$subscription->get_user_id()}], treating as active", 'info');
        // Listings remain active
    }

    public function handle_subscription_cancelled($subscription) {
        if (!$this->validate_subscription($subscription, __FUNCTION__)) {
            return;
        }

        $user_id = $subscription->get_user_id();
        $subscription_id = $subscription->get_id();
        $this->log("Subscription [$subscription_id] cancelled for user [$user_id]", 'info');

        if ($this->is_admin_bypass_enabled($user_id)) {
            $this->log("Admin bypass enabled for user [$user_id], skipping cancellation", 'info');
            return;
        }

        try {
            $packages = Models\User_Listing_Package::query()->filter([
                'user' => $user_id,
                '_meta' => ['_hp_subscription_id' => (string)$subscription_id],
            ])->get();

            $this->log("Found {$packages->count()} packages for subscription [$subscription_id]", 'info');
            foreach ($packages as $package) {
                $package->delete();
            }
            $this->log("Deleted {$packages->count()} package assignments", 'info');
        } catch (\Exception $e) {
            $this->log("Error deleting packages for subscription [$subscription_id]: " . $e->getMessage(), 'error');
        }

        wp_cache_flush();
        if (!$this->has_active_subscriptions($user_id, $subscription_id)) {
            $this->draft_user_listings($user_id);
            $this->log("No active subscriptions remain for user [$user_id], listings drafted", 'info');
        } else {
            $this->log("User [$user_id] has other active subscriptions", 'info');
        }
    }

    public function handle_subscription_pending_cancel($subscription) {
        if (!$this->validate_subscription($subscription, __FUNCTION__)) {
            return;
        }
        $this->log("Subscription [{$subscription->get_id()}] pending cancellation for user [{$subscription->get_user_id()}], treating as active", 'info');
        // Listings remain active
    }

    public function handle_subscription_renewal($subscription, $renewal_order) {
        if (!$this->validate_subscription($subscription, __FUNCTION__)) {
            return;
        }

        $user_id = $subscription->get_user_id();
        $subscription_id = $subscription->get_id();
        $this->log("Subscription [$subscription_id] renewed for user [$user_id]", 'info');

        if ($this->is_admin_bypass_enabled($user_id)) {
            $this->log("Admin bypass enabled for user [$user_id], skipping renewal", 'info');
            return;
        }

        $next_payment = $subscription->get_date('next_payment') ? strtotime($subscription->get_date('next_payment')) : time();
        $package_id = $this->find_package_for_subscription($subscription);

        if ($package_id) {
            $this->activate_package($user_id, $package_id, $subscription_id);
            $options = get_option('hpsr_settings', []);
            if (!empty($options['reset_submit_on_renewal'])) {
                $package = Models\User_Listing_Package::query()->filter(['user' => $user_id, 'package' => $package_id])->get_first();
                if ($package) {
                    $package->set_submit_limit(get_post_meta($package_id, 'hp_submit_limit', true))->save();
                    $this->log("Reset submit limit for package [$package_id]", 'info');
                }
            }
        }

        $this->update_listings_expiration($user_id, $next_payment);
        $this->republish_drafted_listings($user_id);
        wp_cache_flush();
    }

    public function handle_subscription_payment($subscription) {
        $this->handle_subscription_activated($subscription);
    }

    public function validate_listing_subscription($errors, $listing) {
        if (!$listing || !is_object($listing) || in_array($listing->get_status(), ['draft', 'auto-draft', '', null], true)) {
            return $errors;
        }

        $user_id = get_current_user_id();
        if (!$user_id || $this->is_admin_bypass_enabled($user_id) || $this->has_active_subscriptions($user_id)) {
            return $errors;
        }

        $errors[] = __('You need an active subscription to publish listings.', 'subscription-renewals-for-hivepress');
        return $errors;
    }

    public function sync_all_subscriptions() {
        $this->log("Starting daily sync", 'info');
        $users = Models\User::query()->limit(1000)->get_ids();
        $processed = 0;
        $active_count = 0;

        foreach (array_chunk($users, 20) as $batch) {
            foreach ($batch as $user_id) {
                $subscriptions = wcs_get_users_subscriptions($user_id);
                $has_active = false;
                foreach ($subscriptions as $subscription) {
                    $status = $subscription->get_status();
                    if (in_array($status, ['active', 'pending-cancel'], true)) {
                        $this->handle_subscription_activated($subscription);
                        $has_active = true;
                        $active_count++;
                    } elseif (in_array($status, ['cancelled', 'expired'], true)) {
                        $this->handle_subscription_cancelled($subscription);
                    }
                }
                if (!$has_active && !$this->is_admin_bypass_enabled($user_id)) {
                    $this->draft_user_listings($user_id);
                }
                $processed++;
            }
            sleep(1);
        }

        $this->log("Daily sync completed. Processed $processed users with $active_count active subscriptions.", 'info');
    }

    public function process_stuck_orders() {
        $this->log("Processing stuck orders", 'info');
        $orders = wc_get_orders(['status' => ['pending', 'processing'], 'limit' => 20]);
        $processed = 0;
        $completed = 0;

        foreach ($orders as $order) {
            if ($this->is_subscription_order($order) || array_reduce(iterator_to_array($order->get_items()), function($carry, $item) {
                $product = $item->get_product();
                return $carry && $product && $product->is_virtual();
            }, true)) {
                try {
                    $order->update_status('completed', __('Auto-completed virtual/subscription order.', 'subscription-renewals-for-hivepress'));
                    $completed++;
                } catch (\Exception $e) {
                    $this->log("Error processing order [{$order->get_id()}]: " . $e->getMessage(), 'error');
                }
            }
            $processed++;
        }

        $this->log("Processed $processed stuck orders, $completed completed", 'info');
    }

    protected function is_subscription_order($order) {
        return $order && class_exists('WC_Subscriptions_Order') && wcs_order_contains_subscription($order);
    }

    public function has_active_subscriptions($user_id, $exclude_subscription_id = 0) {
        $subscriptions = wcs_get_users_subscriptions($user_id);
        foreach ($subscriptions as $subscription) {
            if ((int)$subscription->get_id() !== (int)$exclude_subscription_id && in_array($subscription->get_status(), ['active', 'pending-cancel'], true)) {
                return true;
            }
        }
        return false;
    }

    public function is_admin_bypass_enabled($user_id) {
        $options = get_option('hpsr_settings', []);
        return user_can($user_id, 'manage_options') && !empty($options['admin_bypass']);
    }

    protected function find_package_for_subscription($subscription) {
        if (!$this->validate_subscription($subscription, __FUNCTION__)) {
            return null;
        }

        try {
            $subscription_id = $subscription->get_id();
            $package_id = get_post_meta($subscription_id, '_hp_package_id', true);
            if ($package_id && Models\Listing_Package::query()->get_by_id($package_id)) {
                return (int)$package_id;
            }

            foreach ($subscription->get_items() as $item) {
                $product_id = $item->get_product_id();
                $package = Models\Listing_Package::query()->filter(['status' => 'publish', 'product' => $product_id])->get_first();
                if ($package) {
                    update_post_meta($subscription_id, '_hp_package_id', $package->get_id());
                    return $package->get_id();
                }
            }
            return null;
        } catch (\Exception $e) {
            $this->log("Error finding package for subscription [$subscription_id]: " . $e->getMessage(), 'error');
            return null;
        }
    }

    protected function update_listings_expiration($user_id, $expiry_timestamp) {
        $expiry_timestamp = $expiry_timestamp ?: time();
        $listings = Models\Listing::query()->filter(['user' => $user_id, 'status__in' => ['publish', 'draft', 'pending']])->get();

        $this->log("Updating expiration for " . $listings->count() . " listings for user [$user_id] to " . date_i18n('Y-m-d H:i:s', $expiry_timestamp), 'info');
        foreach ($listings as $listing) {
            $listing->set_expired_time($expiry_timestamp)->save(['expired_time']);
        }
        wp_cache_flush();
    }

    public function draft_user_listings($user_id) {
        $listings = Models\Listing::query()->filter(['user' => $user_id, 'status' => 'publish'])->get();
        $this->log("Found " . $listings->count() . " published listings to draft for user [$user_id]", 'info');

        $drafted_count = 0;
        foreach ($listings as $listing) {
            $listing->set_status('draft');
            $listing->set_meta('_hpsr_subscription_drafted', '1');
            $listing->save(['status', '_hpsr_subscription_drafted']);
            $drafted_count++;
            $this->log("Drafted listing [{$listing->get_id()}]", 'info');
        }
        wp_cache_flush();
        return $drafted_count;
    }

    protected function republish_drafted_listings($user_id) {
        $listings = Models\Listing::query()->filter(['user' => $user_id, 'status' => 'draft', '_meta' => ['_hpsr_subscription_drafted' => '1']])->get();
        $this->log("Found " . $listings->count() . " drafted listings to republish for user [$user_id]", 'info');

        $republished = 0;
        foreach ($listings as $listing) {
            $listing->set_status('publish');
            $listing->set_meta('_hpsr_subscription_drafted', null);
            $listing->save(['status', '_hpsr_subscription_drafted']);
            $republished++;
            $this->log("Republished listing [{$listing->get_id()}]", 'info');
        }
        wp_cache_flush();
        return $republished;
    }

    protected function activate_package($user_id, $package_id, $subscription_id) {
        $package = Models\Listing_Package::query()->get_by_id($package_id);
        if (!$package) {
            $this->log("Invalid package [$package_id] for user [$user_id]", 'error');
            return false;
        }

        $existing = Models\User_Listing_Package::query()->filter(['user' => $user_id, 'package' => $package_id])->get_first();
        $data = [
            'user' => $user_id,
            'package' => $package_id,
            'submit_limit' => $package->get_submit_limit(),
            '_hp_subscription_id' => (string)$subscription_id,
        ];

        if ($existing) {
            $existing->fill($data)->save();
            $this->log("Updated package [$package_id] for user [$user_id]", 'info');
        } else {
            (new Models\User_Listing_Package())->fill($data)->save();
            $this->log("Activated package [$package_id] for user [$user_id]", 'info');
        }
        wp_cache_flush();
        return true;
    }

    public function debug_listing_expiry_dates($user_id) {
        $listings = Models\Listing::query()->filter(['user' => $user_id])->get();
        $output = "Listing Expiry Debug for User ID: $user_id\n===========================================\n";

        foreach ($listings as $listing) {
            $expired_time = $listing->get_expired_time();
            $output .= "Listing ID: {$listing->get_id()} - \"{$listing->get_title()}\" (Status: {$listing->get_status()})\n";
            $output .= "  Expired Time: " . ($expired_time ? date_i18n('Y-m-d H:i:s', $expired_time) : 'Not set') . "\n";
            $output .= "  Subscription Drafted: " . (get_post_meta($listing->get_id(), '_hpsr_subscription_drafted', true) ? 'Yes' : 'No') . "\n";
            $output .= "-------------------------------------------\n";
        }

        $this->log($output, 'debug', true);
        return $output;
    }

    private function validate_subscription($subscription, $method) {
        if (!$subscription || !is_object($subscription) || !method_exists($subscription, 'get_user_id')) {
            $this->log("Invalid subscription object in $method", 'error');
            return false;
        }
        return true;
    }

    protected function log($message, $level = 'info', $force = false) {
        $options = get_option('hpsr_settings', []);
        if (!$force && $level === 'debug' && empty($options['debug_mode'])) {
            return;
        }

        (new \HivePress\SubscriptionRenewals\Helpers\Logger())->log($message, $level, $force);
    }

    public function maybe_hide_visibility_controls() {
        $user_id = get_current_user_id();
        if ($user_id && !$this->has_active_subscriptions($user_id) && !$this->is_admin_bypass_enabled($user_id)) {
            if (hivepress()->router->get_current_route_name() === 'listing_edit_page') {
                ?>
                <style type="text/css">
                    .hp-listing__action--hide {
                        display: none !important;
                    }
                </style>
                <?php
            }
        }
    }

    public function hpsr_get_stats_summary() {
        $stats = [
            'subscriptions' => ['active' => 0, 'pending-cancel' => 0, 'cancelled' => 0, 'on-hold' => 0, 'expired' => 0, 'trash' => 0, 'total' => 0],
            'packages' => ['active' => 0, 'inactive' => 0, 'admin_bypass' => 0, 'total' => 0],
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
            $stats['listings']['subscription_drafted'] = \HivePress\Models\Listing::query()->filter([
                'status' => 'draft',
                '_meta' => ['_hpsr_subscription_drafted' => '1']
            ])->get()->count();
        }

        set_transient('hpsr_stats_summary', $stats, 15 * MINUTE_IN_SECONDS);
        return $stats;
    }
}