jQuery(document).ready(function() 
{
	function bind_suggest(el, field)
	{
		jQuery(el).suggest('admin-ajax.php?action=meta-search&field=' + field, {delay: 200, minchars: 2});
	}

	// Taxonomy suggest
	jQuery('#taxonomies [name^=key]').each(function() {
		bind_suggest(this, 'key');
	});

	// Search & Replace suggest
	bind_suggest('[name=key_search]', 'key');
	bind_suggest('[name=value_search]', 'value');
	bind_suggest('[name=default_value]', 'value');

	// Delete button
	jQuery('#taxonomies').click(function(ev) {
		$el = jQuery(ev.target);

		if ( $el.is('a') )
			// Delete row
			$el.parents('tr').fadeOut('normal', function() {
				jQuery(this).remove();
			});
	});

	// Add row button
	button = jQuery('#add');
	row = button.parents('tbody').find('tr:first').clone(true)
			.find(':text').val('').end();

	button.click(function() {
		row.clone(true).insertBefore(jQuery(this).parents('tr'));
	});
});
