// Docu: http://wp.smashingmagazine.com/2012/05/01/wordpress-shortcodes-complete-guide/

(function() {
	tinymce.create('tinymce.plugins.PriceGarcon', {
		init : function(ed, url) {
			// register command
			ed.addCommand('mcePriceGarcon', function() {
				ed.windowManager.open({
					file : url + '/window.php',
					width : 340 + ed.getLang('pricegarcon.delta_width', 0),
					height : 240 + ed.getLang('pricegarcon.delta_height', 0),
					inline : 1
				}, {
					plugin_url : url
				});
			});
			
			// register button, hook to command
			ed.addButton('pricegarcon', {
				title : 'PriceWaiter(R) button generator',
				cmd : 'mcePriceGarcon',
				image : url+'/button.png',

		});
	},

	createControl : function(n, cm) {
		return null;
	},

	getInfo : function() {
		return {
			longname : "PriceGarcon",
			author : 'Jeff Bertrand',
			authorurl : 'http://jeffbertrand.net',
			infourl : 'http://www.pricewaiter.com',
			version : "0.9"
			};
		}
	});
	tinymce.PluginManager.add('pricegarcon', tinymce.plugins.PriceGarcon);
})();