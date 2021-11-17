<?php
declare( strict_types=1 );

namespace WP_Rocket\Engine\Optimization\DelayJS\Admin;

use phpDocumentor\Reflection\Types\Void_;
use WP_Rocket\Engine\Admin\Settings\Settings as AdminSettings;
use WP_Rocket\Event_Management\Subscriber_Interface;

class Subscriber implements Subscriber_Interface {
	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Instantiate the class
	 *
	 * @param Settings $settings Settings instance.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Return an array of events that this subscriber listens to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		return [
			'rocket_first_install_options'         => 'add_options',
			'wp_rocket_upgrade'                    => [ 'set_option_on_update', 13, 2 ],
			'rocket_input_sanitize'                => [ 'sanitize_options', 13, 2 ],
			'pre_update_option_wp_rocket_settings' => [
				[
					[ 'maybe_disable_combine_js', 11, 2 ],
					[ 'add_notice_when_delayjs_and_autoptimize_aggregatejs', 10, 2 ],
				],
			],
		];
	}

	/**
	 * Add the delay JS options to the WP Rocket options array
	 *
	 * @param array $options WP Rocket options array.
	 *
	 * @return array
	 * @since 3.7
	 */
	public function add_options( $options ): array {
		return $this->settings->add_options( $options );
	}

	/**
	 * Sets the delay_js_exclusions default value for users with delay JS enabled on upgrade
	 *
	 * @param string $new_version New plugin version.
	 * @param string $old_version Previous plugin version.
	 *
	 * @return void
	 * @since 3.7
	 *
	 * @since 3.9 Sets the delay_js_exclusions default value if delay_js is 1
	 */
	public function set_option_on_update( $new_version, $old_version ) {
		$this->settings->set_option_on_update( $old_version );
	}

	/**
	 * Sanitizes Delay JS options values when the settings form is submitted
	 *
	 * @param array         $input    Array of values submitted from the form.
	 * @param AdminSettings $settings Settings class instance.
	 *
	 * @return array
	 * @since 3.9
	 */
	public function sanitize_options( $input, AdminSettings $settings ): array {
		return $this->settings->sanitize_options( $input, $settings );
	}

	/**
	 * Disable combine JS option when delay JS is enabled
	 *
	 * @param array $value     The new, unserialized option value.
	 * @param array $old_value The old option value.
	 *
	 * @return array
	 * @since 3.9
	 */
	public function maybe_disable_combine_js( $value, $old_value ): array {
		return $this->settings->maybe_disable_combine_js( $value, $old_value );
	}

	/**
	 * Add a compatibility notice to transient errors when Autoptimize Aggregate JS is 'on' and Delay JS is activated.
	 *
	 * @since 3.10.4
	 *
	 * @param array $value     New Settings values array.
	 * @param array $old_value Old Settings values array.
	 *
	 * @return array Returns the new settings values array unchanged.
	 */
	public function add_notice_when_delayjs_and_autoptimize_aggregatejs( $value, $old_value ): array {
		$autoptimize_aggregate_js_setting = get_option( 'autoptimize_js_aggregate' );

		if ( 'on' !== $autoptimize_aggregate_js_setting ) {
			return $value;
		}

		if ( ! ( 0 === (int) $old_value['delay_js'] && 1 === (int) $value['delay_js'] ) ) {
			return $value;
		}

		add_settings_error(
			'general',
			'compatibility_notice',
			'</strong>' .
			__(
				'We have detected that Autoptimize\'s JavaScript Aggregation feature is enabled. The Delay JavaScript Execution will not be applied to the file it creates. We suggest disabling it to take full advantage of Delay JavaScript Execution.',
				'rocket'
			) .
			'<strong>',
			'info'
		);

		return $value;
	}
}
