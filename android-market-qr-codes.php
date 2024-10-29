<?php
/*
Plugin Name: Android Market QR Codes WP Plugin
Plugin URI: http://www.techcredo.com/random-tech/android-market-qr-codes-wordpress-plugin
Description: Paste a link to an app in the Android Web Market or AppBrain, and the plug-in will generate a clickable QR-code for that app
Author: TechCredo.com
Version: 1.02
Date: April 12, 2011
Author URI: http://www.techcredo.com/random-tech/android-market-qr-codes-wordpress-plugin
License: GPL3
*/

// include the file that contains the main function that creates the QR code
include 'qrgenerator.php';

// the part that handles the actual WP shortcode
function qr_creator($atts, $content) { 
	extract(shortcode_atts(array(
		'url' => '', // user can specify a separate URL
	), $atts));
	
	// declare and specify the variables
	// if a separate url is not specified, the url is the content of the shortcode enclosed in [qr] [/qr]
	if (empty($url)){$url = $content;} 
	
	// not sure what this does exactly, either. it obviously has something to do with the language pack(s)
	$theqrcode = new qrgenerator();
	// calls the main function that will return an Android Market QR code
	if (strlen ($url) > 4){ return $theqrcode->create_qr_code($url);}
}
	
// if the current wordpress version supports shortcodes, register the shortcode:
// the name of the shortcode used in wordpress, and the name of the function that handles it
if ( !function_exists('add_shortcode') ) return;
add_shortcode('qr', 'qr_creator'); 
// "init" is the name of the WordPress action we want this plug-in to hook onto, and it 
// runs after WordPress has finished loading - but before any headers are sent.
// the second value should be the name of the function we want to add, i.e. our own main plug-in function, but
// I had to create a function based on the class in order for it to work.
add_action( 'init', create_function( '', 'global $QRc; $QRc = new qrgenerator();' ) );

// a lot of settings bullshit (had a terrible time coding it since the WordPress documentation for it sucked: 
// it was just a complete mess of different and sometimes downright incorrect instructions

// if the user has the proper rights
if ( is_admin() ){ 
	// add options menu in WordPress, and call the function that registers the settings
	add_action('admin_menu', 'qr_plugin_menu');

	// add an options page and register settings
	function qr_plugin_menu() {
		add_options_page('Android Market QR Codes Options', 'Android Market QR Codes', 'manage_options', 'android-qr-codes', 'qr_plugin_options');
		register_setting( 'qr-code-options', 'qrcalign' );
		register_setting( 'qr-code-options', 'qrcsize' );
		register_setting( 'qr-code-options', 'qrcborderc' );
		register_setting( 'qr-code-options', 'qrctitle' );
		register_setting( 'qr-code-options', 'qrcandroidimg' );
		register_setting( 'qr-code-options', 'qrcshowmarketinfo' );
		register_setting( 'qr-code-options', 'qrcappboxcss' );
		register_setting( 'qr-code-options', 'qrcappratingcss' );

		// add  the various options to WordPress mySQL database
		// had this part right in the beginning of create_qr_code in qrgenerator.php earlier, but then
		// the default settings would only be shown in the config screen if the plugin already had been used once
		add_option("qrcalign", 'right');
		add_option("qrcsize", '130x130');
		add_option("qrcborderc", '1px solid #ededed;');
		add_option("qrctitle", 'Scan or click to download');
		add_option("qrcandroidimg", '');
		add_option("qrcshowmarketinfo", 'yes');
		add_option("qrcappboxcss", 'padding:7px; margin:10px; border:1px dotted #cccccc; text-align:right; font-size:12px; font-weight: bold;');
		add_option("qrcappratingcss", 'float:left;font-size:12px;font-weight:bold;');
	}
	
	// the function that shows the actual settings
	function qr_plugin_options() {
	if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
	// we don't want PHP now unless we need to ?>
	<div class="wrap">
	<h2>Android Market QR Codes Options</h2>
	<h3>Settings</h3>
	<form method="post" action="options.php">
	    <?php settings_fields( 'qr-code-options' ); ?>
	    <?php //do_settings( 'qr-code-options' ); ?>
	    <table class="form-table">
	        <tr valign="top">
	        <th scope="row"><strong>QR code alignment</a></strong>. Either "left", "right", "top" or "bottom" (without quotation marks). Leave blank for no alignment.</th>
	        <td width="400px"><input type="text" name="qrcalign" value="<?php echo get_option('qrcalign'); ?>" /></td>
	        </tr>
	
	        <tr valign="top">
	        <th scope="row"><strong>QR code size in pixels</strong>. Must be a square, for example: 150x150</th>
	        <td width="400px"><input type="text" name="qrcsize" value="<?php echo get_option('qrcsize'); ?>" /></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row"><strong>QR code border</strong>. For no border, leave blank. It must be a valid single line CSS value. Default setting: <small>1px solid #ededed;</small></th>
	        <td width="400px"><input type="text" name="qrcborderc" value="<?php echo get_option('qrcborderc'); ?>" /></td>
	        </tr>
	        
	        <tr valign="top">
	        <th scope="row"><strong>QR code title</strong>. The text that is shown when you hover over the QR codes with a mouse cursor.</th>
	        <td width="400px"><input type="text" name="qrctitle" value="<?php echo get_option('qrctitle'); ?>" /></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row"><strong>Download icon for Android devices</strong>. If you want to display a custom icon on Android devices instead of a QR code, enter the full URL to that image here.
			All other options except title and alignment will be ignored for that image.</th>
	        <td width="400px"><input type="text" name="qrcandroidimg" value="<?php echo get_option('qrcandroidimg'); ?>" /></td>
	        </tr>
		
			<tr valign="top">
			<th scope="row">
			<h3>Additional Settings (experimental)</h3>
			The plugin can also display the app's title, official icon and current rating, next to the actual QR code. 
			This feature is a bit experimental and relies on grabbing info directly from the Web Market, so Web Market links are required. 
			If Google changes the structure of the info, this feature might not work until the plugin is updated. If the plugin fails to 
			find the info, a regular QR code will be displayed instead - <em>so it's safe to use either way</em>.</th>
			</tr>
			
	        <tr valign="top">
	        <th scope="row"><strong>Show Android Market info box</strong>. Let the plugin grab the app title, rating and image directly from the Web Market. Requires Web Market links. You can have this option activated and still use AppBrain links and just app titles, although the plugin will then only show the QR code. To activate, simply enter "yes". Anything else will turn the setting off.</th>
	        <td><input type="text" name="qrcshowmarketinfo" value="<?php echo get_option('qrcshowmarketinfo'); ?>" /></td>
	        </tr>
			
	        <tr valign="top">
	        <th scope="row"><strong>Info box CSS</strong>. This setting lets you style the info box with regular CSS. Just enter the CSS like you would do in a normal style sheet, but without class names. For example, to format the text, just enter: "font-size: 12px; font-weight: bold;". Please note that the box will inherit the alignment and height of the QR codes, but not the <em>border</em> value. Default setting: <small>padding:7px; margin:10px; border:1px dotted #cccccc; text-align:right; font-size:12px; font-weight: bold;</small></th>
	        <td width="400px"><input type="text" name="qrcappboxcss" value="<?php echo get_option('qrcappboxcss'); ?>" /></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row"><strong>App rating CSS</strong>. This is the CSS for the app's Market rating, and it works just as the info box above. Default setting: <small>float:left;font-size:12px;font-weight:bold;</small></th>
	        <td width="400px"><input type="text" name="qrcappratingcss" value="<?php echo get_option('qrcappratingcss'); ?>" /></td>
	        </tr>
	    </table>
		<p class="submit"><input type="submit" class="button-primary" value="Save Changes" /></p>
	</form>

	<h3>How to use the plugin</h3>
	This plugin automatically creates clickable QR codes that point to apps in the Android Market.<br /><br />
	
	To use it, simply surround any link to an app in the Android Web Market or AppBrain.com with <em>[qr] [/qr]</em>.<br />
	Put the link in the page or post where you want it to appear. That's it! Example:<br />
	<strong>[qr]https://market.android.com/details?id=com.halfbrick.fruitninja[/qr]</strong><br /><br />
	
	For more information, please visit <a href="http://www.techcredo.com/random-tech/android-market-qr-codes-wordpress-plugin" target="_blank">the Android Market QR Code Plugin page</a> at TechCredo.com<br />
	If you use the plugin often, please consider adding a link back to <a href="http://www.techcredo.com/" target="_blank">TechCredo.com</a> on your site. Thanks!<br /><br />
	Please report comments, suggestions and any possible issues at the plugin page above.<br />
	</div>

	<?php } } ?>