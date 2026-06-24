=== Headless Companion ===
Contributors: iamchitti
Tags: headless, decoupled, nextjs, webhook, purge, caching
Requires at least: 6.0
Tested up to: 7.0
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
* Initial release.
* Bootstrap plugin foundation and namespaced PSR-4 autoloader.
* React-powered settings panel using native WordPress components.
* Secure REST API endpoints for configuration management.
* Webhook trigger system on post status transitions.
* Background revalidation queue using Action Scheduler (with WP-Cron fallback).
* HMAC-SHA256 cryptographic signing of outgoing payloads.
* Smart Cache Invalidation mapping related URLs for posts and term updates.
* Secure live draft preview routing and authentication callback system.
* WPGraphQL extensions supporting preview schema.
* Custom WP-CLI `wp headless-companion` administration namespace.
* GitHub Actions CI workflows for automated linting, testing, and release bundling.

== Development & Contribution ==

We welcome contributions to Headless Companion! Follow these instructions to set up the plugin for local development:

=== Prerequisites ===
* Node.js (version specified in .nvmrc)
* Composer (v2)
* PHP 7.4 or later

=== Local Setup ===
1. Clone the repository into your WordPress plugins folder (`/wp-content/plugins/`).
2. Run `npm install` to install local JavaScript build tools and dependencies.
3. Run `composer install` to install PHP development tools (PHPUnit, PHPCS).

=== Build Scripts ===
* `npm run dev` or `npm run watch`: Starts the webpack watcher for React changes, compiling in real-time.
* `npm run build`: Compiles production-optimized and minified JavaScript/CSS assets.

=== Testing & Linting ===
* `npm run lint`: Runs both JS and PHPCS linters to verify syntax and standard compliance.
* `npm run lint:js`: Runs WordPress ESLint/Prettier checks on JavaScript code.
* `npm run lint:php`: Runs PHP CodeSniffer standard checks.
* `npm run test`: Runs the PHPUnit unit test suite.
