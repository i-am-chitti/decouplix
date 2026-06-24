# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-06-24

### Added
- Namespaced PSR-4 autoloader (`HeadlessCompanion\Autoloader`) and plugin bootstrap.
- React settings dashboard panel using native `@wordpress/components`.
- Secure REST API endpoints for settings storage and retrieval.
- Automated webhook dispatch on post status transitions (publish, update, trash).
- Background queue handler using Action Scheduler (with WP-Cron fallback).
- Outgoing webhook signature signing via HMAC-SHA256.
- Smart Cache Invalidation mapping and path purging (posts, terms, home page).
- Secure live draft preview routing and authentication callback.
- WPGraphQL preview schema extensions.
- Custom WP-CLI `wp headless` command suite.
- GitHub Actions CI workflow for linting & tests, and release bundler workflow.
