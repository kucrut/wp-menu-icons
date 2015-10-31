<?php
/**
 * Menu editor handler
 *
 * @package Menu_Icons
 * @author  Dzikri Aziz <kvcrvt@gmail.com>
 */


/**
 * Nav menu admin
 */
final class Menu_Icons_Picker {

	/**
	 * Holds active icon types
	 *
	 * @since  0.3.0
	 * @access private
	 * @var    array
	 */
	private static $_icon_types;


	/**
	 * Initialize class
	 *
	 * @since   0.1.0
	 * @wp_hook action load-nav-menus.php
	 */
	public static function init() {
		$active_types = Menu_Icons_Settings::get( 'global', 'icon_types' );
		if ( empty( $active_types ) ) {
			return;
		}

		add_action( 'load-nav-menus.php', array( __CLASS__, '_load_nav_menus' ) );
		add_filter( 'wp_edit_nav_menu_walker', array( __CLASS__, '_filter_wp_edit_nav_menu_walker' ), 99 );
		add_filter( 'wp_nav_menu_item_custom_fields', array( __CLASS__, '_fields' ), 10, 4 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, '_enqueue_assets' ), 101 );
		add_action( 'print_media_templates', array( __CLASS__, '_media_templates' ) );

		add_filter( 'manage_nav-menus_columns', array( __CLASS__, '_columns' ), 99 );
		add_action( 'wp_update_nav_menu_item', array( __CLASS__, '_save' ), 10, 3 );
	}


	/**
	 * Load Icon Picker
	 *
	 * @action load-nav-menus.php
	 */
	public static function _load_nav_menus() {
		Icon_Picker::instance()->load();
	}



	/**
	 * Custom walker
	 *
	 * @since   0.3.0
	 * @access  protected
	 * @wp_hook filter    wp_edit_nav_menu_walker/10/1
	 */
	public static function _filter_wp_edit_nav_menu_walker( $walker ) {
		// Load menu item custom fields plugin
		if ( ! class_exists( 'Menu_Item_Custom_Fields_Walker' ) ) {
			require_once Menu_Icons::get( 'dir' ) . 'includes/walker-nav-menu-edit.php';
		}
		$walker = 'Menu_Item_Custom_Fields_Walker';

		return $walker;
	}


	/**
	 * Enqueue scripts & styles on wp-admin/nav-menus.php
	 *
	 * @since   0.1.1
	 * @access  protected
	 * @wp_hook admin_enqueue_scripts
	 */
	public static function _enqueue_assets() {
		$ajax_url = admin_url( '/admin-ajax.php' );
		$data     = array(
			'text'           => array(
				'title'        => __( 'Select Icon', 'menu-icons' ),
				'select'       => __( 'Select', 'menu-icons' ),
				'all'          => __( 'All', 'menu-icons' ),
				'preview'      => __( 'Preview', 'menu-icons' ),
				'settingsInfo' => sprintf(
					esc_html__( "Please note that the actual look of the icons on the front-end will also be affected by your active theme's style. You can use %s if you need to override it.", 'menu-icons' ),
					'<a target="_blank" href="http://wordpress.org/plugins/simple-custom-css/">Simple Custom CSS</a>'
				),
			),
			'settingsFields' => Menu_Icons_Settings::get_settings_fields(),
			'activeTypes'    => Menu_Icons_Settings::get( 'global', 'icon_types' ),
		);

		wp_enqueue_script(
			'menu-icons-picker',
			sprintf( '%sjs/picker.js', Menu_Icons::get( 'url' ) ),
			'icon-picker',
			Menu_Icons::VERSION,
			true
		);

		wp_localize_script( 'menu-icons-picker', 'menuIconsPicker', $data );
	}


	/**
	 * Get preview
	 *
	 * @since 0.2.0
	 * @access private
	 * @param int $id Menu item ID
	 * @param array $meta_value Menu item meta value
	 * @return mixed
	 */
	private static function _get_preview( $id, $meta_value ) {
		// TODO: Show select/preview
		$preview = esc_html__( 'Select', 'menu-icons' );

		return $preview;
	}


	/**
	 * Get Fields
	 *
	 * @since  0.3.0
	 * @access private
	 * @return array
	 */
	private static function _get_fields( Array $values = array() ) {
		$fields = Menu_Icons_Settings::get_settings_fields( $values );

		foreach ( $fields as &$field ) {
			$field['attributes'] = array_merge(
				array(
					'class'    => '_setting',
					'data-key' => $field['id'],
				),
				isset( $field['attributes'] ) ? $field['attributes'] : array()
			);
		}

		unset( $field );

		return $fields;
	}


	/**
	 * Print fields
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @uses    add_action() Calls 'menu_icons_before_fields' hook
	 * @uses    add_action() Calls 'menu_icons_after_fields' hook
	 * @wp_hook action       menu_item_custom_fields/10/3
	 *
	 * @param object $item  Menu item data object.
	 * @param int    $depth Nav menu depth.
	 * @param array  $args  Menu item args.
	 * @param int    $id    Nav menu ID.
	 *
	 * @return string Form fields
	 */
	public static function _fields( $id, $item, $depth, $args ) {
		if ( ! class_exists( 'Kucrut_Form_Field' ) ) {
			require_once Menu_Icons::get( 'dir' ) . 'includes/library/form-fields.php';
		}

		$input_id   = sprintf( 'menu-icons-%d', $item->ID );
		$input_name = sprintf( 'menu-icons[%d]', $item->ID );
		$current    = wp_parse_args(
			Menu_Icons_Meta::get( $item->ID ),
			Menu_Icons_Settings::get_menu_settings( Menu_Icons_Settings::get_current_menu_id() )
		);
		?>
			<div class="field-icon description-wide menu-icons-wrap">
				<?php
					/**
					 * Allow plugins/themes to inject HTML before menu icons' fields
					 *
					 * @param object $item  Menu item data object.
					 * @param int    $depth Nav menu depth.
					 * @param array  $args  Menu item args.
					 * @param int    $id    Nav menu ID.
					 *
					 */
					do_action( 'menu_icons_before_fields', $item, $depth, $args, $id );
				?>
				<p class="description submitbox">
					<label><?php esc_html_e( 'Icon:', 'menu-icons' ) ?></label>
					<?php
						printf(
							'<a id="menu-icons-%1$d-select" class="_select" title="%2$s" data-id="%1$d" data-text="%2$s">%3$s</a>',
							esc_attr__( $item->ID ),
							esc_attr__( 'Select', 'menu-icons' ),
							self::_get_preview( $item->ID, $current ) // xss ok
						);
					?>
					<?php
						printf(
							'<a id="menu-icons-%1$s-remove" class="_remove hidden submitdelete" data-id="%1$s">%2$s</a>',
							esc_attr( $item->ID ),
							esc_html__( 'Remove', 'menu-icons' )
						);
					?>
				</p>
				<?php
					/**
					 * Allow plugins/themes to inject HTML after menu icons' fields
					 *
					 * @param object $item  Menu item data object.
					 * @param int    $depth Nav menu depth.
					 * @param array  $args  Menu item args.
					 * @param int    $id    Nav menu ID.
					 *
					 */
					do_action( 'menu_icons_after_fields', $item, $depth, $args, $id );
				?>
			</div>
		<?php
	}


	/**
	 * Add our field to the screen options toggle
	 *
	 * @since   0.1.0
	 * @access  private
	 * @wp_hook action manage_nav-menus_columns
	 * @link    http://codex.wordpress.org/Plugin_API/Filter_Reference/manage_posts_columns Action: manage_nav-menus_columns/99
	 *
	 * @param array $columns Menu item columns
	 * @return array
	 */
	public static function _columns( $columns ) {
		$columns['icon'] = __( 'Icon', 'menu-icons' );

		return $columns;
	}


	/**
	 * Save menu item metadata
	 *
	 * @since 0.8.0
	 *
	 * @param int   $id    Menu item ID.
	 * @param mixed $value Metadata value.
	 *
	 * @return void
	 */
	public static function save_menu_item_meta( $id, $value ) {
		/**
		 * Allow plugins/themes to filter the values
		 *
		 * Deprecated.
		 *
		 * @since 0.1.0
		 * @param array $value Metadata value.
		 * @param int   $id    Menu item ID.
		 */
		$_value = apply_filters( 'menu_icons_values', $value, $id );

		if ( $_value !== $value ) {
			_deprecated_function( 'menu_icons_values', '0.8.0', 'menu_icons_item_meta_values' );
		}

		/**
		 * Allow plugins/themes to filter the values
		 *
		 * Deprecated.
		 *
		 * @since 0.8.0
		 * @param array $value Metadata value.
		 * @param int   $id    Menu item ID.
		 */
		$value = apply_filters( 'menu_icons_item_meta_values', $_value, $id );

		// Update
		if ( ! empty( $value ) ) {
			update_post_meta( $id, 'menu-icons', $value );
		} else {
			delete_post_meta( $id, 'menu-icons' );
		}
	}


	/**
	 * Save menu item's icons values
	 *
	 * @since  0.1.0
	 * @access protected
	 * @uses   apply_filters() Calls 'menu_icons_values' on returned array.
	 * @link   http://codex.wordpress.org/Plugin_API/Action_Reference/wp_update_nav_menu_item Action: wp_update_nav_menu_item/10/2
	 *
	 * @param int   $menu_id         Nav menu ID
	 * @param int   $menu_item_db_id Menu item ID
	 * @param array $menu_item_args  Menu item data
	 */
	public static function _save( $menu_id, $menu_item_db_id, $menu_item_args ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen instanceof WP_Screen || 'nav-menus' !== $screen->id ) {
			return;
		}

		check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' );

		// Sanitize
		if ( ! empty( $_POST['menu-icons'][ $menu_item_db_id ] ) ) {
			$value = array_map(
				'sanitize_text_field',
				wp_unslash( (array) $_POST['menu-icons'][ $menu_item_db_id ] )
			);
		} else {
			$value = array();
		}

		self::save_menu_item_meta( $menu_item_db_id, $value );
	}


	/**
	 * Get and print media templates from all types
	 *
	 * @since 0.2.0
	 * @wp_hook action print_media_templates
	 */
	public static function _media_templates() {
		$id_prefix = 'tmpl-menu-icons';
		$templates = apply_filters( 'menu_icons_media_templates', array() );

		foreach ( $templates as $key => $template ) {
			$id = sprintf( '%s-%s', $id_prefix, $key );
			self::_print_tempate( $id, $template );
		}

		require_once dirname( __FILE__ ) . '/media-template.php';
	}


	/**
	 * Print media template
	 *
	 * @since 0.2.0
	 * @param string $id       Template ID
	 * @param string $template Media template HTML
	 */
	protected static function _print_tempate( $id, $template ) {
		?>
			<script type="text/html" id="<?php echo esc_attr( $id ) ?>">
				<?php echo $template; // xss ok ?>
			</script>
		<?php
	}
}