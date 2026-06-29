<?php
/**
 * GraphQL Class
 *
 * @package Decouplix
 */

namespace Decouplix;

defined( 'ABSPATH' ) || exit;

/**
 * Class GraphQL
 *
 * Extends WPGraphQL schema and adds secure draft preview authentication.
 */
class GraphQL {

	/**
	 * Singleton instance of GraphQL.
	 *
	 * @var GraphQL|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return GraphQL
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_filter( 'determine_current_user', array( $this, 'authenticate_preview_request' ), 99 );
		add_action( 'graphql_register_types', array( $this, 'register_graphql_fields' ) );
	}

	/**
	 * Authenticate the GraphQL preview request if a valid secret header is present.
	 *
	 * @param int|false $user_id The current authenticated user ID, or false.
	 * @return int|false Modified user ID.
	 */
	public function authenticate_preview_request( $user_id ) {
		// Only override authentication on GraphQL HTTP requests where our custom secret header is present.
		$is_graphql_request = defined( 'GRAPHQL_HTTP_REQUEST' ) && GRAPHQL_HTTP_REQUEST;
		$has_secret_header  = isset( $_SERVER['HTTP_X_HC_SECRET'] ) && ! empty( $_SERVER['HTTP_X_HC_SECRET'] );

		if ( ! $is_graphql_request || ! $has_secret_header ) {
			return $user_id;
		}

		$settings = get_option( 'hc_settings', array() );
		$secret   = isset( $settings['webhook_secret'] ) ? $settings['webhook_secret'] : '';

		if ( empty( $secret ) ) {
			return $user_id;
		}

		$request_secret = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_HC_SECRET'] ) );

		if ( ! hash_equals( $secret, $request_secret ) ) {
			return $user_id;
		}

		// Retrieve an administrator user to authenticate the request.
		$admin_users = get_users(
			array(
				'role'    => 'administrator',
				'number'  => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
			)
		);

		if ( ! empty( $admin_users ) && isset( $admin_users[0]->ID ) ) {
			return $admin_users[0]->ID;
		}

		return $user_id;
	}

	/**
	 * Register custom WPGraphQL fields.
	 *
	 * @return void
	 */
	public function register_graphql_fields() {
		if ( ! function_exists( 'register_graphql_field' ) ) {
			return;
		}

		register_graphql_field(
			'ContentNode',
			'headlessPreviewUrl',
			array(
				'type'        => 'String',
				'description' => __( 'Headless preview URL for the content node.', 'decouplix' ),
				'resolve'     => function ( $post ) {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					if ( ! isset( $post->databaseId ) ) {
						return null;
					}
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$wp_post = get_post( $post->databaseId );
					if ( ! $wp_post ) {
						return null;
					}
					return Preview::get_instance()->filter_preview_link( '', $wp_post );
				},
			)
		);
	}
}
