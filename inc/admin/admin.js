jQuery(function($) {
	function bind_suggest(element, field) 
	{
		$(element).suggest('admin-ajax.php?action=meta-search&field=' + field, {delay: 200, minchars: 2});
	}

	function tax_suggest()
	{
		$('#cf-taxonomies [name^=key]').each(function() {
			bind_suggest(this, 'key');
		});
	}

	function sr_suggest()
	{
		bind_suggest('[name=key_search]', 'key');
		bind_suggest('[name=value_search]', 'value');
		bind_suggest('[name=default_value]', 'value');
	}

	function tax_delete()
	{
		$('.delete').click(function() {
			$(this).parents('tr').fadeOut('normal', function() {
				$(this).remove();
			});
		});
	}

$(document).ready(function() {
	tax_suggest();
	tax_delete();
	sr_suggest();

	// TODO: use cloning instead
	$('#add').click(function() {
		row = 
		'<tr>' + 
		'<td><label for="key"><input class="normal-text" name="key[]" value="" type="text" /></label></td>' +
		'<td><label for="title"><input class="normal-text" name="title[]" value="" type="text" /></label></td>' +
		'<td><a class="delete" href="#">Delete</a></td>' +
		'</tr>';

		$(this).parents('tr').before(row);
		tax_delete();	// reinitialize
		tax_suggest();	// reinitialize
	});
});
});
