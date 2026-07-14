=== Decouplix Previews & Smart Purge ===
Contributors: iamchitti
Tags: headless, decoupled, nextjs, webhook, purge
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 0.1.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimize headless WordPress editing with secure previews, webhook dispatch queueing, automated CDN cache invalidation, and custom WP-CLI tools.

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
3. Navigate to **Settings > Decouplix** to configure your front-end URL, webhook secrets, and cache configuration.

== Changelog ==

= 0.1.3 =
* Cleanup development and test configuration files from release package.

= 0.1.2 =
* Fix generic option, constant, and asset prefixes (changed 'hc' prefix to 'decouplix').

= 0.1.1 =
* Rename plugin to Decouplix Previews & Smart Purge.
* Update documentation for public GitHub repository and external integrations.

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
* Custom WP-CLI `wp decouplix` administration namespace.
* GitHub Actions CI workflows for automated linting, testing, and release bundling.

The plugin is actively developed and maintained at: https://github.com/i-am-chitti/decouplix

We welcome contributions to Decouplix! Follow these instructions to set up the plugin for local development:

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

== Screenshots ==

1. React-powered Decouplix settings dashboard with clean, horizontal settings alignment.
2. Setting configuration details for Webhook URL, Webhook Secret, and Cache Purge Endpoints.
3. Secure Live Draft Preview routing workflow from the Gutenberg editor.
4. Custom WP-CLI `wp decouplix` command suite for advanced administration.

== External Services ==

This plugin is designed to connect to external frontend frameworks, content delivery networks (CDNs), or deployment hosting providers (such as Vercel, Netlify, or custom webhook endpoints) to handle cache revalidation and trigger frontend deploys.

Depending on the Webhook URL and Cache Purge Endpoints configured by the administrator in the settings panel:
* Outgoing HTTP POST requests (containing post metadata, URLs to be purged, and cryptographic signatures) will be sent to the user-specified endpoints when posts/pages are published or modified.
* Default/example placeholders reference Vercel. For more details on these integrations, see:
  * Vercel Terms of Service: https://vercel.com/legal/terms
  * Vercel Privacy Policy: https://vercel.com/legal/privacy-policy
