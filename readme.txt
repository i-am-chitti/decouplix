=== Headless Companion ===
Contributors: deepakkumar
Tags: headless, decoupled, nextjs, webhook, purge, caching
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 0.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimize the content editing experience for headless (decoupled) WordPress sites. Handles secure previewing, webhook management, automated CDN cache invalidation, and custom WP-CLI tools.

== Description ==

An enterprise-ready WordPress plugin that optimizes the content editing experience for headless (decoupled) sites. It handles secure previewing, webhook management, automated CDN cache invalidation, and custom WP-CLI tools.

=== Key Features ===
* **Settings Panel:** A React-powered settings panel built using native WordPress component styling.
* **Webhook Triggering:** Fires webhook payloads to decoupled frontend when posts are published/modified.
* **Revalidation Queue:** Uses a queue system (Action Scheduler with standard WP Cron fallback) to send webhooks asynchronously.
* **Smart Cache Invalidation:** Automatically invalidates cached frontend URLs when related content changes.
* **Headless Previews:** Directs the classic/Gutenberg preview button to target the frontend API route securely.
* **WP-CLI Commands:** Automate webhooks, purging, and configuration checks from the command line.

== Installation ==

1. Upload the plugin directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > Headless Companion** to configure your front-end URL, webhook secrets, and cache configuration.

== Changelog ==

= 0.1.0 =
* Initial release. Bootstrap plugin foundation and autoloader.
