jQuery(function($) {
	bind_suggest = function(element, field) {
		$(element).suggest('admin-ajax.php?action=meta-search&field=' + field, {delay: 200, minchars: 2});
	}

	tax_suggest = function() {
		$('#cf-taxonomies [name^=key]').each(function() {
			bind_suggest(this, 'key');
		});
	}

	sr_suggest = function() {
		bind_suggest('[name=key_search]', 'key');
		bind_suggest('[name=value_search]', 'value');
		bind_suggest('[name=default_value]', 'value');
	}

	tax_delete = function() {
		$('.delete').each(function() {
			$(this).click(function() {
				$(this).parents('tr').fadeOut('normal', function() {
					$(this).remove();
				});
			});
		});
	}

$(document).ready(function() {
	tax_suggest();
	tax_delete();
	sr_suggest();

	$('#add').click(function() {
		row = 
		'<tr>' + 
		'<td><label for="key"><input class="widefat" name="key[]" value="" type="text" /></label></td>' +
		'<td><label for="title"><input class="widefat" name="title[]" value="" type="text" /></label></td>' +
		'<td><a class="delete" href="#">Delete</a></td>' +
		'</tr>';

		$(this).parents('tr').before(row);
		tax_delete();	// reinitialize
		tax_suggest();	// reinitialize
	});
});
});
