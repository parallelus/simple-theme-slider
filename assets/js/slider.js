jQuery(document).ready(function($) {
    
   	var file_frame;
	
	$('.image-sortable').on('click', '.add-st-slide', function(e){
		e.preventDefault();
		e.stopPropagation();

		if ( file_frame ) {
			file_frame.open();
			return;
		}

		file_frame = wp.media.frames.file_frame = wp.media({
			multiple: false
		});

		file_frame.on( 'select', function() {
			attachment = file_frame.state().get('selection').first().toJSON();

			var images_len = $('.image-last-number').val();
			var new_image = $('.image-template').clone(true);		
			new_image = setImageAttrs(new_image, images_len);
			// $('.image-template').before(new_image);
			$('#end-of-slides').before(new_image);
			$('.image-last-number').val(parseInt(images_len) + 1);
			$('input.hidden-source[id="hidden_source['+images_len+']"]').val(attachment.url);
			$('img.image-source[id="image_source['+images_len+']"]').attr("src", attachment.url);
		});

		file_frame.open();
	});

	$('.image-sortable').on( 'click', '.remove-image', function(e) {
		e.preventDefault();
		$(this).closest('table.image-element').remove();
	});

	if($( ".image-sortable" ).length) {
	   $( ".image-sortable" ).sortable();

	   var imageList = $('.image-sortable tbody');
	   var ranks = [];
	   imageList.sortable({
	        items: 'table.image-element',
	        stop: function(event, ui) {
	                var list = $('table.image-element');
	                var key;
	                list.each(function(i, el){
	                	key = $(this).data('id');
	                	$('input.hidden-rank[id="hidden_rank['+key+']"]').val(i);
	                	//$(this).attr('data-id', i);
	            	});
	        }
	    });
	}

	function setImageAttrs(el, len) {
		var name;
		el.find('.image-source').attr({ id   : 'image_source['+len+']',
		 		  					    name : 'image_source['+len+']'});
        var fields = el.find('.image-field');
        fields.each(function() {
        	name = $(this).attr('name');
        	name = name.replace('newimage','image')+'['+len+']';
			$(this).attr({ name : name});
			$(this).removeClass('image-field');
        });

		el.find('.hidden-source').attr({ id   : 'hidden_source['+len+']',
				  					     name : 'hidden_source['+len+']'});
		el.removeClass('image-template');
		el.addClass('image-details');
		el.show();
		return el;
	}

});