jQuery(function($) {
	init_delete = function() {
		$('.delete').each(function() {
			$(this).click(function() {
				$(this).parents('tr').fadeOut('normal', function() {
					$(this).remove();
				});
			});
		});
	}

	init_suggest = function() {
		$('#cf-taxonomies [name^=key]').each(function() {
			$(this).suggest('admin-ajax.php?action=meta-search&field=key', {delay: 200, minchars: 2});
		});
	}

$(document).ready(function() {
	init_delete();
	init_suggest();

	$('#add').click(function() {
		row = 
		'<tr>' + 
		'<td><label for="key"><input class="widefat" name="key[]" value="" type="text" /></label></td>' +
		'<td><label for="title"><input class="widefat" name="title[]" value="" type="text" /></label></td>' +
		'<td><a class="delete" href="#">Delete</a></td>' +
		'</tr>';

		$(this).parents('tr').before(row);
		init_delete();	// reinitialize
		init_suggest();	// reinitialize
	});

	// Search & replace suggest
	$('[name=value_search]').suggest('admin-ajax.php?action=meta-search&field=value', {delay: 200, minchars: 2});
	$('[name=key_search]').suggest('admin-ajax.php?action=meta-search&field=key', {delay: 200, minchars: 2});
});
});
