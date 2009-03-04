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

	bind_suggest = function(element, field) {
		$(element).suggest('admin-ajax.php?action=meta-search&field=' + field, {delay: 200, minchars: 2});
	}

	init_suggest = function() {
		$('#cf-taxonomies [name^=key]').each(function() {
			bind_suggest(this, 'key');
		});
	}

	sr_suggest = function() {
		bind_suggest('[name=key_search]', 'key');
		bind_suggest('[name=value_search]', 'value');
	}

$(document).ready(function() {
	init_delete();
	init_suggest();
	sr_suggest();

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
});
});
