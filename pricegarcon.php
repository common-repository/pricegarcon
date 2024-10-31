<?php
/*
 * @package PriceGarcon
 * @author Jeff Bertrand
 * @version 0.9
 */
/*
Plugin Name: PriceGarcon
Plugin URI: http://pricewaiter.com
Description: Add the PriceWaiter&reg; <em>Name Your Price</em> button to your Posts and Pages. When this plugin is active, look for the moustache icon in the editor! A valid API key is required. <a href="https://www.pricewaiter.com/retailers/signup" title="Sign-up for PriceWaiter" target="_blank">Sign-up today!</a>
Author: Jeff Bertrand
Author URI: http://jeffbertrand.net
Version: 0.9
*/


global $wp_version;

$exit_msg = 'PriceGarcon requires WordPress 3 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update!</a>';
if ( version_compare( $wp_version, '3', '<' ) ) {
	exit($exit_msg);
}


if( !class_exists('PriceGarcon') ):
class PriceGarcon{
	private $s_pluginUrl = '';
	private $s_pluginDir = '';
	private $s_pluginId = 'pricegarcon-settings-admin';
	private $s_optionRef = 'pricegarcon-settings'; // for updating/getting settings saved in the database
	private $s_tinyId = 'pricegarcon'; // TinyMCE, must be the same as the identifier in editor_plugin.js
	
	private $a_defaults = array(
		'apiKey' => '',
		'type' => 'button_nyp',
		'color' => '',
		'colorHover' => '',
		);


	// ***** BEGIN HOUSEKEEPING METHODS *****


	public function __construct(){
		if(is_admin()){
			$this->s_pluginUrl = trailingslashit( WP_PLUGIN_URL.'/'.dirname( plugin_basename(__FILE__)) );
			$this->s_pluginDir = trailingslashit( WP_PLUGIN_DIR . '/' . dirname( plugin_basename(__FILE__)) );

			// hook into navigation and add a form
			add_action('admin_menu', array(&$this, 'admin_menu')); // add link to settings form in admin navigation
			add_action('admin_init', array(&$this, 'plugin_settings'));
			add_filter('plugin_row_meta', array(&$this,'settings_inline'),10,2 ); // in Plugins, add inline Settings link
			
			// hook into TinyMCE
			add_action('init', array(&$this, 'tinymce_init_plugin'));
		}
		
		add_shortcode('pricegarcon', array(&$this,'pricegarcon_shortcode'));
	}


	/* pub func plugin_install()
	 * This method runs when the plugin is first activated. It installs default values in table *options.
	 */
	public function plugin_install(){
		update_option($this->s_optionRef, $this->a_defaults);
	}


	/* pub func plugin_uninstall()
	 * This method runs when the plugin is deactivated. It deletes values saved in the *options table
	 * and removes the previously installed roles.
	 */
	public function plugin_uninstall(){
		global $wpdb;

		$wpdb->query( $wpdb->prepare(
			"
				DELETE FROM {$wpdb->prefix}options WHERE option_name='%s'
			",
			array($this->s_optionRef)
		));
	}


	// ***** BEGIN ADMIN INTERFACE METHODS *****


	/* pub func settings_inline(array,string)
	 * This method adds a Settings link to this plugins entry on the Plugins page. It's not necessary, but some
	 * people like to find a settings link there once they've activated a new plugin.
	 */
	public function settings_inline($a_links, $s_file){
		$s_pluginFile = plugin_basename(__FILE__);

		if ($s_file == $s_pluginFile){
			return array_merge($a_links, array(sprintf('<a href="options-general.php?page=%s">%s</a>', $this->s_pluginId, __('Settings'))) );
		}
		return $a_links;
	}


	/* pub func admin_menu()
	 * This method adds the PriceGarcon link under Settings. This could have been in __construct but dedicating a method
	 * makes it easier to expand features later (like Help Tab support?).
	 */
	public function admin_menu(){
		add_options_page('PriceGarcon', 'PriceGarcon', 'manage_options', $this->s_pluginId, array(&$this, 'plugin_settings_page'));
	}
	
	
	//***** BEGINS SETTINGS METHODS ***** 
	// NOTE The WordPress Settings API is employed to automate form generation/validation and handle permissions.


	/* priv func plugin_settings_page()
	 * This is the form with basic HTML outline and Settings API function calls.
	 */
	public function plugin_settings_page(){
	    ?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>PriceGarcon</h2>			
			<form method="post" action="options.php">
				<?php
				settings_fields($this->s_optionRef);	
				do_settings_sections($this->s_pluginId);
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
	
	/* pub func plugin_settings()
	 * This method registers this plugin will have settings, defines sections that will appear on the form, and
	 * covers the white list of variables a user may update.
	 */
	public function plugin_settings(){
		register_setting($this->s_optionRef, $this->s_optionRef, array(&$this, 'validate_settings'));

		add_settings_section('pricegarcon_main', 'Plugin Settings', array(&$this, 'main_settings_text'), $this->s_pluginId);
		add_settings_section('pricegarcon_button', 'Button Appearance', array(&$this, 'button_settings_text'), $this->s_pluginId);
		
		/* variables are defined in the following format
			add_settings_field( 
				'variableName',
				'displayName',	// wrap display name with <label> tags for improved screen reader support and interactivity
				'methodName',	// name of method that generates <input> tag for this variable
				'pluginId',		// page on which form field appears ("parent")
				'sectionId'		// section on page which form field appears under ("child")
			);
		*/
		
		add_settings_field('apiKey', '<label for="pw-setting-apiKey">API Key</label>', array(&$this, 'form_input_apiKey'), $this->s_pluginId, 'pricegarcon_main');
		add_settings_field('type', '<label for="pw-setting-type" title="\"Name Your Price\" or \"Make And Offer\"">Type</label>', array(&$this, 'form_input_type'), $this->s_pluginId, 'pricegarcon_button');
		add_settings_field('color', '<label for="pw-setting-color" title="When the mouse pointer is not over the button">Color</label>', array(&$this, 'form_input_color'), $this->s_pluginId, 'pricegarcon_button');
		add_settings_field('colorHover', '<label for="pw-setting-colorHover" title="When the mouse pointer is over the button">Hover Color</label>', array(&$this, 'form_input_colorHover'), $this->s_pluginId, 'pricegarcon_button');
		
	}


	/* pub func validate_settings($array)
	 * Scrubs and validates data submitted from the settings form. Untrusted data array is passed as parameter, 
	 * paired with associative array from database (by like keys), and scrubbed based on expected data. An array
	 * of clean pairs is returned.
	 */
	public function validate_settings($input){
		$a_options = get_option($this->s_optionRef);
		
		// apiKey is a string of non-special characters (A-Z, a-z, 0-9)
		// ?? Find out if it's always 50-characters long.
		$a_options['apiKey'] = trim($input['apiKey']);
		if(!preg_match('/^[a-z0-9]+$/i', $a_options['apiKey'])){
			$a_options['apiKey'] = '';
		}
		
		$a_options['type'] = trim($input['type']);
		
		// color is a string of non-special hexadecimal characters (A-F, a-f, 0-9) with optional #
		// regex looks for hex sets of 6 and 3 because HTML accepts that #ffffff is the same as #fff
		$a_options['color'] = trim($input['color'], " \t\n\r\0\x0B#"); // trim leading/trailing white space and #
		if(preg_match('/^[a-f0-9]{6}$/i', $a_options['color']) || preg_match('/^[abcdef0-9]{3}$/i', $a_options['color'])){
			$a_options['color'] = strtolower('#'.$a_options['color']); // lowercase letters are easier to read
		}
		else {
			$a_options['color'] = '';
		}
		
		// See above for notes about 'color'
		$a_options['colorHover'] = trim($input['colorHover'], " \t\n\r\0\x0B#");
		if(preg_match('/^[a-f0-9]{6}$/i', $a_options['colorHover']) || preg_match('/^[abcdef0-9]{3}$/i', $a_options['colorHover'])){
			$a_options['colorHover'] = strtolower('#'.$a_options['colorHover']);
		}
		else {
			$a_options['colorHover'] = '';
		}
		
		return $a_options;
	}
	
	/* 
	 * Descriptive text for form sections
	 */
	
	public function main_settings_text(){
		echo '<p>PriceWaiter&reg; requires <em>all</em> widget users to have a custom API key for each store. <a href="https://www.pricewaiter.com/retailers/signup" title="Sign-up for PriceWaiter" target="_blank">Sign-up for PriceWaiter</a> to receive your valid key.</p>';
	}
	
	public function button_settings_text(){
		echo '<p>Control the look and feel of the Name Your Price button on your website.</p>';
	}
	
	/*
	 * <input> generators for form
	 */
	
	public function form_input_apiKey(){
		$a_options = get_option($this->s_optionRef);
		echo "<input type='text' id='pw-setting-apiKey' name='pricegarcon-settings[apiKey]' size='40' maxlength='128' value='{$a_options['apiKey']}' />";
	}
	
	public function form_input_type(){
		$a_options = get_option($this->s_optionRef);
		$s_type = $a_options['type'];
		echo '<select id="pw-setting-type" name="pricegarcon-settings[type]">'
			// <options> can have a selected="selected" attribute to denote which item in a dropdown has been "used" in the past.
			// I like to use <condition>?<if true>:<if false> inline to apply that sort of thing. Because strcmp() returns 0 when
			// two words match, the <if false> condition needs to have the selected="selected" attribute in it.
			. '<option value="button_nyp"' . ( strcmp($s_type,'button_nyp') ? '' : ' selected="selected"' ) . '>"Name Your Price" button</option>'
			. '<option value="text_nyp"' . ( strcmp($s_type,'text_nyp') ? '' : ' selected="selected"' ) . '>"Name Your Price" text</option>'
			. '<option value="button_mo"' . ( strcmp($s_type,'button_mo') ? '' : ' selected="selected"' ) . '>"Make An Offer" button</option>'
			. '<option value="text_mo"' . ( strcmp($s_type,'text_mo') ? '' : ' selected="selected"' ) . '>"Make An Offer" text</option>'
			. '<select>';
	}
	
	public function form_input_color(){
		$a_options = get_option($this->s_optionRef);
		echo "<input type='text' id='pw-setting-color' name='pricegarcon-settings[color]' size='10' maxlength='7' value='{$a_options['color']}' />"
			. '<p class="description">Example: #97c0e6</p>';
	}
	
	public function form_input_colorHover(){
		$a_options = get_option($this->s_optionRef);
		echo "<input type='text' id='pw-setting-colorHover' name='pricegarcon-settings[colorHover]' size='10' maxlength='7' value='{$a_options['colorHover']}' />"
			. '<p class="description">Example: #6d9fd6</p>';
	}
	
	
	//***** BEGIN TinyMCE METHODS *****


	/* pub func tinymce_init_plugin()
	 * Method is used to queue PriceGarcon's TinyMCE plugin and WYSIWYG button.
	 */
	public function tinymce_init_plugin(){
		if(!current_user_can('edit_posts') && !current_user_can('edit_pages')){
			return;
		}

		if(get_user_option('rich_editing') == 'true'){
			add_filter('mce_external_plugins', array(&$this, 'tinymce_add_plugin'));
			add_filter('mce_buttons', array(&$this, 'tinymce_register_button')); // place PriceGarcon button on first row
		}
	}

	/* pub func tinymce_add_plugin(array)
	 * Method queues PriceGarcon TinyMCE plugin into the mix.
	 */
	public function tinymce_add_plugin($plugin_array){
		$plugin_array[$this->s_tinyId] = $this->s_pluginUrl . '_res/tinymce/editor_plugin.js';
		return $plugin_array;
	}

	/* pub func tinymce_register_button(array)
	 * Method adds PriceGarcon button onto the end of a given row in the WYSIWYG
	 */
	public function tinymce_register_button($buttons){
		array_push($buttons, $this->s_tinyId);
		return $buttons;
	}
	

	//***** BEGIN PLUGIN FEATURE METHODS *****


	/* pub func pricegarcon_shortcode(string,string)
	 * Method is invoked whenever PriceGarcon Shortcode is found in the contents of a Post or Page.
	 * If the Shortcode validates then the PriceWaiter javascript API is injected and the magic happens.
	 * Valid: 	[pricegarcon sku="XXXXXXXXX" name="Roller Blades"]
	 *			[pricegarcon sku="XXXXXXXXX" name="Roller Blades" price="49" image="http://domain.com/pic.jpg"]
	 */
	public function pricegarcon_shortcode($s_params, $s_content=null){
		if(!empty($s_params)){
			$a_options = get_option($this->s_optionRef);

			$a_par = shortcode_atts( array('sku'=>'', 'name'=>'', 'description'=>'', 'price'=>'', 'image'=>''), $s_params);
			
			// PriceWaiter API documentation indicates the API key, SKU, and product name are required
			if(!empty($a_options['apiKey']) && !empty($a_par['sku']) && !empty($a_par['name'])){
				
				// build the button object, omit optional data that is empty
				$s_button = "button: { type: '{$a_options['type']}'"
					. ( !empty($a_options['color']) ? ", color: '{$a_options['color']}'" : '' )
					. ( !empty($a_options['colorHover']) ? ", hoverColor: '{$a_options['colorHover']}'" : '' )
					. '},';

				// build the product object, omit optional data that is empty
				$s_product = "product: { sku: '{$a_par['sku']}', name: '{$a_par['name']}'"
					. ( !empty($a_par['description']) ? ", description: '{$a_par['description']}'" : '' )
					. ( !empty($a_par['price']) ? ", price: {$a_par['price']}" : '' )
					. ( !empty($a_par['image']) ? ", image: '{$a_par['image']}'" : '' )
					. '},';

				return "<span id=\"pricewaiter\"></span>

					<script type=\"text/javascript\">
					var PriceWaiterOptions = {
						apiKey: '{$a_options['apiKey']}',
						{$s_button}
						{$s_product}
						onLoad: function() {
							//alert('PriceWaiter is loaded');
						}
					};
					</script>
			
					<script type=\"text/javascript\">
					(function() {
						var pw = document.createElement('script');
						pw.type = 'text/javascript';
						pw.src = \"https://testing.pricewaiter.com/nyp/script/widget.js\";
						pw.async = true;

						var s = document.getElementsByTagName('script')[0];
						s.parentNode.insertBefore(pw, s);
					})();
					</script>";
			}
		}
		
		return null; // API key was absent or Shortcode was invalid
	}
}
else:
	exit('Class PriceGarcon already declared!');
endif;


$PriceGarcon = new PriceGarcon();


if( isset($PriceGarcon) ){
	register_activation_hook(__FILE__,array(&$PriceGarcon,'plugin_install')); // run one-time install proceedure
	register_deactivation_hook(__FILE__,array(&$PriceGarcon,'plugin_uninstall')); // run one-time uninstall proceedure
}


?>