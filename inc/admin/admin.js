jQuery(document).ready(function() 
{
	function bind_suggest(element, field) 
	{
		jQuery(element).suggest('admin-ajax.php?action=meta-search&field=' + field, {delay: 200, minchars: 2});
	}

	// Taxonomy suggest
	jQuery('#cf-taxonomies [name^=key]').each(function() {
		bind_suggest(this, 'key');
	});

	// Search & Replace suggest
	bind_suggest('[name=key_search]', 'key');
	bind_suggest('[name=value_search]', 'value');
	bind_suggest('[name=default_value]', 'value');

	// Delete button
	jQuery('.delete').click(function() {
		jQuery(this).parents('tr').fadeOut('normal', function() {
			jQuery(this).remove();
		});
	});

	// Add row button
	row = jQuery('#add').parents('tbody').find('tr:first');

	jQuery('#add').click(function() {
		row.clone(true)
			.find(':text').val('').end()
			.insertBefore(jQuery(this).parents('tr'));
	});
});
