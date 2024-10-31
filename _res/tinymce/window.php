<?php
// gotta bootstrap this page back into the admin
require_once(dirname(dirname(dirname(__FILE__))) . '/pricegarcon-config.php');

if(!current_user_can('edit_pages') && !current_user_can('edit_posts')){ 
	wp_die('You do not have permission to be here');
}

global $wpdb;

$s_siteUrl = get_option('siteurl');

/* TODO:
 * This pop-up form submits to javascript for parsing. I would consider having the form
 * submit to a php file to reliably scrub the data and validate it before building the
 * [pricegarcon] Shortcode and injecting it into the Post or Page.
 */

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>PriceWaiter&reg; Generator</title>
	
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	<script language="javascript" type="text/javascript" src="<?php echo $s_siteUrl; ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo $s_siteUrl; ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo $s_siteUrl; ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo $s_siteUrl; ?>/wp-content/plugins/pricegarcon/_res/tinymce/shortcode.js"></script>
	
	<style type="text/css">
	body{ margin:0; font-family:Calibri,"Myriad Pro Web","Myriad Pro",Myriad,"Helvetica Neue",Helvetica,Arial,"sans serif"; background:#f1f1f1; }
	hgroup { display:block; height:52px; background:url('pricewaiter.png') center center no-repeat; }
	h1, h2 { display:none; }
	td { vertical-align:top; }
	label { cursor:pointer; }
	#rowSku label { font-weight:bold; }
	#rowName label { font-weight:bold; }
	</style>
</head>
<body>
	<hgroup>
		<h1>PriceWaiter&reg;</h1>
		<h2>Product Info</h2>
	</hgroup>
	<form name="PriceGarcon" action="#">
		<table border="0" cellpadding="3" cellspacing="0">
			<tbody>
				<tr id="rowSku">
					<td><label for="pwSku">Product SKU</label></td>
					<td><input type="text" id="pwSku" size="35"/></td>
				</tr>
				<tr id="rowName">
					<td><label for="pwName">Product Name</label></td>
					<td><input type="text" id="pwName" size="35"/></td>
				</tr>
				<tr id="rowDesc">
					<td><label for="pwDesc">Description</label></td>
					<td><textarea id="pwDesc" rows="2" cols="34"></textarea></td>
				</tr>
				<tr id="rowPrice">
					<td><label for="pwPrice">Price ($)</label></td>
					<td><input type="text" id="pwPrice" size="10"/></td>
				</tr>
				<tr id="rowPic">
					<td><label for="pwImage">Picture URL</label></td>
					<td><input type="text" id="pwImage" size="35"></td>
				</tr>
				<tr id="rowSubmit">
					<td>&nbsp;</td>
					<td>
						<input type="button" id="cancel" name="cancel" value="Cancel" onclick="tinyMCEPopup.close();" />
						<input type="submit" id="insert" name="insert" value="Insert" onclick="insertPriceGarconShortcode();" />
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</body>
</html>