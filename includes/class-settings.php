<?php
/**
 * Off-Canvas Sidebars plugin settings
 *
 * Settings
 * @author Jory Hogeveen <info@keraweb.nl>
 * @package off-canvas-sidebars
 * @version 0.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

final class OCS_Off_Canvas_Sidebars_Settings extends OCS_Off_Canvas_Sidebars_Form
{
	/**
	 * The single instance of the class.
	 *
	 * @var    OCS_Off_Canvas_Sidebars_Settings
	 * @since  0.3
	 */
	protected static $_instance = null;

	protected $general_key = '';
	protected $settings_tab = 'ocs-settings';
	protected $sidebars_tab = 'ocs-sidebars';
	protected $shortcode_tab = 'ocs-shortcode';
	protected $importexport_tab = 'ocs-importexport';
	protected $plugin_key = '';
	protected $plugin_tabs = array();
	protected $settings = array();
	protected $general_labels = array();
	protected $capability = 'edit_theme_options';

	/**
	 * @since  0.1
	 * @since  0.3  private constructor
	 * @access private
	 */
	private function __construct() {
		$this->plugin_key = off_canvas_sidebars()->get_plugin_key();
		add_action( 'admin_init', array( $this, 'load_plugin_data' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'register_importexport_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menus' ), 15 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
	}

	/**
	 * Get plugin defaults
	 * @since  0.1
	 */
	function load_plugin_data() {
		$off_canvas_sidebars = off_canvas_sidebars();
		$this->settings = $off_canvas_sidebars->get_settings();
		$this->general_labels = $off_canvas_sidebars->get_general_labels();
		$this->general_key = $off_canvas_sidebars->get_general_key();

		/**
		 * Change the capability for the OCS settings
		 * @since  1.4
		 * @param  string
		 * @return string
		 */
		$this->capability = apply_filters( 'ocs_settings_capability', $this->capability );
	}

	/**
	 * Enqueue our styles and scripts only when it's our page
	 * @since  0.1
	 * @param  $hook
	 */
	function enqueue_styles_scripts( $hook ) {
		if ( 'appearance_page_' . $this->plugin_key !== $hook ) {
			return;
		}

		// Add the color picker css and script file
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'postbox' );

		// Add our own scripts
		wp_enqueue_style( 'off-canvas-sidebars-admin', OCS_PLUGIN_URL . '/css/off-canvas-sidebars-admin.css', array(), OCS_PLUGIN_VERSION );
		wp_enqueue_script( 'off-canvas-sidebars-settings', OCS_PLUGIN_URL . '/js/off-canvas-sidebars-settings.js', array( 'jquery' ), OCS_PLUGIN_VERSION, true );
		wp_localize_script( 'off-canvas-sidebars-settings', 'OCS_OFF_CANVAS_SIDEBARS_SETTINGS', array(
			'general_key' => $this->general_key,
			'plugin_key' => $this->plugin_key,
			'css_prefix' => $this->settings['css_prefix'],
			'__required_fields_not_set' => __( 'Some required fields are not set!', 'off-canvas-sidebars' ),
		) );

	}

	/**
	 * Create admin page under the appearance menu
	 * @since  0.1
	 */
	function add_admin_menus() {
		add_theme_page(
			esc_attr__( 'Off-Canvas Sidebars', 'off-canvas-sidebars' ),
			esc_attr__( 'Off-Canvas Sidebars', 'off-canvas-sidebars' ),
			$this->capability,
			$this->plugin_key,
			array( $this, 'plugin_options_page' )
		);
	}

	/**
	 * Register our settings
	 * @since 0.1
	 */
	function register_settings() {
		$this->plugin_tabs[ $this->settings_tab ] = esc_attr__( 'Settings', 'off-canvas-sidebars' );
		$this->plugin_tabs[ $this->sidebars_tab ] = esc_attr__( 'Sidebars', 'off-canvas-sidebars' );
		$this->plugin_tabs[ $this->shortcode_tab ] = esc_attr__( 'Shortcodes', 'off-canvas-sidebars' );

		register_setting( $this->settings_tab, $this->general_key, array( $this, 'validate_input' ) );
		register_setting( $this->sidebars_tab, $this->general_key, array( $this, 'validate_input' ) );

		add_settings_section(
			'section_general',
			esc_attr__( 'Global Settings', 'off-canvas-sidebars' ),
			'',
			$this->settings_tab
		);
		add_settings_section(
			'section_frontend',
			esc_attr__( 'Frontend Settings', 'off-canvas-sidebars' ),
			'',
			$this->settings_tab
		);
		add_settings_section(
			'section_admin',
			esc_attr__( 'Admin Settings', 'off-canvas-sidebars' ),
			'',
			$this->settings_tab
		);

		$this->register_general_settings();

		// Register sidebar settings
		foreach ( $this->settings['sidebars'] as $sidebar => $sidebar_data ) {
			add_settings_section(
				'section_sidebar_' . $sidebar,
				__( 'Off-Canvas Sidebar', 'off-canvas-sidebars' ) . ' - <code class="js-dynamic-id">' . $this->settings['sidebars'][ $sidebar ]['label'] . '</code>',
				array( $this, 'register_general_settings' ),
				$this->sidebars_tab
			);
			$this->register_sidebar_settings( $sidebar );
		}

		do_action( 'off_canvas_sidebar_settings' );
	}

	/**
	 * General settings
	 * @since 0.1
	 */
	function register_general_settings() {

		/*
		 * General
		 */
		add_settings_field(
			'enabled_sidebars',
			esc_attr__( 'Enable Sidebars', 'off-canvas-sidebars' ),
			array( $this, 'enabled_sidebars_option' ),
			$this->settings_tab,
			'section_general'
		);

		/*
		 * Frontend
		 */
		add_settings_field(
			'enable_frontend',
			esc_attr__( 'Enable front-end', 'off-canvas-sidebars' ),
			array( $this, 'checkbox_option' ),
			$this->settings_tab,
			'section_frontend',
			array(
				'name' => 'enable_frontend',
				'label' => __( 'Let this plugin add the necessary elements on the front-end.', 'off-canvas-sidebars' ),
				// Translators: %s stands for a URL.
				'description' => sprintf( __( '<a href="%s" target="_blank">Read this to setup your theme for support!</a> (Themes based on the Genesis Framework are supported by default)', 'off-canvas-sidebars' ), 'https://wordpress.org/plugins/off-canvas-sidebars/installation/' ),
			)
		);
		/*add_settings_field(
			'frontend_type',
			esc_attr__( 'Front-end type', 'off-canvas-sidebars' ),
			array( $this, 'frontend_type_option' ),
			$this->settings_tab,
			'section_frontend'
		);*/
		add_settings_field(
			'css_prefix',
			esc_attr__( 'CSS Prefix', 'off-canvas-sidebars' ),
			array( $this, 'text_option' ),
			$this->settings_tab,
			'section_frontend',
			array(
				'name' => 'css_prefix',
				'label' => __( 'Default', 'off-canvas-sidebars' ) . ': <code>ocs</code>',
				'placeholder' => 'ocs',
			)
		);
		add_settings_field(
			'site_close',
			esc_attr__( 'Close sidebar when clicking on the site', 'off-canvas-sidebars' ),
			array( $this, 'checkbox_option' ),
			$this->settings_tab,
			'section_frontend',
			array(
				'name' => 'site_close',
				'label' => __( 'Enables closing of a off-canvas sidebar by clicking on the site. Default: true.', 'off-canvas-sidebars' ),
			)
		);
		add_settings_field(
			'disable_over',
			esc_attr__( 'Disable over', 'off-canvas-sidebars' ),
			array( $this, 'number_option' ),
			$this->settings_tab,
			'section_frontend',
			array(
				'name' => 'disable_over',
				'description' => __( 'Disable off-canvas sidebars over specified screen width. Leave blank to disable.', 'off-canvas-sidebars' ),
				'input_after' => '<code>px</code>',
			)
		);
		add_settings_field(
			'hide_control_classes',
			esc_attr__( 'Auto-hide control classes', 'off-canvas-sidebars' ),
			array( $this, 'checkbox_option' ),
			$this->settings_tab,
			'section_frontend',
			array(
				'name' => 'hide_control_classes',
				'label' => __( 'Hide off-canvas sidebar control classes over width specified in <strong>"Disable over"</strong>. Default: false.', 'off-canvas-sidebars' ),
			)
		);
		add_settings_field(
			'scroll_lock',
			esc_attr__( 'Scroll lock', 'off-canvas-sidebars' ),
			array( $this, 'checkbox_option' ),
			$this->settings_tab,
			'section_frontend',
			array(
				'name' => 'scroll_lock',
				'label' => __( 'Prevent site content scrolling whilst a off-canvas sidebar is open. Default: false.', 'off-canvas-sidebars' ),
			)
		);
		add_settings_field(
			'background_color',
			esc_attr__( 'Background color', 'off-canvas-sidebars' ),
			array( $this, 'color_option' ),
			$this->settings_tab,
			'section_frontend',
			array(
				'name' => 'background_color',
				'description' => __( 'Choose a background color for the site container. Default: <code>#ffffff</code>.', 'off-canvas-sidebars' ),
			)
		);
		// Genesis already has before and after hooks set
		if ( get_template() !== 'genesis' ) {
			// Check if the before hook is filtered. If it is this setting is not needed
			if ( '' === apply_filters( 'ocs_website_before_hook', '' ) ) {
				add_settings_field(
					'website_before_hook',
					'<code>website_before</code> ' . esc_attr__( 'hook name', 'off-canvas-sidebars' ),
					array( $this, 'text_option' ),
					$this->settings_tab,
					'section_frontend',
					array(
						'name'        => 'website_before_hook',
						'placeholder' => 'website_before',
					)
				);
			}
			// Check if the after hook is filtered. If it is this setting is not needed
			if ( '' === apply_filters( 'ocs_website_after_hook', '' ) ) {
				add_settings_field(
					'website_after_hook',
					'<code>website_after</code> ' . esc_attr__( 'hook name', 'off-canvas-sidebars' ),
					array( $this, 'text_option' ),
					$this->settings_tab,
					'section_frontend',
					array(
						'name'        => 'website_after_hook',
						'placeholder' => 'website_after',
					)
				);
			}
		}
		//https://github.com/ftlabs/fastclick
		add_settings_field(
			'use_fastclick',
			esc_attr__( 'Use the FastClick library?', 'off-canvas-sidebars' ),
			array( $this, 'checkbox_option' ),
			$this->settings_tab,
			'section_frontend',
			array(
				'name' => 'use_fastclick',
				'label' => __( 'Yes. Default: disabled', 'off-canvas-sidebars' ),
				'description' => __( 'Devices with touch capability often have a 300ms delay on click triggers. FastClick is a JavaScript library purposely built to elimate the delay where neccesary.', 'off-canvas-sidebars' ),
			)
		);

		add_settings_field(
			'compatibility_position_fixed',
			esc_attr__( 'Compatibility for fixed elements', 'off-canvas-sidebars' ),
			array( $this, 'radio_option' ),
			$this->settings_tab,
			'section_frontend',
			array(
				'name' => 'compatibility_position_fixed',
				'default' => 'none',
				'options' => array(
					'none' => array(
						'name' => 'none',
						'label' => __( 'No', 'off-canvas-sidebars' ),
						'value' => 'none',
					),
					'legacy-css' => array(
						'name' => 'legacy-css',
						'label' => __( 'Legacy CSS solution', 'off-canvas-sidebars' ) . ' (' . __( 'Use basic CSS positioning instead of CSS3 transform with hardware acceleration', 'off-canvas-sidebars' ) . ')',
						'value' => 'legacy-css',
						'description' => __( 'This is your best option if your site uses sticky menus and/or other fixed elements within the site container.', 'off-canvas-sidebars' ),
					),
					'custom-js' => array(
						'name' => 'custom-js',
						'label' => __( 'JavaScript solution', 'off-canvas-sidebars' ) . ' (' . __( 'Experimental', 'off-canvas-sidebars' ) . ')',
						'value' => 'custom-js',
						'description' => __( 'While still in development, this could fix compatibility issues with fixed elements.', 'off-canvas-sidebars' ),
					),
				),
			)
		);
	}

	function register_sidebar_settings( $sidebar_id ) {

		add_settings_field(
			'sidebar_enable',
			esc_attr__( 'Enable', 'off-canvas-sidebars' ),
			array( $this, 'checkbox_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'enable',
			)
		);
		add_settings_field(
			'sidebar_id',
			esc_attr__( 'ID', 'off-canvas-sidebars' ) . ' <span class="required">*</span>',
			array( $this, 'text_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'id',
				'value' => $sidebar_id,
				'required' => true,
				'description' => __( 'IMPORTANT: Must be unique!', 'off-canvas-sidebars' ),
			)
		);
		add_settings_field(
			'sidebar_label',
			esc_attr__( 'Name', 'off-canvas-sidebars' ),
			array( $this, 'text_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'label',
			)
		);
		add_settings_field(
			'sidebar_content',
			esc_attr__( 'Content', 'off-canvas-sidebars' ),
			array( $this, 'radio_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'content',
				'default' => 'sidebar',
				'options' => array(
					'sidebar' => array(
						'name' => 'sidebar',
						'label' => __( 'Sidebar', 'off-canvas-sidebars' ) . ' &nbsp (' . __( 'Default', 'off-canvas-sidebars' ) . ')',
						'value' => 'sidebar',
					),
					'menu' => array(
						'name' => 'menu',
						'label' => __( 'Menu', 'off-canvas-sidebars' ),
						'value' => 'menu',
					),
					'action' => array(
						'name' => 'action',
						'label' => __( 'Custom', 'off-canvas-sidebars' ) . ' &nbsp; (<a href="https://developer.wordpress.org/reference/functions/add_action/" target="_blank">' . __( 'Action hook', 'off-canvas-sidebars' ) . '</a>: <code>ocs_custom_content_sidebar_<span class="js-dynamic-id"></span></code> )',
						'value' => 'action',
					),
				),
				'description' => __( 'Keep in mind that WordPress has menu and text widgets by default, the "sidebar" object is your best option in most cases.', 'off-canvas-sidebars' ),
			)
		);
		add_settings_field(
			'sidebar_location',
			esc_attr__( 'Location', 'off-canvas-sidebars' ) . ' <span class="required">*</span>',
			array( $this, 'sidebar_location' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'required' => true,
			)
		);
		add_settings_field(
			'sidebar_size',
			esc_attr__( 'Size', 'off-canvas-sidebars' ),
			array( $this, 'sidebar_size' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'description' => __( 'You can overwrite this with CSS', 'off-canvas-sidebars' ),
			)
		);
		add_settings_field(
			'sidebar_style',
			esc_attr__( 'Style', 'off-canvas-sidebars' ) . ' <span class="required">*</span>',
			array( $this, 'sidebar_style' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'required' => true,
			)
		);
		add_settings_field(
			'animation_speed',
			esc_attr__( 'Animation speed', 'off-canvas-sidebars' ),
			array( $this, 'number_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'animation_speed',
				'description' =>
					__( 'Set the animation speed for showing and hiding this sidebar. Default: 300ms', 'off-canvas-sidebars' ) . '<br>' .
					__( 'You can overwrite this with CSS', 'off-canvas-sidebars' ),
				'input_after' => '<code>ms</code>',
			)
		);
		add_settings_field(
			'padding',
			esc_attr__( 'Padding', 'off-canvas-sidebars' ),
			array( $this, 'number_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'padding',
				'description' =>
					__( 'Add CSS padding (in pixels) to this sidebar. Default: none', 'off-canvas-sidebars' ) . '<br>' .
					__( 'You can overwrite this with CSS', 'off-canvas-sidebars' ),
				'input_after' => '<code>px</code>',
			)
		);
		add_settings_field(
			'background_color',
			esc_attr__( 'Background color', 'off-canvas-sidebars' ),
			array( $this, 'color_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'background_color',
				'description' =>
					__( 'Choose a background color for this sidebar. Default: <code>#222222</code>.', 'off-canvas-sidebars' ) . '<br>' .
					__( 'You can overwrite this with CSS', 'off-canvas-sidebars' ),
			)
		);

		add_settings_field(
			'overwrite_global_settings',
			esc_attr__( 'Overwrite global settings', 'off-canvas-sidebars' ),
			array( $this, 'checkbox_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'overwrite_global_settings',
			)
		);
		add_settings_field(
			'site_close',
			esc_attr__( 'Close sidebar when clicking on the site', 'off-canvas-sidebars' ),
			array( $this, 'checkbox_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'site_close',
				'label' => __( 'Enables closing of a off-canvas sidebar by clicking on the site. Default: true.', 'off-canvas-sidebars' ),
			)
		);
		add_settings_field(
			'disable_over',
			esc_attr__( 'Disable over', 'off-canvas-sidebars' ),
			array( $this, 'number_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'disable_over',
				'description' => __( 'Disable off-canvas sidebars over specified screen width. Leave blank to disable.', 'off-canvas-sidebars' ),
				'input_after' => '<code>px</code>',
			)
		);
		add_settings_field(
			'hide_control_classes',
			esc_attr__( 'Auto-hide control classes', 'off-canvas-sidebars' ),
			array( $this, 'checkbox_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'hide_control_classes',
				'label' => __( 'Hide off-canvas sidebar control classes over width specified in <strong>"Disable over"</strong>. Default: false.', 'off-canvas-sidebars' ),
			)
		);
		add_settings_field(
			'scroll_lock',
			esc_attr__( 'Scroll lock', 'off-canvas-sidebars' ),
			array( $this, 'checkbox_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'scroll_lock',
				'label' => __( 'Prevent site content scrolling whilst a off-canvas sidebar is open. Default: false.', 'off-canvas-sidebars' ),
			)
		);

		add_settings_field(
			'sidebar_delete',
			esc_attr__( 'Delete sidebar', 'off-canvas-sidebars' ),
			array( $this, 'checkbox_option' ),
			$this->sidebars_tab,
			'section_sidebar_' . $sidebar_id,
			array(
				'sidebar' => $sidebar_id,
				'name' => 'delete',
				'value' => 0,
			)
		);

	}

	/*
	 * Specific fields
	 */
	function frontend_type_option( $args ) {
		$prefixes     = $this->get_option_prefixes( $args );
		$prefix_name  = $prefixes['prefixName'];
		$prefix_value = $prefixes['prefixValue'];
		$prefix_id    = $prefixes['prefixId'];
		?><fieldset class="radio">
			<label><input type="radio" name="<?php echo $prefix_name . '[frontend_type]'; ?>" id="<?php echo $prefix_id . '_style_action'; ?>" value="action" <?php checked( $prefix_value['frontend_type'], 'action' ); ?> /> <?php _e( 'Actions', 'off-canvas-sidebars' ); echo ' (' . __( 'Default', 'off-canvas-sidebars' ) . ')' ?></label>
			<label><input type="radio" name="<?php echo $prefix_name . '[frontend_type]'; ?>" id="<?php echo $prefix_id . '_style_jquery'; ?>" value="jquery" <?php checked( $prefix_value['frontend_type'], 'jquery' ); ?> /> <?php _e( 'jQuery', 'off-canvas-sidebars' ); echo ' (' . __( 'Experimental', 'off-canvas-sidebars' ) . ')' ?></label>
			<?php if ( isset( $args['description'] ) ) { ?>
			<p class="description"><?php echo $args['description'] ?></p>
			<?php } ?>
		</fieldset><?php
	}

	function enabled_sidebars_option() {
		$prefix_name  = esc_attr( $this->general_key ) . '[sidebars]';
		$prefix_value = $this->settings['sidebars'];
		$prefix_id    = $this->general_key . '_sidebars';
		//$prefix_classes = array( $prefix_id );
		if ( ! empty( $this->settings['sidebars'] ) ) {
		?><fieldset class="checkbox"><?php
			foreach ( $prefix_value as $sidebar => $sidebar_data ) {
				//$classes = $this->get_option_classes( $prefix_classes, 'enable' );
			?>
				<label><input type="checkbox" name="<?php echo $prefix_name . '[' . $sidebar . '][enable]'; ?>" id="<?php echo $prefix_id . '_enable_' . $sidebar; ?>" value="1" <?php checked( $prefix_value[ $sidebar ]['enable'], 1 ); ?> /> <?php echo $this->settings['sidebars'][ $sidebar ]['label']; ?></label>
			<?php
			}
		?>
		<input type="hidden" name="<?php echo $prefix_name . '[ocs_update]'; ?>" value="1" />
		</fieldset>
		<?php
		} else {
			echo '<a href="?page=' . esc_attr( $this->plugin_key ) . '&tab=' . esc_attr( $this->sidebars_tab ) . '">'
			     . __( 'Click here to add off-canvas sidebars', 'off-canvas-sidebars' ) . '</a>';
		}
	}

	function sidebar_location( $args ) {
		$prefixes       = $this->get_option_prefixes( $args );
		$prefix_name    = $prefixes['prefixName'];
		$prefix_value   = $prefixes['prefixValue'];
		$prefix_id      = $prefixes['prefixId'];
		$prefix_classes = $prefixes['prefixClasses'];
		if ( isset( $args['sidebar'] ) ) {
			$classes = $this->get_option_classes( $prefix_classes, 'location' );
		?><fieldset class="radio">
			<label><input type="radio" name="<?php echo $prefix_name . '[location]'; ?>" class="<?php echo $classes; ?>" id="<?php echo $prefix_id . '_location_left'; ?>" value="left" <?php checked( $prefix_value['location'], 'left' ); ?> /> <?php _e( 'Left', 'off-canvas-sidebars' ); ?></label>
			<label><input type="radio" name="<?php echo $prefix_name . '[location]'; ?>" class="<?php echo $classes; ?>" id="<?php echo $prefix_id . '_location_right'; ?>" value="right" <?php checked( $prefix_value['location'], 'right' ); ?> /> <?php _e( 'Right', 'off-canvas-sidebars' ); ?></label>
			<label><input type="radio" name="<?php echo $prefix_name . '[location]'; ?>" class="<?php echo $classes; ?>" id="<?php echo $prefix_id . '_location_top'; ?>" value="top" <?php checked( $prefix_value['location'], 'top' ); ?> /> <?php _e( 'Top', 'off-canvas-sidebars' ); ?></label>
			<label><input type="radio" name="<?php echo $prefix_name . '[location]'; ?>" class="<?php echo $classes; ?>" id="<?php echo $prefix_id . '_location_bottom'; ?>" value="bottom" <?php checked( $prefix_value['location'], 'bottom' ); ?> /> <?php _e( 'Bottom', 'off-canvas-sidebars' ); ?></label>
			<?php if ( isset( $args['description'] ) ) { ?>
			<p class="description"><?php echo $args['description'] ?></p>
			<?php } ?>
		</fieldset><?php
		}
	}

	function sidebar_size( $args ) {
		$prefixes       = $this->get_option_prefixes( $args );
		$prefix_name    = $prefixes['prefixName'];
		$prefix_value   = $prefixes['prefixValue'];
		$prefix_id      = $prefixes['prefixId'];
		$prefix_classes = $prefixes['prefixClasses'];
		if ( isset( $args['sidebar'] ) ) {
			$classes = $this->get_option_classes( $prefix_classes, 'size' );
		?><fieldset class="radio">
			<label><input type="radio" name="<?php echo $prefix_name . '[size]'; ?>" class="<?php echo $classes; ?>" id="<?php echo $prefix_id . '_size_default'; ?>" value="default" <?php checked( $prefix_value['size'], 'default' ); ?> /> <?php _e( 'Default', 'off-canvas-sidebars' ); ?></label>
			<label><input type="radio" name="<?php echo $prefix_name . '[size]'; ?>" class="<?php echo $classes; ?>" id="<?php echo $prefix_id . '_size_small'; ?>" value="small" <?php checked( $prefix_value['size'], 'small' ); ?> /> <?php _e( 'Small', 'off-canvas-sidebars' ); ?></label>
			<label><input type="radio" name="<?php echo $prefix_name . '[size]'; ?>" class="<?php echo $classes; ?>" id="<?php echo $prefix_id . '_size_large'; ?>" value="large" <?php checked( $prefix_value['size'], 'large' ); ?> /> <?php _e( 'Large', 'off-canvas-sidebars' ); ?></label>
			<div class="custom-input">
				<label style="display: inline-block">
					<input type="radio" name="<?php echo $prefix_name . '[size]'; ?>" class="<?php echo $classes; ?>" id="<?php echo $prefix_id . '_size_custom'; ?>" value="custom" <?php checked( $prefix_value['size'], 'custom' ); ?> /> <?php _e( 'Custom', 'off-canvas-sidebars' ); ?>
				</label>:
				<input type="number" name="<?php echo $prefix_name . '[size_input]'; ?>" class="<?php echo $this->get_option_classes( $prefix_classes, 'size_input' ); ?>" min="1" max="" step="1" value="<?php echo $prefix_value['size_input'] ?>" />
				<select name="<?php echo $prefix_name . '[size_input_type]'; ?>" class="<?php echo $this->get_option_classes( $prefix_classes, 'size_input_type' ); ?>">
					<option value="%" <?php selected( $prefix_value['size_input_type'], '%' ); ?>>%</option>
					<option value="px" <?php selected( $prefix_value['size_input_type'], 'px' ); ?>>px</option>
				</select>
			</div>
			<?php if ( isset( $args['description'] ) ) { ?>
			<p class="description"><?php echo $args['description'] ?></p>
			<?php } ?>
		</fieldset><?php
		}
	}

	function sidebar_style( $args ) {
		$prefixes = $this->get_option_prefixes( $args );
		$prefix_name = $prefixes['prefixName'];
		$prefix_value = $prefixes['prefixValue'];
		$prefix_id = $prefixes['prefixId'];
		$prefix_classes = $prefixes['prefixClasses'];
		if ( isset( $args['sidebar'] ) ) {
			$classes = $this->get_option_classes( $prefix_classes, 'style' );
		?><fieldset class="radio">
			<label><input type="radio" name="<?php echo $prefix_name . '[style]'; ?>" class="<?php echo $classes; ?>" id="<?php echo $prefix_id . '_style_push'; ?>" value="push" <?php checked( $prefix_value['style'], 'push' ); ?> /> <?php _e( 'Sidebar slides and pushes the site across when opened.', 'off-canvas-sidebars' ); ?></label>
			<label><input type="radio" name="<?php echo $prefix_name . '[style]'; ?>" class="<?php echo $classes; ?>" id="<?php echo $prefix_id . '_style_reveal'; ?>" value="reveal" <?php checked( $prefix_value['style'], 'reveal' ); ?> /> <?php _e( 'Sidebar reveals and pushes the site across when opened.', 'off-canvas-sidebars' ); ?></label>
			<label><input type="radio" name="<?php echo $prefix_name . '[style]'; ?>" class="<?php echo $classes; ?>" id="<?php echo $prefix_id . '_style_shift'; ?>" value="shift" <?php checked( $prefix_value['style'], 'shift' ); ?> /> <?php _e( 'Sidebar shifts and pushes the site across when opened.', 'off-canvas-sidebars' ); ?></label>
			<label><input type="radio" name="<?php echo $prefix_name . '[style]'; ?>" class="<?php echo $classes; ?>" id="<?php echo $prefix_id . '_style_overlay'; ?>" value="overlay" <?php checked( $prefix_value['style'], 'overlay' ); ?> /> <?php _e( 'Sidebar overlays the site when opened.', 'off-canvas-sidebars' ); ?></label>
			<?php if ( isset( $args['description'] ) ) { ?>
			<p class="description"><?php echo $args['description'] ?></p>
			<?php } ?>
		</fieldset><?php
		}
	}

	/**
	 * Validates post values
	 *
	 * @since  0.1
	 *
	 * @param  array  $input
	 * @return array  $output
	 */
	function validate_input( $input ) {
		// First set current values
		$output = $this->settings;
		$tab = $_POST['ocs_tab'];

		// Add new sidebar
		if ( ! empty( $input['sidebars']['ocs_add_new'] ) ) {
			$new_sidebar_id = $this->validate_id( $input['sidebars']['ocs_add_new'] );
			if ( empty( $input['sidebars'][ $new_sidebar_id ] ) && empty( $output['sidebars'][ $new_sidebar_id ] ) ) {
				$input['sidebars'][ $new_sidebar_id ] = array(
					'enable' => 1,
					'label' => strip_tags( stripslashes( $input['sidebars']['ocs_add_new'] ) ),
				);
			} else {
				// Translators: %s stands for a sidebar ID.
				add_settings_error( $new_sidebar_id . '_duplicate_id', esc_attr( 'ocs_duplicate_id' ), sprintf( __( 'The ID %s already exists! Sidebar not added.', 'off-canvas-sidebars' ), '<code>' . $new_sidebar_id . '</code>' ) );
			}
		}
		unset( $input['sidebars']['ocs_add_new'] );

		if ( $tab === $this->settings_tab ) {
			$input['enable_frontend']      = ( ! empty( $input['enable_frontend'] ) ) ? 1 : 0;
			$input['site_close']           = ( ! empty( $input['site_close'] ) ) ? 1 : 0;
			$input['hide_control_classes'] = ( ! empty( $input['hide_control_classes'] ) ) ? 1 : 0;
			$input['scroll_lock']          = ( ! empty( $input['scroll_lock'] ) ) ? 1 : 0;
			$input['use_fastclick']        = ( ! empty( $input['use_fastclick'] ) ) ? 1 : 0;
		}

		// Handle existing sidebars
		if ( isset( $input['sidebars'] ) ) {

			// Update trigger, always remove
			if ( isset( $input['sidebars']['ocs_update'] ) ) {
				unset( $input['sidebars']['ocs_update'] );
			}

			foreach ( $output['sidebars'] as $sidebar_id => $sidebar_data ) {

				if ( ! isset( $input['sidebars'][ $sidebar_id ] ) ) {
					$input['sidebars'][ $sidebar_id ] = $output['sidebars'][ $sidebar_id ];
					// Sidebars are set but this sidebar isn't checked as active
					$input['sidebars'][ $sidebar_id ]['enable'] = 0;
				}

				// Global settings page
				if ( count( $input['sidebars'][ $sidebar_id ] ) < 2 ) {
					$output['sidebars'][ $sidebar_id ]['enable'] = $this->validate_checkbox( $input['sidebars'][ $sidebar_id ]['enable'] );
					$input['sidebars'][ $sidebar_id ] = $output['sidebars'][ $sidebar_id ];
				}

				// Default label is sidebar ID
				if ( empty( $input['sidebars'][ $sidebar_id ]['label'] ) ) {
					$input['sidebars'][ $sidebar_id ]['label'] = $sidebar_id;
				}

				// Change sidebar ID
				if ( ! empty( $input['sidebars'][ $sidebar_id ]['id'] ) && $sidebar_id !== $input['sidebars'][ $sidebar_id ]['id'] ) {

					$new_sidebar_id = $this->validate_id( $input['sidebars'][ $sidebar_id ]['id'] );

					if ( $sidebar_id !== $new_sidebar_id ) {

						if ( empty( $input['sidebars'][ $new_sidebar_id ] ) ) {

							$input['sidebars'][ $new_sidebar_id ] = $input['sidebars'][ $sidebar_id ];
							$input['sidebars'][ $new_sidebar_id ]['id'] = $new_sidebar_id;

							unset( $input['sidebars'][ $sidebar_id ] );

							$this->migrate_sidebars_widgets( $sidebar_id, $new_sidebar_id );
						} else {
							// Translators: %s stands for a sidebar ID.
							add_settings_error( $sidebar_id . '_duplicate_id', esc_attr( 'ocs_duplicate_id' ), sprintf( __( 'The ID %s already exists! The ID is not changed.', 'off-canvas-sidebars' ), '<code>' . $new_sidebar_id . '</code>' ) );
						}
					}
				}
			} // End foreach().
		} // End if().

		// Overwrite non existing values with current values
		foreach ( $output as $key => $value ) {
			if ( ! isset( $input[ $key ] ) ) {
				$input[ $key ] = $value;
			}
		}

		// Overwrite the old settings
		$output = $input;

		if ( $tab === $this->settings_tab ) {
			// Make sure unchecked checkboxes are 0 on save
			$output['enable_frontend']               = $this->validate_checkbox( $output['enable_frontend'] );
			$output['site_close']                    = $this->validate_checkbox( $output['site_close'] );
			$output['hide_control_classes']          = $this->validate_checkbox( $output['hide_control_classes'] );
			$output['scroll_lock']                   = $this->validate_checkbox( $output['scroll_lock'] );
			$output['use_fastclick']                 = $this->validate_checkbox( $output['use_fastclick'] );

			// Numeric values (not integers!)
			$output['disable_over'] = $this->validate_numeric( $output['disable_over'] );

			// Remove whitespaces
			$output['website_before_hook'] = $this->remove_whitespace( $output['website_before_hook'] );
			$output['website_after_hook']  = $this->remove_whitespace( $output['website_after_hook'] );

			// Attribute validation
			$output['css_prefix'] = $this->validate_id( $output['css_prefix'] );

			// Validate radio options
			$output['compatibility_position_fixed'] = $this->validate_radio( $output['compatibility_position_fixed'], array( 'none', 'custom-js', 'legacy-css' ), 'none' );

			// Set default values if no value is set
			if ( empty( $output['css_prefix'] ) ) {
				$output['css_prefix'] = 'ocs';
			}
		}

		foreach ( $output['sidebars'] as $sidebar_id => $sidebar_data ) {

			// Delete sidebar
			if ( ! empty( $input['sidebars'][ $sidebar_id ]['delete'] ) ) {
				unset( $input['sidebars'][ $sidebar_id ] );
				unset( $output['sidebars'][ $sidebar_id ] );
			} else {

				// Make sure unchecked checkboxes are 0 on save
				$output['sidebars'][ $sidebar_id ]['enable']                    = $this->validate_checkbox( $output['sidebars'][ $sidebar_id ]['enable'] );
				$output['sidebars'][ $sidebar_id ]['overwrite_global_settings'] = $this->validate_checkbox( $output['sidebars'][ $sidebar_id ]['overwrite_global_settings'] );
				$output['sidebars'][ $sidebar_id ]['site_close']                = $this->validate_checkbox( $output['sidebars'][ $sidebar_id ]['site_close'] );
				$output['sidebars'][ $sidebar_id ]['hide_control_classes']      = $this->validate_checkbox( $output['sidebars'][ $sidebar_id ]['hide_control_classes'] );
				$output['sidebars'][ $sidebar_id ]['scroll_lock']               = $this->validate_checkbox( $output['sidebars'][ $sidebar_id ]['scroll_lock'] );

				// Numeric values (not integers!)
				$output['sidebars'][ $sidebar_id ]['padding']         = $this->validate_numeric( $output['sidebars'][ $sidebar_id ]['padding'] );
				$output['sidebars'][ $sidebar_id ]['disable_over']    = $this->validate_numeric( $output['sidebars'][ $sidebar_id ]['disable_over'] );
				$output['sidebars'][ $sidebar_id ]['animation_speed'] = $this->validate_numeric( $output['sidebars'][ $sidebar_id ]['animation_speed'] );

				// Validate radio options
				$output['sidebars'][ $sidebar_id ]['content'] = $this->validate_radio( $output['sidebars'][ $sidebar_id ]['content'], array( 'sidebar', 'menu', 'action' ), 'sidebar' );

				$new_sidebar_id = $this->validate_id( $sidebar_id );
				if ( $sidebar_id !== $new_sidebar_id ) {
					$output['sidebars'][ $new_sidebar_id ] = $output['sidebars'][ $sidebar_id ];
					$output['sidebars'][ $new_sidebar_id ]['id'] = $new_sidebar_id;

					unset( $output['sidebars'][ $sidebar_id ] );

					$this->migrate_sidebars_widgets( $sidebar_id, $new_sidebar_id );
				}
			}
		}

		// Validate global settings with defaults
		$output = off_canvas_sidebars()->validate_settings( $output, off_canvas_sidebars()->get_default_settings() );
		// Validate sidebar settings with defaults
		foreach ( $output['sidebars'] as $sidebar_id => $sidebar_settings ) {
			$output['sidebars'][ $sidebar_id ] = off_canvas_sidebars()->validate_settings( $sidebar_settings, off_canvas_sidebars()->get_default_sidebar_settings() );
		}

		if ( isset( $output['ocs_tab'] ) ) {
			unset( $output['ocs_tab'] );
		}

		return $output;
	}

	/**
	 * Updates the existing widgets when a sidebar ID changes
	 *
	 * @since  0.3
	 *
	 * @param  string  $old_id
	 * @param  string  $new_id
	 */
	function migrate_sidebars_widgets( $old_id, $new_id ) {
		$old_id = 'off-canvas-' . $old_id;
		$new_id = 'off-canvas-' . $new_id;
		$sidebars_widgets = wp_get_sidebars_widgets();

		if ( ! empty( $sidebars_widgets[ $old_id ] ) ) {
			$sidebars_widgets[ $new_id ] = $sidebars_widgets[ $old_id ];
			unset( $sidebars_widgets[ $old_id ] );
		}

		wp_set_sidebars_widgets( $sidebars_widgets );
	}

	/**
	 * Validates checkbox values, used by validate_input
	 *
	 * @since  0.1.2
	 *
	 * @param  mixed  $value
	 * @return int    $value
	 */
	function validate_checkbox( $value ) {
		return ( ! empty( $value ) ) ? (int) strip_tags( $value ) : 0;
	}

	/**
	 * Validates radio values against the possible options
	 *
	 * @since  0.4
	 *
	 * @param  string $value
	 * @param  array  $options
	 * @param  string $default
	 * @return int    $value
	 */
	function validate_radio( $value, $options, $default ) {
		return ( ! empty( $value ) && in_array( $value, $options, true ) ) ? strip_tags( $value ) : $default;
	}

	/**
	 * Validates id values, used by validate_input
	 *
	 * @since  0.2
	 * @since  0.3  Convert to lowercase and convert spaces to dashes before preg_replace
	 *
	 * @param  string $value
	 * @return string $value
	 */
	function validate_id( $value ) {
		return preg_replace( '/[^a-z0-9_-]+/i', '', str_replace( ' ', '-', strtolower( $value ) ) );
	}

	/**
	 * Validates numeric values, used by validate_input
	 *
	 * @since  0.2.2
	 *
	 * @param  mixed $value
	 * @return string $value
	 */
	function validate_numeric( $value ) {
		return ( ! empty( $value ) && is_numeric( $value ) ) ? (string) absint( $value ) : '';
	}

	/**
	 * Remove whitespace
	 *
	 * @since  0.3
	 *
	 * @param  mixed $value
	 * @return string $value
	 */
	function remove_whitespace( $value ) {
		return ( ! empty( $value ) ) ? str_replace( array( ' ' ), '', (string) $value ) : '';
	}

	/*
	 * Plugin Options page rendering goes here, checks
	 * for active tab and replaces key with the related
	 * settings key. Uses the plugin_options_tabs method
	 * to render the tabs.
	 *
	 * @since  0.1
	 */
	function plugin_options_page() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->settings_tab;
		$do_submit = ( in_array( $tab, array( $this->settings_tab, $this->sidebars_tab ), true ) ) ? true : false;
		?>
	<div class="wrap">
		<h1><?php _e( 'Off-Canvas Sidebars', 'off-canvas-sidebars' ) ?></h1>
		<?php $this->plugin_options_tabs(); ?>
		<div class="<?php echo $this->plugin_key ?> container">

			<form id="<?php echo $this->general_key ?>" method="post" action="options.php" enctype="multipart/form-data">

				<?php settings_errors(); ?>
				<?php if ( $do_submit ) { ?>
				<p class="alignright"><?php submit_button( null, 'primary', 'submit', false ); ?></p>
				<?php } ?>
				<input id="ocs_tab" type="hidden" name="ocs_tab" value="<?php echo $tab ?>" />

				<?php if ( $tab === $this->settings_tab ) { ?>
				<p><?php // Translators: %s stands for a URL.
					echo sprintf( __( 'You can add the control buttons with a widget, menu item or with custom code, <a href="%s" target="_blank">click here for documentation.</a>', 'off-canvas-sidebars' ), 'https://wordpress.org/plugins/off-canvas-sidebars/installation/' ); ?></p>
				<p><?php echo $this->general_labels['compatibility_notice_theme']; ?></p>
				<?php } elseif ( $tab === $this->sidebars_tab ) { ?>
				<p>
					<?php _e( 'Add a new sidebar', 'off-canvas-sidebars' ) ?> <input name="<?php echo esc_attr( $this->general_key ) . '[sidebars][ocs_add_new]'; ?>" value="" type="text" placeholder="<?php _e( 'Name', 'off-canvas-sidebars' ) ?>" />
					<?php submit_button( __( 'Add sidebar', 'off-canvas-sidebars' ), 'primary', 'submit', false ); ?>
				</p>
				<?php } ?>

				<div class="metabox-holder">
				<div class="postbox-container">
				<div id="main-sortables" class="meta-box-sortables ui-sortable">
				<?php settings_fields( $tab ); ?>
				<?php $this->do_settings_sections( $tab ); ?>

				<?php if ( $tab === $this->shortcode_tab ) $this->shortcode_tab(); ?>
				<?php if ( $tab === $this->importexport_tab ) $this->importexport_tab(); ?>
				</div>
				</div>
				</div>
				<?php if ( $do_submit ) submit_button(); ?>

			</form>

			<div class="ocs-sidebar">
				<div class="ocs-credits">
					<h3 class="hndle"><?php echo __( 'Off-Canvas Sidebars', 'off-canvas-sidebars' ) . ' ' . OCS_PLUGIN_VERSION ?></h3>
					<div class="inside">
						<h4 class="inner"><?php _e( 'Need support?', 'off-canvas-sidebars' ) ?></h4>
						<p class="inner">
							<?php // Translators: %1$s and %2$s stands for a URL.
							echo sprintf( __( 'If you are having problems with this plugin, checkout plugin <a href="%1$s" target="_blank">Documentation</a> or talk about them in the <a href="%2$s" target="_blank">Support forum</a>', 'off-canvas-sidebars' ), 'https://wordpress.org/plugins/off-canvas-sidebars/installation/', 'https://wordpress.org/support/plugin/off-canvas-sidebars' ) ?>
						</p>
						<hr />
						<h4 class="inner"><?php _e( 'Do you like this plugin?', 'off-canvas-sidebars' ) ?></h4>
						<a class="inner" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YGPLMLU7XQ9E8&lc=NL&item_name=Off%2dCanvas%20Sidebars&item_number=JWPP%2dOCS&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted" target="_blank">
							<img alt="PayPal - The safer, easier way to pay online!" border="0" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif">
						</a>
						<p class="inner">
						<a href="https://wordpress.org/support/plugin/off-canvas-sidebars/reviews/" target="_blank"><?php _e( 'Rate it 5 on WordPress.org', 'off-canvas-sidebars' ) ?></a><br />
						<a href="https://wordpress.org/plugins/off-canvas-sidebars/" target="_blank"><?php _e( 'Blog about it & link to the plugin page', 'off-canvas-sidebars' ) ?></a><br />
						<a href="https://profiles.wordpress.org/keraweb/#content-plugins" target="_blank"><?php _e( 'Check out my other WordPress plugins', 'off-canvas-sidebars' ) ?></a><br />
						</p>
						<hr />
						<h4 class="inner"><?php _e( 'Want to help?', 'off-canvas-sidebars' ) ?></h4>
						<p class="inner">
						<a href="https://github.com/JoryHogeveen/off-canvas-sidebars" target="_blank"><?php _e( 'Follow and/or contribute on GitHub', 'off-canvas-sidebars' ) ?></a>
						</p>
						<hr />
						<p class="ocs-link inner"><?php _e( 'Created by', 'off-canvas-sidebars' ) ?> <a href="https://profiles.wordpress.org/keraweb/" target="_blank" title="Keraweb - Jory Hogeveen"><!--<img src="' . plugins_url( '../images/logo-keraweb.png', __FILE__ ) . '" title="Keraweb - Jory Hogeveen" alt="Keraweb - Jory Hogeveen" />-->Keraweb (Jory Hogeveen)</a></p>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
		//add_action( 'in_admin_footer', array( 'OCS_Lib', 'admin_footer' ) );
	}

	/**
	 * This function is similar to the function in the Settings API, only the output HTML is changed.
	 * Print out the settings fields for a particular settings section
	 *
	 * @since  0.1
	 *
	 * @global $wp_settings_sections  array of settings sections
	 * @global $wp_settings_fields    array of settings fields and their pages/sections
	 *
	 * @param  string  $page     Slug title of the admin page who's settings fields you want to show.
	 * param  string  $section  Slug title of the settings section who's fields you want to show.
	 */
	function do_settings_sections( $page ) {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections[ $page ] ) )
			return;

		foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
			$box_classes = 'stuffbox postbox ' . $section['id'] . '';
			if ( $page === $this->sidebars_tab ) {
				$box_classes .= ' section-sidebar if-js-closed';
			}
			echo '<div id="' . $section['id'] . '" class="' . $box_classes . '">';
			echo '<button type="button" class="handlediv button-link" aria-expanded="true"><span class="screen-reader-text">' . __( 'Toggle panel', 'off-canvas-sidebars' ) . '</span><span class="toggle-indicator" aria-hidden="true"></span></button>';
			if ( $section['title'] )
				echo "<h3 class=\"hndle\"><span>{$section['title']}</span></h3>\n";

			if ( $section['callback'] )
				call_user_func( $section['callback'], $section );

			if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) )
				continue;
			echo '<div class="inside"><table class="form-table">';

			if ( $page === $this->sidebars_tab ) {
				echo '<tr class="sidebar_classes" style="display: none;"><th>' . __( 'ID & Classes', 'off-canvas-sidebars' ) . '</th><td>';
				echo  __( 'Sidebar ID', 'off-canvas-sidebars' ) . ': <code>#' . $this->settings['css_prefix'] . '-<span class="js-dynamic-id"></span></code> &nbsp; '
					. __( 'Trigger Classes', 'off-canvas-sidebars' ) . ': <code>.' . $this->settings['css_prefix'] . '-toggle-<span class="js-dynamic-id"></span></code> <code>.' . $this->settings['css_prefix'] . '-open-<span class="js-dynamic-id"></span></code> <code>.' . $this->settings['css_prefix'] . '-close-<span class="js-dynamic-id"></span></code>';
				echo '</td></tr>';
			}
			do_settings_fields( $page, $section['id'] );
			echo '</table>';
			if ( $page === $this->sidebars_tab ) {
				submit_button( null, 'primary', 'submit', false );
			}
			echo '</div>';
			echo '</div>';
		}
	}

	/*
	 * Renders our tabs in the plugin options page,
	 * walks through the object's tabs array and prints
	 * them one by one. Provides the heading for the
	 * plugin_options_page method.
	 *
	 * @since  0.1
	 */
	function plugin_options_tabs() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->settings_tab;

		echo '<h1 class="nav-tab-wrapper">';
		foreach ( $this->plugin_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab === $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . esc_attr( $active ) . '" href="?page=' . esc_attr( $this->plugin_key ) . '&amp;tab=' . esc_attr( $tab_key ) . '">' . esc_html( $tab_caption ) . '</a>';
		}
		echo '</h1>';
	}

	function shortcode_tab() {
		?>
		<div id="section_shortcode" class="stuffbox postbox">
			<h3 class="hndle"><span><?php _e( 'Shortcode', 'off-canvas-sidebars' ) ?>:</span></h3>
			<div class="inside">
			<textarea id="ocs_shortcode" class="widefat">[ocs_trigger sidebar=""]</textarea>
		</div></div>
		<?php

		echo '<div id="section_shortcode_options" class="stuffbox postbox postbox postbox-third first">';

		echo '<h3 class="hndle"><span>' . __( 'Required options', 'off-canvas-sidebars' ) . ':</span></h3>';

		echo '<div class="inside"><table class="form-table">';
		echo '<tr><td>';

		$sidebar_select = array();
		foreach ( $this->settings['sidebars'] as $sidebar_id => $sidebar_data ) {
			$sidebar_select[] = array(
				'value' => $sidebar_id,
				'label' => $sidebar_data['label'],
			);
		}
		$this->select_option( array(
			'name' => 'sidebar',
			'label' => __( 'Sidebar ID', 'off-canvas-sidebars' ),
			'description' => __( '(Required) The off-canvas sidebar ID', 'off-canvas-sidebars' ),
			'options' => $sidebar_select,
		) );

		echo '</td></tr>';
		echo '<tr><td>';

		$this->text_option( array(
			'name'        => 'text',
			'label'       => __( 'Text', 'off-canvas-sidebars' ),
			'value'       => '',
			'class'       => 'widefat',
			'description' => __( 'Limited HTML allowed', 'off-canvas-sidebars' ),
			'multiline'   => true,
		) );

		echo '</td></tr>';
		echo '</table></div></div>';

		echo '<div id="section_shortcode_optionaloptions" class="stuffbox postbox postbox postbox-third">';

		echo '<h3 class="hndle"><span>' . __( 'Optional options', 'off-canvas-sidebars' ) . ':</span></h3>';

		echo '<div class="inside"><table class="form-table">';
		echo '<tr><td>';

		$this->select_option( array(
			'name' => 'action',
			'label' => __( 'Trigger action', 'off-canvas-sidebars' ),
			'options' => array(
				array(
					'label' => __( 'Toggle', 'off-canvas-sidebars' ) . ' (' . __( 'Default', 'off-canvas-sidebars' ) . ')',
					'value' => '',
				),
				array( 'label' => __( 'Open', 'off-canvas-sidebars' ), 'value' => 'open' ),
				array( 'label' => __( 'Close', 'off-canvas-sidebars' ), 'value' => 'close' ),
			),
			//'tooltip' => __( 'The trigger action. Default: toggle', 'off-canvas-sidebars' ),
		) );

		echo '</td></tr>';
		echo '<tr><td>';

		$elements = array( 'button', 'span', 'a', 'b', 'strong', 'i', 'em', 'img', 'div' );
		$element_values = array();
		$element_values[] = array(
			'value' => '',
			'label' => ' - ' . __( 'Select', 'off-canvas-sidebars' ) . ' - ',
		);
		foreach ( $elements as $e ) {
			$element_values[] = array(
				'value' => $e,
				'label' => '' . $e . '',
			);
		}
		$this->select_option( array(
			'name' => 'element',
			'label' => __( 'HTML element', 'off-canvas-sidebars' ),
			'options' => $element_values,
			'description' => __( 'Choose wisely', 'off-canvas-sidebars' ) . '. ' . __( 'Default', 'off-canvas-sidebars' ) . ': <code>button</code>',
		) );

		echo '</td></tr>';
		echo '<tr><td>';

		$this->text_option( array(
			'name' => 'class',
			'label' => __( 'Extra classes', 'off-canvas-sidebars' ),
			'value' => '',
			'class' => 'widefat',
			'description' => __( 'Separate multiple classes with a space', 'off-canvas-sidebars' ),
		) );

		echo '</td></tr>';
		echo '<tr><td>';

		$this->text_option( array(
			'name' => 'attr',
			'label' => __( 'Custom attributes', 'off-canvas-sidebars' ),
			'value' => '',
			'class' => 'widefat',
			'description' => __( 'key : value ; key : value', 'off-canvas-sidebars' ),
			'multiline' => true,
		) );

		echo '</td></tr>';
		echo '<tr><td>';

		$this->checkbox_option( array(
			'name' => 'nested',
			'label' => __( 'Nested shortcode', 'off-canvas-sidebars' ) . '?',
			'value' => '',
			'description' => __( '[ocs_trigger text="Your text"] or [ocs_trigger]Your text[/ocs_trigger]', 'off-canvas-sidebars' ),
		) );

		echo '</td></tr>';

		echo '</table></div></div>';
		?>
		<div id="section_shortcode_preview" class="stuffbox postbox postbox-third">
			<h3 class="hndle"><span><?php _e( 'Preview', 'off-canvas-sidebars' ) ?>:</span></h3>
			<div class="inside">
			<div id="ocs_shortcode_preview"></div>
			</div>
			<h3 class="hndle"><span>HTML:</span></h3>
			<div class="inside">
			<textarea id="ocs_shortcode_html" class="widefat"></textarea>
		</div></div>
		<?php
	}

	function importexport_tab() {
	?>
		<h3><?php _e( 'Import/Export Settings', 'off-canvas-sidebars' ); ?></h3>

		<p><a class="submit button" href="?<?php echo $this->plugin_key ?>-export"><?php esc_attr_e( 'Export Settings', 'off-canvas-sidebars' ); ?></a></p>

		<p>
			<input type="hidden" name="<?php echo $this->plugin_key ?>-import" id="<?php echo $this->plugin_key ?>-import" value="true" />
			<?php submit_button( esc_attr__( 'Import Settings', 'off-canvas-sidebars' ), 'button', $this->plugin_key . '-submit', false ); ?>
			<input type="file" name="<?php echo $this->plugin_key ?>-import-file" id="<?php echo $this->plugin_key ?>-import-file" />
		</p>
	<?php
	}

	function register_importexport_settings() {
		$this->plugin_tabs[ $this->importexport_tab ] = esc_attr__( 'Import/Export', 'off-canvas-sidebars' );

		/**
		 * Filter documented in $this->load_plugin_data()
		 */
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}

		if ( isset( $_GET['gocs_message'] ) ) {

			$gocs_message_class = '';
			$gocs_message = '';

			switch ( $_GET['gocs_message'] ) {
				case 1:
					$gocs_message_class = 'updated';
					$gocs_message = esc_attr__( 'Settings Imported', 'off-canvas-sidebars' );
					break;
				case 2:
					$gocs_message_class = 'error';
					$gocs_message = esc_attr__( 'Invalid Settings File', 'off-canvas-sidebars' );
					break;
				case 3:
					$gocs_message_class = 'error';
					$gocs_message = esc_attr__( 'No Settings File Selected', 'off-canvas-sidebars' );
					break;
				default:
					break;
			}

			if ( ! empty( $gocs_message ) ) {
				echo '<div class="' . $gocs_message_class . '"><p>' . esc_html( $gocs_message ) . '</p></div>';
			}
		}

		// export settings
		if ( isset( $_GET[ $this->plugin_key . '-export' ] ) ) {
			header( "Content-Disposition: attachment; filename=" . $this->plugin_key . ".txt" );
			header( 'Content-Type: text/plain; charset=utf-8' );
			$general = $this->settings;

			echo "[START=OCS SETTINGS]\n";
			foreach ( $general as $id => $text )
				echo "$id\t" . wp_json_encode( $text ) . "\n";
			echo "[STOP=OCS SETTINGS]";
			exit;
		}

		// import settings
		if ( isset( $_POST[ $this->plugin_key . '-import' ] ) ) {

			if ( $_FILES[ $this->plugin_key . '-import-file' ]['tmp_name'] ) {

				$import = explode( "\n", file_get_contents( $_FILES[ $this->plugin_key . '-import-file' ]['tmp_name'] ) );
				if ( array_shift( $import ) == "[START=OCS SETTINGS]" && array_pop( $import ) == "[STOP=OCS SETTINGS]" ) {

					$settings = array();
					foreach ( $import as $import_option ) {
						list( $key, $value ) = explode( "\t", $import_option );
						$settings[ $key ] = json_decode( sanitize_text_field( $value ), true );
					}

					// Validate global settings
					$settings = off_canvas_sidebars()->validate_settings( $settings, off_canvas_sidebars()->get_default_settings() );

					// Validate sidebar settings
					if ( ! empty( $settings['sidebars'] ) ) {
						foreach ( $settings['sidebars'] as $sidebar_id => $sidebar_settings ) {
							$settings['sidebars'][ $sidebar_id ] = off_canvas_sidebars()->validate_settings( $sidebar_settings, off_canvas_sidebars()->get_default_sidebar_settings() );
						}
					}

					update_option( $this->general_key, $settings );
					$gocs_message = 1;
				} else {
					$gocs_message = 2;
				}
			} else {
				$gocs_message = 3;
			}

			wp_redirect( admin_url( '/themes.php?page=' . $this->plugin_key . '&tab=' . $this->importexport_tab . '&gocs_message=' . esc_attr( $gocs_message ) ) );
			exit;
		}
	}

	/**
	 * Main Off-Canvas Sidebars Settings Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   0.3
	 * @static
	 * @return  OCS_Off_Canvas_Sidebars_Settings
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

} // end class
