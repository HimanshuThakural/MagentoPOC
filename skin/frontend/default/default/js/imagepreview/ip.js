jQuery(document).ready(function() {
	jQuery('#imagepreview_image').hide();
	jQuery('#imagepreview_content').attr('style', 'width: 200px; height: 200px;'); 
	jQuery('#imagepreview_navigator').hide();
	jQuery('#imagepreview_content_close').hide();
	
	jQuery('#imagepreview_background,#imagepreview_content_close').click(function() {
		jQuery('#imagepreview_container').fadeOut('slow', function() {
			jQuery('#imagepreview_content').attr('style', 'width: 200px; height: 200px;');
			jQuery('#imagepreview_image').hide();
			jQuery('#imagepreview_navigator').hide();
			jQuery('#imagepreview_content_close').hide();
			jQuery('#imagepreview_image').attr('src', '');
		});
	});
		
	jQuery('div.product-img-box div.more-views ul li a').each(function (imageNum) {
		jQuery(this).click(function() {
			jQuery("#imagepreview_container").fadeIn("slow", function() {
				showPI(imageNum);
			});
		});
	});
	jQuery('#image').click(function() {
		jQuery("#imagepreview_container").fadeIn("slow", function() {
			showPI(0);
		});
	});
	
	var image = jQuery('#imagepreview_image');
	image.load(function() {
		var ic = jQuery('#imagepreview_content');
		var cw = ic.width();
		var ch = ic.height();
		if (cw != (image.width() + 50) || ch != (image.height() + 90))  {
			ic.animate({width: (image.width() + 50)}, 500, function () {
				ic.animate({height: (image.height() + 90)}, 500, function (){
					image.fadeIn(500, function() {
						jQuery('#imagepreview_content_close').fadeIn(500, function(){});
						jQuery('#imagepreview_navigator').fadeIn(500, function(){});
					});
				});
			});
		} else {
			image.fadeIn(500, function() {});
		}
	});

});
	
function showPI(imageNum){
	var imagepreview_src = new Array();
	jQuery('div.product-img-box div.more-views ul li a img').each(function (i){
		imagepreview_src[i] = jQuery(this).attr('zoom');
	});
	if (imageNum == -1) 
   	 	imagepreview_currentimage = imagepreview_src.length - 1;
	else  
		imagepreview_currentimage = imageNum % imagepreview_src.length;
	jQuery('#imagepreview_imagenums').html('' + (imagepreview_currentimage + 1) + ' / ' + imagepreview_src.length);
	jQuery('#imagepreview_image').attr({ 'src' : imagepreview_src[imagepreview_currentimage] });
}