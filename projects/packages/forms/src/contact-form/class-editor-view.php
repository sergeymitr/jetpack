<?php
/**
 * A prototype to allow inline editing / editor views for contact forms.
 *
 * Originally developed in: https://github.com/automattic/gm2016-grunion-editor
 * Authors: Michael Arestad, Andrew Ozz, and George Stephanis
 *
 * @package automattic/jetpack-forms
 */

namespace Automattic\Jetpack\Forms\ContactForm;

use Automattic\Jetpack\Assets;

/**
 * Grunion editor view class.
 */
class Editor_View {
	/**
	 * Add hooks according to screen.
	 *
	 * @param \WP_Screen $screen Data about current screen.
	 */
	public static function add_hooks( $screen ) {
		if ( isset( $screen->base ) && 'post' === $screen->base ) {
			add_action( 'admin_notices', array( __CLASS__, 'handle_editor_view_js' ) );
			add_action( 'admin_head', array( __CLASS__, 'admin_head' ) );
		}
	}

	/**
	 * Admin header.
	 */
	public static function admin_head() {
		remove_action( 'media_buttons', array( Admin::init(), 'grunion_media_button' ), 999 );
		add_action( 'media_buttons', array( __CLASS__, 'grunion_media_button' ), 999 );
	}

	/**
	 * Render the grunion media button.
	 */
	public static function grunion_media_button() {
		$title = __( 'Add Contact Form', 'jetpack-forms' );
		?>

		<button type="button" id="insert-jetpack-contact-form" class="button" title="<?php echo esc_attr( $title ); ?>" href="javascript:;">
			<span class="jetpack-contact-form-icon"></span>
			<?php echo esc_html( $title ); ?>
		</button>

		<?php
	}

	/**
	 * Get external plugins.
	 *
	 * @param array $plugin_array - the plugin array.
	 *
	 * @return array
	 */
	public static function mce_external_plugins( $plugin_array ) {
		$plugin_array['grunion_form'] = Assets::get_file_url_for_environment(
			'_inc/build/contact-form/js/tinymce-plugin-form-button.min.js',
			'modules/contact-form/js/tinymce-plugin-form-button.js'
		);
		return $plugin_array;
	}

	/**
	 * MCE buttons.
	 *
	 * @param array $buttons - the buttons.
	 *
	 * @return array
	 */
	public static function mce_buttons( $buttons ) {
		$size     = count( $buttons );
		$buttons1 = array_slice( $buttons, 0, $size - 1 );
		$buttons2 = array_slice( $buttons, $size - 1 );
		return array_merge(
			$buttons1,
			array( 'grunion' ),
			$buttons2
		);
	}

	/**
	 * WordPress Shortcode Editor View JS Code
	 */
	public static function handle_editor_view_js() {
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'editor_view_js_templates' ), 1 );
		add_filter( 'mce_external_plugins', array( __CLASS__, 'mce_external_plugins' ) );
		add_filter( 'mce_buttons', array( __CLASS__, 'mce_buttons' ) );

		wp_enqueue_style( 'grunion-editor-ui', plugins_url( '../../dist/contact-form/css/editor-ui.css', __FILE__ ), array(), \JETPACK__VERSION );
		wp_style_add_data( 'grunion-editor-ui', 'rtl', 'replace' );

		Assets::register_script(
			'grunion-editor-view',
			'../../dist/contact-form/js/editor-view.js',
			__FILE__,
			array(
				'enqueue'      => true,
				'dependencies' => array( 'wp-util', 'jquery', 'quicktags' ),
				'version'      => \JETPACK__VERSION,
				'in_footer'    => true,
			)
		);

		wp_localize_script(
			'grunion-editor-view',
			'grunionEditorView',
			array(
				'inline_editing_style'     => plugins_url( '../../dist/contact-form/css/editor-inline-editing-style.css', __FILE__ ),
				'inline_editing_style_rtl' => plugins_url( '../../dist/contact-form/css/editor-inline-editing-style.rtl.css', __FILE__ ),
				'dashicons_css_url'        => includes_url( 'css/dashicons.css' ),
				'default_form'             => '[contact-field label="' . __( 'Name', 'jetpack-forms' ) . '" type="name"  required="true" /]' .
									'[contact-field label="' . __( 'Email', 'jetpack-forms' ) . '" type="email" required="true" /]' .
									'[contact-field label="' . __( 'Website', 'jetpack-forms' ) . '" type="url" /]' .
									'[contact-field label="' . __( 'Message', 'jetpack-forms' ) . '" type="textarea" /]',
				'labels'                   => array(
					'submit_button_text'  => __( 'Submit', 'jetpack-forms' ),
					/** This filter is documented in \Automattic\Jetpack\Forms\ContactForm\Contact_Form */
					'required_field_text' => apply_filters( 'jetpack_required_field_text', __( '(required)', 'jetpack-forms' ) ),
					'edit_close_ays'      => __( 'Are you sure you\'d like to stop editing this form without saving your changes?', 'jetpack-forms' ),
					'quicktags_label'     => __( 'contact form', 'jetpack-forms' ),
					'tinymce_label'       => __( 'Add contact form', 'jetpack-forms' ),
				),
			)
		);

		add_editor_style( plugin_dir_url( __FILE__ ) . '../../dist/contact-form/css/editor-style.css' );
	}

	/**
	 * JS Templates.
	 */
	public static function editor_view_js_templates() {
		?>
<script type="text/html" id="tmpl-grunion-contact-form">
	<form class="card jetpack-contact-form-shortcode-preview" action='#' method='post' class='contact-form commentsblock' onsubmit="return false;">
		{{{ data.body }}}
		<p class='contact-submit'>
			<input type='submit' value='{{ data.submit_button_text }}' class='pushbutton-wide'/>
		</p>
	</form>
</script>

<script type="text/html" id="tmpl-grunion-field-email">
	<div>
		<label for='{{ data.id }}' class='grunion-field-label email'>{{ data.label }}<# if ( data.required ) print( " <span>" + data.required + "</span>" ) #></label>
		<input type='email' name='{{ data.id }}' id='{{ data.id }}' value='{{ data.value }}' class='{{ data.class }}' placeholder='{{ data.placeholder }}' />
	</div>
</script>

<script type="text/html" id="tmpl-grunion-field-telephone">
	<div>
		<label for='{{ data.id }}' class='grunion-field-label telephone'>{{ data.label }}<# if ( data.required ) print( " <span>" + data.required + "</span>" ) #></label>
		<input type='tel' name='{{ data.id }}' id='{{ data.id }}' value='{{ data.value }}' class='{{ data.class }}' placeholder='{{ data.placeholder }}' />
	</div>
</script>

<script type="text/html" id="tmpl-grunion-field-textarea">
	<div>
		<label for='contact-form-comment-{{ data.id }}' class='grunion-field-label textarea'>{{ data.label }}<# if ( data.required ) print( " <span>" + data.required + "</span>" ) #></label>
		<textarea name='{{ data.id }}' id='contact-form-comment-{{ data.id }}' rows='20' class='{{ data.class }}' placeholder='{{ data.placeholder }}'>{{ data.value }}</textarea>
	</div>
</script>

<script type="text/html" id="tmpl-grunion-field-radio">
	<div>
		<label class='grunion-field-label'>{{ data.label }}<# if ( data.required ) print( " <span>" + data.required + "</span>" ) #></label>
		<# _.each( data.options, function( option ) { #>
			<label class='grunion-radio-label radio'>
				<input type='radio' name='{{ data.id }}' value='{{ option }}' class="{{ data.class }}" <# if ( option === data.value ) print( "checked='checked'" ) #> />
				<span>{{ option }}</span>
			</label>
		<# }); #>
		<div class='clear-form'></div>
	</div>
</script>

<script type="text/html" id="tmpl-grunion-field-checkbox">
	<div>
		<label class='grunion-field-label checkbox'>
			<input type='checkbox' name='{{ data.id }}' value='<?php esc_attr__( 'Yes', 'jetpack-forms' ); ?>' class="{{ data.class }}" <# if ( data.value ) print( 'checked="checked"' ) #> />
				<span>{{ data.label }}</span><# if ( data.required ) print( " <span>" + data.required + "</span>" ) #>
		</label>
		<div class='clear-form'></div>
	</div>
</script>

<script type="text/html" id="tmpl-grunion-field-checkbox-multiple">
	<div>
		<label class='grunion-field-label'>{{ data.label }}<# if ( data.required ) print( " <span>" + data.required + "</span>" ) #></label>
		<# _.each( data.options, function( option ) { #>
			<label class='grunion-checkbox-multiple-label checkbox-multiple'>
				<input type='checkbox' name='{{ data.id }}[]' value='{{ option }}' class="{{ data.class }}" <# if ( option === data.value || _.contains( data.value, option ) ) print( "checked='checked'" ) #> />
				<span>{{ option }}</span>
			</label>
		<# }); #>
		<div class='clear-form'></div>
	</div>
</script>

<script type="text/html" id="tmpl-grunion-field-select">
	<div>
		<label for='{{ data.id }}' class='grunion-field-label select'>{{ data.label }}<# if ( data.required ) print( " <span>" + data.required + "</span>" ) #></label>
		<select name='{{ data.id }}' id='{{ data.id }}' class="{{ data.class }}">
			<# _.each( data.options, function( option ) { #>
				<option <# if ( option === data.value ) print( "selected='selected'" ) #>>{{ option }}</option>
			<# }); #>
		</select>
	</div>
</script>

<script type="text/html" id="tmpl-grunion-field-date">
	<div>
		<label for='{{ data.id }}' class='grunion-field-label {{ data.type }}'>{{ data.label }}<# if ( data.required ) print( " <span>" + data.required + "</span>" ) #></label>
		<input type='text' name='{{ data.id }}' id='{{ data.id }}' value='{{ data.value }}' class="{{ data.class }}" />
	</div>
</script>

<script type="text/html" id="tmpl-grunion-field-text">
	<div>
		<label for='{{ data.id }}' class='grunion-field-label {{ data.type }}'>{{ data.label }}<# if ( data.required ) print( " <span>" + data.required + "</span>" ) #></label>
		<input type='text' name='{{ data.id }}' id='{{ data.id }}' value='{{ data.value }}' class='{{ data.class }}' placeholder='{{ data.placeholder }}' />
	</div>
</script>

<script type="text/html" id="tmpl-grunion-field-url">
	<div>
		<label for='{{ data.id }}' class='grunion-field-label {{ data.type }}'>{{ data.label }}<# if ( data.required ) print( " <span>" + data.required + "</span>" ) #></label>
		<input type='url' name='{{ data.id }}' id='{{ data.id }}' value='{{ data.value }}' class='{{ data.class }}' placeholder='{{ data.placeholder }}' />
	</div>
</script>


<script type="text/html" id="tmpl-grunion-field-edit">
	<div class="card is-compact grunion-field-edit grunion-field-{{ data.type }}" aria-label="<?php esc_attr_e( 'Form Field', 'jetpack-forms' ); ?>">
		<label class="grunion-name">
			<span><?php esc_html_e( 'Field Label', 'jetpack-forms' ); ?></span>
			<input type="text" name="label" placeholder="<?php esc_attr_e( 'Label', 'jetpack-forms' ); ?>" value="{{ data.label }}"/>
		</label>

		<?php
		$grunion_field_types = array(
			'text'              => __( 'Text', 'jetpack-forms' ),
			'name'              => __( 'Name', 'jetpack-forms' ),
			'email'             => __( 'Email', 'jetpack-forms' ),
			'url'               => __( 'Website', 'jetpack-forms' ),
			'textarea'          => __( 'Textarea', 'jetpack-forms' ),
			'checkbox'          => __( 'Checkbox', 'jetpack-forms' ),
			'checkbox-multiple' => __( 'Checkbox with Multiple Items', 'jetpack-forms' ),
			'select'            => __( 'Drop down', 'jetpack-forms' ),
			'radio'             => __( 'Radio', 'jetpack-forms' ),
			'date'              => __( 'Date', 'jetpack-forms' ),
		);
		?>
		<div class="grunion-type-options">
			<label class="grunion-type">
				<?php esc_html_e( 'Field Type', 'jetpack-forms' ); ?>
				<select name="type">
					<?php foreach ( $grunion_field_types as $type => $label ) : ?>
					<option <# if ( '<?php echo esc_js( $type ); ?>' === data.type ) print( "selected='selected'" ) #> value="<?php echo esc_attr( $type ); ?>">
						<?php echo esc_html( $label ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</label>

			<label class="grunion-required">
				<input type="checkbox" name="required" value="1" <# if ( data.required ) print( 'checked="checked"' ) #> />
				<span><?php esc_html_e( 'Required?', 'jetpack-forms' ); ?></span>
			</label>
		</div>

		<label class="grunion-options">
			<?php esc_html_e( 'Options', 'jetpack-forms' ); ?>
			<ol>
				<# if ( data.options ) { #>
					<# _.each( data.options, function( option ) { #>
						<li><input type="text" name="option" value="{{ option }}" /> <a class="delete-option" href="javascript:;"><span class="screen-reader-text"><?php esc_html_e( 'Delete Option', 'jetpack-forms' ); ?></span></a></li>
					<# }); #>
				<# } else { #>
					<li><input type="text" name="option" /> <a class="delete-option" href="javascript:;"><span class="screen-reader-text"><?php esc_html_e( 'Delete Option', 'jetpack-forms' ); ?></span></a></li>
					<li><input type="text" name="option" /> <a class="delete-option" href="javascript:;"><span class="screen-reader-text"><?php esc_html_e( 'Delete Option', 'jetpack-forms' ); ?></span></a></li>
					<li><input type="text" name="option" /> <a class="delete-option" href="javascript:;"><span class="screen-reader-text"><?php esc_html_e( 'Delete Option', 'jetpack-forms' ); ?></span></a></li>
				<# } #>
				<li><a class="add-option" href="javascript:;"><?php esc_html_e( 'Add new option...', 'jetpack-forms' ); ?></a></li>
			</ol>
		</label>

		<a href="javascript:;" class="delete-field"><span class="screen-reader-text"><?php esc_html_e( 'Delete Field', 'jetpack-forms' ); ?></span></a>
	</div>
</script>

<script type="text/html" id="tmpl-grunion-field-edit-option">
	<li><input type="text" name="option" /> <a class="delete-option" href="javascript:;"><span class="screen-reader-text"><?php esc_html_e( 'Delete Option', 'jetpack-forms' ); ?></span></a></li>
</script>

<script type="text/html" id="tmpl-grunion-editor-inline">
			<h1 id="form-settings-header" class="grunion-section-header"><?php esc_html_e( 'Contact form information', 'jetpack-forms' ); ?></h1>
			<section class="card grunion-form-settings" aria-labelledby="form-settings-header">
				<label><?php esc_html_e( 'What would you like the subject of the email to be?', 'jetpack-forms' ); ?>
					<input type="text" name="subject" value="{{ data.subject }}" />
				</label>
				<label><?php esc_html_e( 'Which email address should we send the submissions to?', 'jetpack-forms' ); ?>
					<input type="text" name="to" value="{{ data.to }}" />
				</label>
			</section>
			<h1 id="form-fields-header" class="grunion-section-header"><?php esc_html_e( 'Contact form fields', 'jetpack-forms' ); ?></h1>
			<section class="grunion-fields" aria-labelledby="form-fields-header">
				{{{ data.fields }}}
			</section>
			<section class="grunion-controls">
				<?php submit_button( esc_html__( 'Add Field', 'jetpack-forms' ), 'secondary', 'add-field', false ); ?>

				<div class="grunion-update-controls">
					<?php submit_button( esc_html__( 'Cancel', 'jetpack-forms' ), 'delete', 'cancel', false ); ?>
					<?php submit_button( esc_html__( 'Update Form', 'jetpack-forms' ), 'primary', 'submit', false ); ?>
				</div>
			</section>
</script>

</div>
		<?php
	}
}
