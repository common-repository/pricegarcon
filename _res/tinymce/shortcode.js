/* func insertPriceGarconShortcode()
 * Function parses the completed form fields into PriceGarcon Shortcode. Returns 
 * parsed Shortcode to the cursor's position in the WYSIWYG editor.
 */
function insertPriceGarconShortcode(){	
	var s_shortcode = '';

	var s_sku = document.getElementById('pwSku').value;
	var s_name = document.getElementById('pwName').value;
	var s_desc = document.getElementById('pwDesc').value;
	var f_price = document.getElementById('pwPrice').value;
	var s_image = document.getElementById('pwImage').value;

	if(s_sku != '' && s_name != ''){
		s_shortcode = '[pricegarcon' + ' sku="' + s_sku + '" name="' + s_name + '"';
		
		if(s_desc != ''){
			s_shortcode += ' description="' + s_desc + '"';
		}
		
		if(f_price != ''){
			s_shortcode += ' price="' + f_price + '"';
		}
		
		if(s_image != ''){
			s_shortcode += ' image="' + s_image + '"';
		}
		
		s_shortcode += ']';
	} else {
		tinyMCEPopup.close();
	}
	
	if(window.tinyMCE) {
		window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, s_shortcode);
		tinyMCEPopup.editor.execCommand('mceRepaint');
		tinyMCEPopup.close();
	}
	return;
}
