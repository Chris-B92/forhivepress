=== Subscription Renewals for HivePress ===
Contributors: chrisbruce
Tags: hivepress, woocommerce, subscriptions, listings, renewals
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automates listing renewals with WooCommerce Subscriptions in HivePress.

== Description ==

**Subscription Renewals for HivePress** seamlessly integrates WooCommerce Subscriptions with HivePress Paid Listings, automating the renewal and management of listings based on subscription status.

The plugin handles all of the following:

**For users with an active subscription:**

* Automatically updates all listing expiry dates to match their latest subscription's expiry date at each renewal
* Respects user's listing front-end visibility choices so drafts stay as drafts and published listings stay published
* Treats subscriptions with a pending cancellation status as active to respect a user's full billing period
* Automatically restores front-end visibility controls when a subscription is active

**For users with a cancelled or expired subscription:**

* Automatically drafts all user listings when a subscription is cancelled or expired
* Automatically hides front-end visibility controls when a subscription is cancelled or expired
* Automatically deletes and removes assigned packages when a subscription is cancelled or expired

**When a user resubscribes:**

* Automatically restores front-end visibility controls
* Restores drafted listings to published status if they were draft due to subscription status

**Additional features:**

* Force-completes past and future orders for WooCommerce virtual items
* Option to show the front-end WP toolbar to admins only
* Admin bypass option to allow administrators to publish listings without requiring a subscription
* Debug logs with user-friendly information
* Manual sync tool for user subscription data
* Process stuck orders functionality
* Cron job health check
* User lookup tool to view user subscriptions, packages, and listings

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/subscription-renewals-for-hivepress` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the "Subscription Renewals" menu item in the admin area to configure the plugin.

== Requirements ==

* WordPress 5.0 or higher
* PHP 7.0 or higher
* HivePress core plugin
* WooCommerce plugin
* WooCommerce Subscriptions plugin
* HivePress Paid Listings extension

== Frequently Asked Questions ==

= How does the plugin handle subscription renewals? =

When a subscription renews, the plugin automatically updates the expiry dates on all of the user's listings to match the new subscription expiry date, maintaining their visibility status.

= What happens when a subscription is cancelled? =

When a subscription is cancelled, all the user's published listings are automatically changed to draft status, and their ability to control visibility is hidden.

= Does this plugin work with multiple subscriptions per user? =

Yes, if a user has multiple active subscriptions, their listings will remain active until all subscriptions expire or are cancelled.

= Can administrators bypass subscription requirements? =

Yes, there's an "Admin Bypass" option that allows administrators to publish listings without selecting a package or checking out.

== Screenshots ==

1. Admin Dashboard
2. User Lookup Tool
3. Settings Page
4. Debug Logs

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of Subscription Renewals for HivePress.