<?php
/**
 * Plugin Name: Salus iT500 Heating Schema
 * Description: View Salus iT500 weekly heating programme values.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Salus iT500 Heating Schema
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Salus_iT500_Heating_Schema {
	const OPTION_NAME        = 'salus_i500_credentials';
	const SCHEMA_PAGE_SLUG   = 'salus-i500-schema';
	const SETTINGS_PAGE_SLUG = 'salus-i500-settings';
	const TEST_ACTION        = 'salus_i500_test_connection';
	const SCHEMA_ACTION      = 'salus_i500_refresh_schema';
	const API_HOST           = 'https://sal-emea-p01-api.arrayent.com';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_schema_styles' ) );
		add_action( 'admin_post_' . self::TEST_ACTION, array( __CLASS__, 'test_connection' ) );
		add_action( 'admin_post_' . self::SCHEMA_ACTION, array( __CLASS__, 'refresh_schema' ) );
	}

	public static function add_settings_page() {
		$heater_icon = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M3 2h2v3h2V2h2v3h2V2h2v3h2V2h2v3h1v12h-2v-3H4v3H2V5h1V2zm1 5v5h12V7H4zm2 1h2v3H6V8zm4 0h2v3h-2V8zm4 0h2v3h-2V8z"/></svg>'
		);
		add_menu_page(
			__( 'Heating schema', 'salus-i500-heating-schema' ),
			__( 'Heating schema', 'salus-i500-heating-schema' ),
			'manage_options',
			self::SCHEMA_PAGE_SLUG,
			array( __CLASS__, 'render_schema_page' ),
			$heater_icon,
			58
		);
		add_submenu_page(
			self::SCHEMA_PAGE_SLUG,
			__( 'Schema', 'salus-i500-heating-schema' ),
			__( 'Schema', 'salus-i500-heating-schema' ),
			'manage_options',
			self::SCHEMA_PAGE_SLUG,
			array( __CLASS__, 'render_schema_page' )
		);

		add_options_page(
			__( 'Salus iT500', 'salus-i500-heating-schema' ),
			__( 'Salus iT500', 'salus-i500-heating-schema' ),
			'manage_options',
			self::SETTINGS_PAGE_SLUG,
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function enqueue_schema_styles( $hook_suffix ) {
		if ( 'toplevel_page_' . self::SCHEMA_PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_register_style( 'salus-i500-schema', false, array(), '0.1.0' );
		wp_enqueue_style( 'salus-i500-schema' );
		wp_add_inline_style(
			'salus-i500-schema',
			'.salus-schema-intro{max-width:760px;color:#50575e}.salus-schema-device{margin:28px 0 36px}.salus-schema-zone{margin:20px 0 28px}.salus-schema-days{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;max-width:1100px}.salus-schema-day{background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:14px 16px;box-shadow:0 1px 2px rgba(0,0,0,.04)}.salus-schema-day h4{margin:0 0 12px;font-size:14px}.salus-schema-slots{display:flex;flex-wrap:wrap;gap:8px}.salus-schema-slot{display:inline-flex;align-items:center;gap:8px;padding:7px 10px;background:#f0f6fc;border:1px solid #c5d9ed;border-radius:4px;color:#1d2327}.salus-schema-slot time{font-weight:600}.salus-schema-slot strong{color:#135e96}.salus-schema-empty{color:#646970;font-style:italic}.salus-schema-raw{margin-top:12px;color:#646970}.salus-schema-raw code{white-space:normal;overflow-wrap:anywhere}.salus-schema-warning{color:#b32d2e}@media(max-width:600px){.salus-schema-days{grid-template-columns:1fr}}'
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
			self::SETTINGS_PAGE_SLUG
		);

		add_settings_field(
			'salus_i500_username',
			__( 'Username', 'salus-i500-heating-schema' ),
			array( __CLASS__, 'render_username_field' ),
			self::SETTINGS_PAGE_SLUG,
			'salus_i500_account'
		);

		add_settings_field(
			'salus_i500_password',
			__( 'Password', 'salus-i500-heating-schema' ),
			array( __CLASS__, 'render_password_field' ),
			self::SETTINGS_PAGE_SLUG,
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
				do_settings_sections( self::SETTINGS_PAGE_SLUG );
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

	public static function render_schema_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$schema = get_transient( self::schema_cache_key() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Heating schema', 'salus-i500-heating-schema' ); ?></h1>
			<p class="salus-schema-intro"><?php esc_html_e( 'Your heating week at a glance. Each time shows when the target temperature changes; it remains in effect until the next listed time. Temperatures use the unit configured on your thermostat. This page is read-only.', 'salus-i500-heating-schema' ); ?></p>
			<?php self::render_connection_notice(); ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SCHEMA_ACTION ); ?>">
				<?php wp_nonce_field( self::SCHEMA_ACTION ); ?>
				<?php submit_button( __( 'Refresh schema', 'salus-i500-heating-schema' ), 'secondary', 'submit', false ); ?>
			</form>
			<?php
			if ( is_array( $schema ) && ! empty( $schema['devices'] ) ) {
				foreach ( $schema['devices'] as $device ) {
					self::render_device_schema( $device );
				}
			} elseif ( is_array( $schema ) && isset( $schema['devices'] ) ) {
				echo '<p>' . esc_html__( 'No devices were returned for this account.', 'salus-i500-heating-schema' ) . '</p>';
			} else {
				echo '<p>' . esc_html__( 'No schema has been loaded yet. Select “Refresh schema” to read it from Salus.', 'salus-i500-heating-schema' ) . '</p>';
			}
			?>
		</div>
		<?php
	}

	private static function render_device_schema( $device ) {
		?>
		<section class="salus-schema-device">
			<h2><?php echo esc_html( $device['name'] ); ?></h2>
		<?php
		foreach ( $device['zones'] as $zone ) {
			?>
			<section class="salus-schema-zone">
				<h3><?php echo esc_html( $zone['name'] ); ?></h3>
				<div class="salus-schema-days">
					<?php foreach ( $zone['days'] as $day ) : ?>
						<article class="salus-schema-day">
							<h4><?php echo esc_html( $day['name'] ); ?></h4>
							<?php $slots = self::decode_heating_program( $day['value'] ); ?>
							<?php if ( is_wp_error( $slots ) ) : ?>
								<p class="salus-schema-warning"><?php esc_html_e( 'This programme could not be decoded.', 'salus-i500-heating-schema' ); ?></p>
							<?php elseif ( empty( $slots ) ) : ?>
								<p class="salus-schema-empty"><?php esc_html_e( 'No temperature changes are set for this day.', 'salus-i500-heating-schema' ); ?></p>
							<?php else : ?>
								<div class="salus-schema-slots">
									<?php foreach ( $slots as $slot ) : ?>
										<span class="salus-schema-slot">
											<time><?php echo esc_html( $slot['time'] ); ?></time>
											<span aria-hidden="true">&rarr;</span>
											<strong><?php echo esc_html( $slot['temperature'] ); ?>&deg;</strong>
										</span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<details class="salus-schema-raw">
								<summary><?php esc_html_e( 'View original device value', 'salus-i500-heating-schema' ); ?></summary>
								<code><?php echo esc_html( $day['value'] ); ?></code>
							</details>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
			<?php
		}
		?>
		</section>
		<?php
	}

	public static function refresh_schema() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to refresh the heating schema.', 'salus-i500-heating-schema' ) );
		}
		check_admin_referer( self::SCHEMA_ACTION );

		$credentials = self::get_credentials();
		if ( '' === $credentials['username'] || '' === $credentials['password'] ) {
			self::store_connection_result(
				'error',
				__( 'Enter and save your Salus username and password in Settings > Salus iT500 first.', 'salus-i500-heating-schema' )
			);
			self::redirect_to_schema();
		}

		$auth = self::authenticate( $credentials );
		if ( is_wp_error( $auth ) ) {
			self::store_connection_result( 'error', $auth->get_error_message() );
			self::redirect_to_schema();
		}

		$device_list = self::api_post(
			'getDeviceList',
			array(
				'userId'   => $auth['userId'],
				'secToken' => $auth['securityToken'],
			)
		);
		if ( is_wp_error( $device_list ) ) {
			self::store_connection_result( 'error', $device_list->get_error_message() );
			self::redirect_to_schema();
		}

		$devices_xml = self::parse_api_xml( $device_list, 'getDeviceListResponse' );
		if ( is_wp_error( $devices_xml ) ) {
			self::store_connection_result( 'error', $devices_xml->get_error_message() );
			self::redirect_to_schema();
		}

		$device_nodes = $devices_xml->xpath( '//*[local-name()="devList"]' );
		if ( false === $device_nodes ) {
			self::store_connection_result( 'error', __( 'Could not read the Salus device list.', 'salus-i500-heating-schema' ) );
			self::redirect_to_schema();
		}

		$devices = array();
		foreach ( $device_nodes as $device_node ) {
			$device_id = self::xml_child_value( $device_node, 'devId' );
			if ( '' === $device_id ) {
				continue;
			}

			$attributes_response = self::api_post(
				'getDeviceAttributesWithValues',
				array(
					'userId'   => $auth['userId'],
					'secToken' => $auth['securityToken'],
					'devId'    => $device_id,
				)
			);
			if ( is_wp_error( $attributes_response ) ) {
				self::store_connection_result( 'error', $attributes_response->get_error_message() );
				self::redirect_to_schema();
			}

			$attributes_xml = self::parse_api_xml( $attributes_response, 'getDeviceAttributesWithValuesResponse' );
			if ( is_wp_error( $attributes_xml ) ) {
				self::store_connection_result( 'error', $attributes_xml->get_error_message() );
				self::redirect_to_schema();
			}

			$attributes = self::extract_device_attributes( $attributes_xml );
			if ( is_wp_error( $attributes ) ) {
				self::store_connection_result( 'error', $attributes->get_error_message() );
				self::redirect_to_schema();
			}

			$zones = array();
			foreach ( array( 'A' => __( 'Heating zone 1', 'salus-i500-heating-schema' ), 'B' => __( 'Heating zone 2', 'salus-i500-heating-schema' ) ) as $prefix => $zone_name ) {
				$days = array();
				$day_names = array(
					__( 'Monday', 'salus-i500-heating-schema' ),
					__( 'Tuesday', 'salus-i500-heating-schema' ),
					__( 'Wednesday', 'salus-i500-heating-schema' ),
					__( 'Thursday', 'salus-i500-heating-schema' ),
					__( 'Friday', 'salus-i500-heating-schema' ),
					__( 'Saturday', 'salus-i500-heating-schema' ),
					__( 'Sunday', 'salus-i500-heating-schema' ),
				);
				foreach ( $day_names as $day_index => $day_name ) {
					$attribute_name = $prefix . sprintf( '%02d', $day_index );
					if ( ! array_key_exists( $attribute_name, $attributes ) ) {
						continue;
					}
					$days[] = array(
						'name'  => $day_name,
						'value' => $attributes[ $attribute_name ],
					);
				}

				if ( ! empty( $days ) ) {
					$zones[] = array(
						'name' => $zone_name,
						'days' => $days,
					);
				}
			}

			$devices[] = array(
				'name'  => self::xml_child_value( $device_node, 'devName' ),
				'zones' => $zones,
			);
		}

		set_transient(
			self::schema_cache_key(),
			array( 'devices' => $devices ),
			5 * MINUTE_IN_SECONDS
		);
		self::store_connection_result( 'success', __( 'Heating schema refreshed from Salus.', 'salus-i500-heating-schema' ) );
		self::redirect_to_schema();
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

		$auth = self::authenticate( $credentials );
		if ( is_wp_error( $auth ) ) {
			self::store_connection_result( 'error', $auth->get_error_message() );
			self::redirect_to_settings();
		}
		self::store_connection_result( 'success', __( 'Authentication succeeded. The session token was not saved.', 'salus-i500-heating-schema' ) );
		self::redirect_to_settings();
	}

	private static function authenticate( $credentials ) {
		$response = wp_remote_post(
			self::API_HOST . '/acc/applications/SalusService/sessions',
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
			return new WP_Error( 'salus_connection_failed', $response->get_error_message() );
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'salus_auth_http_error',
				sprintf(
					/* translators: %d is the HTTP status code returned by the Salus API. */
					__( 'The Salus API returned HTTP %d during authentication.', 'salus-i500-heating-schema' ),
					(int) $status
				)
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['userId'] ) || empty( $body['securityToken'] ) ) {
			return new WP_Error(
				'salus_auth_invalid_response',
				__( 'The response did not include the expected authentication details. Check the credentials and API compatibility.', 'salus-i500-heating-schema' )
			);
		}

		return $body;
	}

	private static function api_post( $path, $data ) {
		$response = wp_remote_post(
			self::API_HOST . '/zdk/services/zamapi/' . $path,
			array(
				'timeout'   => 15,
				'sslverify' => true,
				'headers'   => array( 'Accept' => 'application/xml, text/xml' ),
				'body'      => $data,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'salus_api_request_failed', $response->get_error_message() );
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'salus_api_http_error',
				sprintf(
					/* translators: %d is the HTTP status code returned by the Salus API. */
					__( 'The Salus API returned HTTP %d while reading the heating schema.', 'salus-i500-heating-schema' ),
					(int) $status
				)
			);
		}

		return wp_remote_retrieve_body( $response );
	}

	private static function parse_api_xml( $body, $expected_response ) {
		if ( ! function_exists( 'simplexml_load_string' ) ) {
			return new WP_Error( 'salus_xml_unavailable', __( 'PHP SimpleXML is required to read Salus API responses.', 'salus-i500-heating-schema' ) );
		}

		$previous = libxml_use_internal_errors( true );
		$xml      = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		if ( false === $xml ) {
			return new WP_Error( 'salus_xml_invalid', __( 'The Salus API returned invalid XML while reading the heating schema.', 'salus-i500-heating-schema' ) );
		}

		$matches = $xml->xpath( '//*[local-name()="' . $expected_response . '"]' );
		if ( false === $matches || empty( $matches ) ) {
			return new WP_Error( 'salus_xml_unexpected', __( 'The Salus API response did not contain the expected schema data.', 'salus-i500-heating-schema' ) );
		}

		return $matches[0];
	}

	private static function extract_device_attributes( $xml ) {
		$attribute_nodes = $xml->xpath( './/*[local-name()="attrList"]' );
		if ( false === $attribute_nodes ) {
			return new WP_Error( 'salus_attributes_invalid', __( 'Could not read device attributes from the Salus response.', 'salus-i500-heating-schema' ) );
		}

		$attributes = array();
		foreach ( $attribute_nodes as $attribute_node ) {
			$name = self::xml_child_value( $attribute_node, 'name' );
			if ( '' !== $name ) {
				$attributes[ $name ] = self::xml_child_value( $attribute_node, 'value' );
			}
		}

		return $attributes;
	}

	private static function decode_heating_program( $program ) {
		$program = trim( (string) $program );
		if ( '' === $program ) {
			return array();
		}
		if ( 0 !== strlen( $program ) % 4 ) {
			return new WP_Error( 'salus_schedule_invalid_length', __( 'The programme value has an unsupported format.', 'salus-i500-heating-schema' ) );
		}

		$slots = array();
		for ( $offset = 0; $offset < strlen( $program ); $offset += 4 ) {
			$group  = substr( $program, $offset, 4 );
			$values = array();
			for ( $index = 0; $index < 4; $index++ ) {
				$value = ord( $group[ $index ] ) - 48;
				if ( $value < 0 || $value > 74 ) {
					return new WP_Error( 'salus_schedule_invalid_character', __( 'The programme contains characters that cannot be decoded.', 'salus-i500-heating-schema' ) );
				}
				$values[] = $value;
			}

			list( $hour, $minute, $whole_degrees, $tenths ) = $values;
			if ( 0 === $hour && 0 === $minute && 0 === $whole_degrees && 0 === $tenths ) {
				break;
			}
			if ( $hour > 23 || $minute > 59 || $whole_degrees > 50 || $tenths > 9 ) {
				return new WP_Error( 'salus_schedule_invalid_entry', __( 'The programme contains an invalid time or temperature.', 'salus-i500-heating-schema' ) );
			}

			$slots[] = array(
				'time'        => sprintf( '%02d:%02d', $hour, $minute ),
				'temperature' => number_format( $whole_degrees + ( $tenths / 10 ), 1, '.', '' ),
			);
		}

		return $slots;
	}

	private static function xml_child_value( $node, $child_name ) {
		$matches = $node->xpath( './*[local-name()="' . $child_name . '"]' );
		if ( false === $matches || empty( $matches ) ) {
			return '';
		}

		return (string) $matches[0];
	}

	private static function schema_cache_key() {
		$credentials = self::get_credentials();
		return 'salus_i500_schema_' . md5( $credentials['username'] );
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
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::SETTINGS_PAGE_SLUG ) );
		exit;
	}

	private static function redirect_to_schema() {
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SCHEMA_PAGE_SLUG ) );
		exit;
	}
}

Salus_iT500_Heating_Schema::init();
