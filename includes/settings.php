<?php

/**
 * Settings
 *
 * @package Menu_Icons
 * @author Dzikri Aziz <kvcrvt@gmail.com>
 */
final class Menu_Icons_Settings {

	protected static $defaults = array(
		'icon_types' => array(),
		'extra_css'  => '1',
		'position'   => 'before',
	);

	protected static $settings;


	public static function get() {
		return kucrut_get_array_value_deep( self::$settings, func_get_args() );
	}


	private static function _get() {
		$settings = get_option( 'menu-icons', null );

		if ( is_null( $settings ) ) {
			$settings = self::$defaults;
		}

		/**
		 * Check icon types
		 *
		 * A type could be enabled in the settings but disabled by a filter,
		 * so we need to 'fix' it here.
		 */
		if ( ! empty( $settings['icon_types'] ) ) {
			$active_types = array();
			$icon_types   = Menu_Icons::get( 'icon_types' );

			foreach ( (array) $settings['icon_types'] as $index => $id ) {
				if ( isset( $icon_types[ $id ] ) ) {
					$active_types[] = $id;
				}
			}

			if ( $settings['icon_types'] !== $active_types ) {
				$settings['icon_types'] = $active_types;
				update_option( 'menu-icons', $settings );
			}
		}

		self::$settings = $settings;
	}


	/**
	 * Settings init
	 *
	 * @since 0.3.0
	 */
	public static function init() {
		self::$defaults['icon_types'] = array_keys( Menu_Icons::get('icon_types') );
		self::_get();

		add_action( 'load-nav-menus.php', array( __CLASS__, '_load_nav_menus' ) );
	}


	public static function _load_nav_menus() {
		self::_maybe_update_settings();
		self::_add_settings_meta_box();

		add_action( 'admin_enqueue_scripts', array( __CLASS__, '_enqueue_scripts' ), 99 );

		// Load editor
		$active_types = self::get( 'icon_types' );
		if ( ! empty( $active_types ) ) {
			require_once Menu_Icons::get( 'dir' ) . 'includes/admin.php';
			Menu_Icons_Admin_Nav_Menus::init();
		}
	}


	/**
	 * Update settings
	 *
	 * @since   0.3.0
	 * @access  private
	 * @wp_hook load-nav-menus.php
	 */
	public static function _maybe_update_settings() {
		if ( ! empty( $_POST['menu-icons']['settings'] ) ) {
			$values = update_option(
				'menu-icons',
				$_POST['menu-icons']['settings']
			);
			self::_get();
		}
	}


	/**
	 * Settings meta box
	 *
	 * @since  0.3.0
	 * @access private
	 */
	private static function _add_settings_meta_box() {
		add_meta_box(
			'menu-icons-settings',
			__( 'Menu Icons Settings', 'menu-icons' ),
			array( __CLASS__, '_meta_box' ),
			'nav-menus',
			'side',
			'low',
			array()
		);
	}


	/**
	 * Settings meta box
	 *
	 * @since 0.3.0
	 */
	public static function _meta_box() {
		require_once Menu_Icons::get( 'dir' ) . 'includes/library/form-fields.php';

		$icon_types = array();
		foreach ( Menu_Icons::get('icon_types') as $id => $props ) {
			$icon_types[ $id ] = $props['label'];
		}

		$fields     = array(
			array(
				'id'      => 'icon_types',
				'type'    => 'checkbox',
				'label'   => __( 'Icon Types', 'menu-icons' ),
				'choices' => $icon_types,
			),
			array(
				'id'      => 'extra_css',
				'type'    => 'select',
				'label'   => __( 'Extra Stylesheet', 'menu-icons' ),
				'choices' => array(
					'1' => __( 'Enable', 'menu-icons' ),
					'0' => __( 'Disable', 'menu-icons' ),
				),
			),
			array(
				'id'      => 'default_position',
				'type'    => 'select',
				'label'   => __( 'Default Icon Position', 'menu-icons' ),
				'choices' => array(
					'before' => __( 'Before', 'menu-icons' ),
					'after'  => __( 'After', 'menu-icons' ),
				),
			),
		);
		$field_args = array(
			'prefix'  => 'menu-icons',
			'section' => 'settings',
		);

		?>
			<div class="taxonomydiv">
				<div class="tabs-panel">
					<?php
						foreach ( $fields as $_field ) :
							$_field['value'] = self::get( $_field['id'] );
							$field = Kucrut_Form_Field::create( $_field, $field_args );
					?>
						<div class="_field">
							<?php printf(
								'<label for="%s" class="_main">%s</label>',
								esc_attr( $field->id ),
								esc_html( $_field['label'] )
							) ?>
							<?php $field->render() ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<p class="submitbox button-controls">
				<span class="list-controls">
					<?php printf(
						'<a href="%s" class="select-all submitdelete">%s</a>',
						esc_url( '#' ),
						esc_html__( 'Reset', 'menu-icons' )
					) ?>
				</span>

				<span class="add-to-menu">
					<?php submit_button(
						__( 'Save Settings', 'menu-icons' ),
						'secondary',
						'menu-item-settings-save',
						false
					) ?>
				</span>
			</p>
		<?php
	}


	/**
	 * Enqueue scripts & styles for admin page
	 *
	 * @since   0.3.0
	 * @wp_hook action admin_enqueue_scripts
	 */
	public static function _enqueue_scripts() {
		wp_enqueue_style(
			'menu-icons',
			Menu_Icons::get( 'url' ) . 'css/admin' . Menu_Icons::get_script_suffix() . '.css',
			false,
			Menu_Icons::VERSION
		);
	}
}
