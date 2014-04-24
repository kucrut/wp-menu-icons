<?php

/**
 * Form Fields
 *
 * A prototype for the new "Form Fields" plugin, a standalone plugin and
 * extension for the upcoming "Settings" plugin, a rewrite of KC Settings.
 *
 * @author  Dzikri Aziz <kvcrvt@gmail.com>
 */

/**
 * Form Fields
 */
abstract class Kucrut_Form_Field {

	/**
	 * Holds field & argument defaults
	 *
	 * @since  0.1.0
	 * @var    array
	 * @access protected
	 */
	protected static $defaults = array(
		'field' => array(
			'id'          => '',
			'type'        => 'text',
			'value'       => null,
			'default'     => '',
			'attributes'  => array(),
			'description' => '',
			'choices'     => array(),
		),
		'args'  => array(
			'prefix'  => '',
			'section' => '',
			'mode'    => 'plugin'
		),
	);

	/**
	 * Holds field attributes
	 *
	 * @since  0.1.0
	 * @var    array
	 * @access protected
	 */
	protected static $types = array(
		'text'            => 'Kucrut_Form_Field_Text',
		'checkbox'        => 'Kucrut_Form_Field_Checkbox',
		'radio'           => 'Kucrut_Form_Field_Radio',
		'textarea'        => 'Kucrut_Form_Field_Textarea',
		'select'          => 'Kucrut_Form_Field_Select',
		'select_multiple' => 'Kucrut_Form_Field_Select_Multiple',
		'special'         => 'Kucrut_Form_Field_Special',
	);

	/**
	 * Holds forbidden attributes
	 *
	 * @since  0.1.0
	 * @var    array
	 * @access protected
	 */
	protected static $forbidden_attributes = array(
		'id', 'name', 'value', 'checked', 'multiple',
	);

	/**
	 * Holds allowed html tags
	 *
	 * @since  0.1.0
	 * @var    array
	 * @access protected
	 */
	protected $allowed_html = array(
		'a'      => array(
			'href'   => true,
			'target' => true,
			'title'  => true,
		),
		'code'   => true,
		'em'     => true,
		'p'      => array( 'class' => true ),
		'span'   => array( 'class' => true ),
		'strong' => true,
	);

	/**
	 * Holds field keys
	 *
	 * @since  0.1.0
	 * @var    array
	 * @access protected
	 */
	protected $keys;

	/**
	 * Holds constructed field
	 *
	 * @since  0.1.0
	 * @var    array
	 * @access protected
	 */
	protected $field;


	/**
	 * Holds field attributes
	 *
	 * @since  0.1.0
	 * @var    array
	 * @access protected
	 */
	protected $attributes = array();


	/**
	 * Loader
	 *
	 * @param string URL path to this directory
	 */
	final public static function load( $url_path = null ) {
		// Set URL path for assets
		if ( ! is_null( $url_path ) ) {
			self::$url_path = $url_path;
		}
		else {
			self::$url_path = plugin_dir_url( __FILE__ );
		}

		// Supported field types
		self::$types = apply_filters(
			'form_field_types',
			self::$types
		);
	}


	/**
	 * Create field
	 *
	 * @param array $field Field array
	 * @param array $args  Extra field arguments
	 */
	final public static function create( Array $field, $args = array() ) {
		$field = wp_parse_args( $field, self::$defaults['field'] );
		if ( ! isset( self::$types[ $field['type'] ] )
			|| ! is_subclass_of( self::$types[ $field['type'] ], __CLASS__ )
		) {
			trigger_error(
				sprintf(
					__( '%1$s: Type %2$s is not supported, reverting to text.', 'menu-icons' ),
					__CLASS__,
					$field['type']
				),
				E_USER_WARNING
			);
			$field['type'] = 'text';
		}

		foreach ( self::$forbidden_attributes as $key ) {
			unset( $field['attributes'][ $key ] );
		}

		$args  = (object) wp_parse_args( $args, self::$defaults['args'] );
		$class = self::$types[ $field['type'] ];

		return new $class( $field, $args );
	}


	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 * @param array  $field Field array
	 * @param object $args  Extra field arguments
	 */
	public function __construct( $field, $args ) {
		$this->field = $field;
		$this->args  = $args;
		$this->keys  = array_filter(
			array(
				$this->args->prefix,
				$this->args->section,
				$this->field['id'],
			)
		);

		$this->attributes['id']   = $this->create_id();
		$this->attributes['name'] = $this->create_name();

		$this->attributes = wp_parse_args(
			$this->attributes,
			(array) $field['attributes']
		);

		$this->set_properties();
	}


	/**
	 * Attribute
	 *
	 * @since  0.1.0
	 * @param  string $key Attribute key
	 * @return mixed  NULL if attribute doesn't exist
	 */
	public function __get( $key ) {
		foreach ( array( 'attributes', 'field' ) as $group ) {
			if ( isset( $this->{$group}[ $key ] ) ) {
				return $this->{$group}[ $key ];
			}
		}

		return null;
	}


	/**
	 * Create id/name attribute
	 *
	 * @since 0.1.0
	 * @param string $format Attribute format
	 * @param array  $keys Field keys to construct the ID
	 */
	protected function create_id_name( $format ) {
		return call_user_func_array(
			'sprintf',
			array_merge(
				array( $format ),
				$this->keys
			)
		);
	}


	/**
	 * Create id attribute
	 *
	 * @since  0.1.0
	 * @access protected
	 *
	 * @param array $keys Field keys to construct the ID
	 */
	protected function create_id() {
		$format = implode( '-', $this->keys );

		return $this->create_id_name( $format );
	}


	/**
	 * Create name attribute
	 *
	 * @since  0.1.0
	 * @access protected
	 *
	 * @param array $keys Field keys to construct the name
	 */
	protected function create_name() {
		$format  = '%s';
		$format .= str_repeat( '[%s]', ( count( $this->keys ) - 1 ) );

		return $this->create_id_name( $format );
	}


	/**
	* Set field properties
	*
	* @since 0.1.0
	*/
	protected function set_properties() {}


	/**
	* Build field attributes
	*
	* @since  0.1.0
	* @param  array  $excludes Attributes to be excluded
	* @return string
	*/
	protected function build_attributes( $excludes = array() ) {
		$excludes   = array_filter( (array) $excludes );
		$attributes = '';

		foreach ( $this->attributes as $key => $value ) {
			if ( in_array( $key, $excludes ) ) {
				continue;
			}

			if ( 'class' === $key ) {
				$value = implode( ' ', (array) $value );
			}

			$attributes .= sprintf(
				' %s="%s"',
				esc_attr( $key ),
				esc_attr( $value )
			);
		}

		return $attributes;
	}


	/**
	* Print field
	*
	* @since 0.1.0
	*/
	abstract public function render();


	/**
	* Print field description
	*
	* @since 0.1.0
	*/
	public function description() {
		if ( ! empty( $this->field['description'] ) ) {
			printf(
				'<p class="description">%s</p>',
				wp_kses( $this->field['description'], $this->allowed_html )
			);
		}
	}
}


/**
 * Field: text
 */
class Kucrut_Form_Field_Text extends Kucrut_Form_Field {

	protected $attributes = array(
		'class' => 'regular-text',
	);


	public function render() {
		printf(
			'<input type="text" value="%s"%s />',
			esc_attr( $this->field['value'] ),
			$this->build_attributes()
		);
		$this->description();
	}
}


/**
 * Field: Textarea
 */
class Kucrut_Form_Field_Textarea extends Kucrut_Form_Field {

	protected $attributes = array(
		'class' => 'widefat',
		'cols'  => 50,
		'rows'  => 5,
	);


	public function render() {
		printf(
			'<textarea%s>%s</textarea>',
			$this->build_attributes(),
			esc_textarea( $this->field['value'] )
		);
	}
}


/**
 * Field: Checkbox
 */
class Kucrut_Form_Field_Checkbox extends Kucrut_Form_Field {

	protected $type = 'checkbox';

	protected $format = '<label><input type="%s" value="%s"%s%s /> %s</label><br />';


	protected function set_properties() {
		$this->field['value'] = array_filter( (array) $this->field['value'] );
		$this->attributes['name'] .= '[]';
	}


	protected function checked( $value ) {
		return checked( in_array( $value, $this->field['value'] ), true, false );
	}


	public function render() {
		foreach ( $this->field['choices'] as $value => $label ) {
			printf(
				$this->format,
				$this->type,
				esc_attr( $value ),
				$this->checked( $value ),
				$this->build_attributes( 'id' ),
				esc_html( $label )
			);
		}
	}
}


/**
 * Field: Radio
 */
class Kucrut_Form_Field_Radio extends Kucrut_Form_Field_Checkbox {

	protected $type = 'radio';

	protected function set_properties() {
		if ( ! is_string( $this->field['value'] ) ) {
			$this->field['value'] = '';
		}
	}


	protected function checked( $value ) {
		return checked( $value, $this->field['value'], false );
	}
}


/**
 * Field: Select
 */
class Kucrut_Form_Field_Select extends Kucrut_Form_Field {

	protected $format = '<option value="%s"%s>%s</option>';


	protected function set_properties() {
		if ( ! is_string( $this->field['value'] ) ) {
			$this->field['value'] = '';
		}
	}


	protected function selected( $value ) {
		return selected( ( $value == $this->field['value'] ), true, false );
	}


	public function render() {
		?>
		<select<?php echo $this->build_attributes() // xss ok ?>>
			<?php foreach ( $this->field['choices'] as $value => $label ) : ?>
				<?php printf(
					$this->format,
					esc_attr( $value ),
					$this->selected( $value ),
					esc_html( $label )
				) ?>
			<?php endforeach; ?>
		</select>
		<?php
	}
}


/**
 * Field: Multiple Select
 */
class Kucrut_Form_Field_Select_Multiple extends Kucrut_Form_Field_Select {

	protected function set_properties() {
		$this->field['value'] = array_filter( (array) $this->field['value'] );
		$this->attributes['name'] .= '[]';
		$this->attributes['multiple'] = 'multiple';
	}


	protected function selected( $value ) {
		return selected( in_array( $value, $this->field['value'] ), true, false );
	}
}


/**
 * Field: Special (Callback)
 */
class Kucrut_Form_Field_Special extends Kucrut_Form_Field {
	public function render() {
		call_user_func_array(
			$this->field['render_cb'],
			array( $this )
		);
	}
}
