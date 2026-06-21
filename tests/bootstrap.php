<?php
/**
 * PHPUnit Bootstrap
 *
 * @package HeadlessCompanion
 */

// Define path constants.
define( 'HC_TESTS_DIR', __DIR__ );
define( 'HC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );

// Define mock ABSPATH and functions if not running inside real WordPress test suite.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', HC_PLUGIN_DIR );
}

// Load Composer autoloader.
if ( file_exists( HC_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once HC_PLUGIN_DIR . 'vendor/autoload.php';
}

// Load the autoloader.
require_once HC_PLUGIN_DIR . 'includes/Autoloader.php';
\HeadlessCompanion\Autoloader::register();

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		global $hc_registered_actions;
		if ( ! is_array( $hc_registered_actions ) ) {
			$hc_registered_actions = array();
		}
		$hc_registered_actions[] = array(
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
}
if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $callback ) {}
}
if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook( $file, $callback ) {}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		global $hc_mock_options;
		if ( is_array( $hc_mock_options ) && array_key_exists( $option, $hc_mock_options ) ) {
			return $hc_mock_options[ $option ];
		}
		return $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		global $hc_mock_options;
		if ( ! is_array( $hc_mock_options ) ) {
			$hc_mock_options = array();
		}
		$hc_mock_options[ $option ] = $value;
		return true;
	}
}
if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
		return substr( bin2hex( random_bytes( $length ) ), 0, $length );
	}
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return dirname( $file ) . '/';
	}
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'https://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}
if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	function register_rest_route( $namespace, $route, $args = array(), $override = false ) {
		global $hc_registered_routes;
		if ( ! is_array( $hc_registered_routes ) ) {
			$hc_registered_routes = array();
		}
		$hc_registered_routes[] = array(
			'namespace' => $namespace,
			'route'     => $route,
			'args'      => $args,
		);
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		global $hc_current_user_can_result;
		return isset( $hc_current_user_can_result ) ? $hc_current_user_can_result : true;
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( $path = '' ) {
		return 'https://example.com/wp-json/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		return 'mock_nonce_' . $action;
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return $url;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		return array_merge( $defaults, $args );
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		private $params = array();
		public $method = '';
		public $route = '';
		public function __construct( $method = '', $route = '' ) {
			$this->method = $method;
			$this->route  = $route;
		}
		public function set_param( $key, $value ) {
			$this->params[ $key ] = $value;
		}
		public function get_param( $key ) {
			return isset( $this->params[ $key ] ) ? $this->params[ $key ] : null;
		}
		public function has_param( $key ) {
			return array_key_exists( $key, $this->params );
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		public $data;
		public $status;
		public function __construct( $data = null, $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}
		public function get_data() {
			return $this->data;
		}
		public function get_status() {
			return $this->status;
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code;
		public $message;
		public $data;
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}
		public function get_error_code() {
			return $this->code;
		}
		public function get_error_message() {
			return $this->message;
		}
	}
}

