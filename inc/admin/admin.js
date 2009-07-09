jQuery(document).ready(function($) {

	// Settings logic
	(function() {
		var $by_order = $('[name=rank_by_order]').parents('p');

		var $and = $('[name=allow_and]')
			.click(function() {
				$relevance.attr('checked', false);
				$by_order.hide();
			});

		var $relevance = $('[name=relevance]')
			.click(function() {
				$and.attr('checked', false); 
				$by_order.toggle();
			});

		if ( ! $relevance.is(':checked') )
			$by_order.hide();
	})();

	var bind_suggest = function ($el, field) {
		$el.suggest('admin-ajax.php?action=meta-search&field=' + field, {delay: 200, minchars: 2});
	}

	// Taxonomy suggest
	bind_suggest($('#taxonomies [name^=key]'), 'key');

	// Search & Replace suggest
	bind_suggest($('[name=key_search]'), 'key');
	bind_suggest($('[name=value_search]'), 'value');
	bind_suggest($('[name=default_value]'), 'value');

	//Table
	(function() {
		var $table = $('#taxonomies table');
		var $row = $table.find('tbody tr:first').clone()
			.find(':text').val('').end();

		$table.click(function(ev) {
			var $el = $(ev.target);

			// Add row button
			if ( $el.is('#add') ) {
				var $new_row = $row.clone()
					.insertBefore($el.parents('tr'));

				bind_suggest($new_row.find('input:first'), 'key');
			}

			// Delete button
			else if ( $el.is('a') ) {
				$el.parents('tr').fadeOut('normal', function() {
					$(this).remove();
				});
			}

			return false;
		});
	})();
});

