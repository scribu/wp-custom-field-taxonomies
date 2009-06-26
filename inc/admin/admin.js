jQuery(document).ready(function() 
{
	function bind_suggest($el, field)
	{
		$el.suggest('admin-ajax.php?action=meta-search&field=' + field, {delay: 200, minchars: 2});
	}

	// Taxonomy suggest
	bind_suggest(jQuery('#taxonomies [name^=key]'), 'key');

	// Search & Replace suggest
	bind_suggest(jQuery('[name=key_search]'), 'key');
	bind_suggest(jQuery('[name=value_search]'), 'value');
	bind_suggest(jQuery('[name=default_value]'), 'value');

	$table = jQuery('#taxonomies');
	$row = $table.find('tbody tr:first').clone()
		.find(':text').val('').end();

	$table.click(function(ev)
	{
		$el = jQuery(ev.target);

		// Add row button
		if ( $el.is('#add') )
		{
			$new_row = $row.clone();

			$new_row.insertBefore($el.parents('tr'));

			bind_suggest($new_row.find('input:first'), 'key');
		}

		// Delete button
		else if ( $el.is('a') )
		{
			$el.parents('tr').fadeOut('normal', function() {
				jQuery(this).remove();
			});
			
			return false;
		}
	});
});
