<?php
/**
 * Plugin Name: Salus iT500 Heating Schema
 * Description: Configure Salus iT500 API credentials and test the connection.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Salus iT500 Heating Schema
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Salus_iT500_Heating_Schema {
	const OPTION_NAME = 'salus_i500_credentials';
	const PAGE_SLUG   = 'salus-i500-heating-schema';
	const TEST_ACTION = 'salus_i500_test_connection';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_' . self::TEST_ACTION, array( __CLASS__, 'test_connection' ) );
	}

	public static function add_settings_page() {
		add_options_page(
			__( 'Salus iT500', 'salus-i500-heating-schema' ),
			__( 'Salus iT500', 'salus-i500-heating-schema' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'salus_i500_settings',
			self::OPTION_NAME,
			array( 'sanitize_callback' => array( __CLASS__, 'sanitize_credentials' ) )
		);

		add_settings_section(
			'salus_i500_account',
			__( 'Salus account', 'salus-i500-heating-schema' ),
			array( __CLASS__, 'render_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'salus_i500_username',
			__( 'Username', 'salus-i500-heating-schema' ),
			array( __CLASS__, 'render_username_field' ),
			self::PAGE_SLUG,
			'salus_i500_account'
		);

		add_settings_field(
			'salus_i500_password',
			__( 'Password', 'salus-i500-heating-schema' ),
			array( __CLASS__, 'render_password_field' ),
			self::PAGE_SLUG,
			'salus_i500_account'
		);
	}

	public static function sanitize_credentials( $input ) {
		$old = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $old ) ) {
			$old = array();
		}
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$password = isset( $input['password'] ) ? (string) $input['password'] : '';
		if ( ! empty( $input['clear_password'] ) ) {
			$password = '';
		} elseif ( '' === $password && isset( $old['password'] ) ) {
			$password = (string) $old['password'];
		}

		return array(
			'username' => isset( $input['username'] ) ? sanitize_text_field( $input['username'] ) : '',
			'password' => $password,
		);
	}

	public static function render_section_description() {
		echo '<p>' . esc_html__( 'Credentials are stored in this WordPress site and sent to the Salus API only when you test the connection.', 'salus-i500-heating-schema' ) . '</p>';
		echo '<p>' . esc_html__( 'This uses the unofficial iT500 API authentication flow. The password is MD5-hashed as required by that protocol; this is not a general-purpose password security measure.', 'salus-i500-heating-schema' ) . '</p>';
	}

	public static function render_username_field() {
		$credentials = self::get_credentials();
		printf(
			'<input type="text" class="regular-text" autocomplete="username" name="%1$s[username]" value="%2$s">',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $credentials['username'] )
		);
	}

	public static function render_password_field() {
		$credentials = self::get_credentials();
		?>
		<input type="password" class="regular-text" autocomplete="new-password" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[password]" value="">
		<p class="description">
			<?php
			echo ! empty( $credentials['password'] )
				? esc_html__( 'A password is saved. Leave this blank to keep it, or select the checkbox below to clear it.', 'salus-i500-heating-schema' )
				: esc_html__( 'No password is saved.', 'salus-i500-heating-schema' );
			?>
		</p>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[clear_password]" value="1">
			<?php esc_html_e( 'Clear the saved password', 'salus-i500-heating-schema' ); ?>
		</label>
		<?php
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Salus iT500', 'salus-i500-heating-schema' ); ?></h1>
			<?php self::render_connection_notice(); ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'salus_i500_settings' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save credentials', 'salus-i500-heating-schema' ) );
				?>
			</form>
			<hr>
			<h2><?php esc_html_e( 'Connection test', 'salus-i500-heating-schema' ); ?></h2>
			<p><?php esc_html_e( 'Save your credentials before testing. This checks authentication only and does not change any heating settings.', 'salus-i500-heating-schema' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::TEST_ACTION ); ?>">
				<?php wp_nonce_field( self::TEST_ACTION ); ?>
				<?php submit_button( __( 'Test connection', 'salus-i500-heating-schema' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	public static function test_connection() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to test this connection.', 'salus-i500-heating-schema' ) );
		}
		check_admin_referer( self::TEST_ACTION );

		$credentials = self::get_credentials();
		if ( '' === $credentials['username'] || '' === $credentials['password'] ) {
			self::store_connection_result(
				'error',
				__( 'Enter and save both a username and password before testing.', 'salus-i500-heating-schema' )
			);
			self::redirect_to_settings();
		}

		$url      = 'https://sal-emea-p01-api.arrayent.com/acc/applications/SalusService/sessions';
		$response = wp_remote_post(
			$url,
			array(
				'timeout'   => 15,
				'sslverify' => true,
				'headers'   => array(
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'      => wp_json_encode(
					array(
						'username' => $credentials['username'],
						'password' => md5( $credentials['password'] ),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			self::store_connection_result( 'error', $response->get_error_message() );
			self::redirect_to_settings();
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			self::store_connection_result(
				'error',
				sprintf(
					/* translators: %d is the HTTP status code returned by the Salus API. */
					__( 'The Salus API returned HTTP %d during authentication.', 'salus-i500-heating-schema' ),
					(int) $status
				)
			);
			self::redirect_to_settings();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['userId'] ) || empty( $body['securityToken'] ) ) {
			self::store_connection_result(
				'error',
				__( 'The response did not include the expected authentication details. Check the credentials and API compatibility.', 'salus-i500-heating-schema' )
			);
			self::redirect_to_settings();
		}

		self::store_connection_result( 'success', __( 'Authentication succeeded. The session token was not saved.', 'salus-i500-heating-schema' ) );
		self::redirect_to_settings();
	}

	private static function get_credentials() {
		$credentials = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $credentials ) ) {
			$credentials = array();
		}

		return array(
			'username' => isset( $credentials['username'] ) ? (string) $credentials['username'] : '',
			'password' => isset( $credentials['password'] ) ? (string) $credentials['password'] : '',
		);
	}

	private static function store_connection_result( $type, $message ) {
		set_transient(
			'salus_i500_connection_' . get_current_user_id(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			MINUTE_IN_SECONDS
		);
	}

	private static function render_connection_notice() {
		$key    = 'salus_i500_connection_' . get_current_user_id();
		$result = get_transient( $key );
		if ( ! is_array( $result ) || empty( $result['message'] ) ) {
			return;
		}

		delete_transient( $key );
		$type  = isset( $result['type'] ) && 'success' === $result['type'] ? 'success' : 'error';
		$class = 'success' === $type ? 'notice-success' : 'notice-error';
		printf(
			'<div class="notice %1$s"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $result['message'] )
		);
	}

	private static function redirect_to_settings() {
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) );
		exit;
	}
}

Salus_iT500_Heating_Schema::init();
