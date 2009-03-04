jQuery(function($) {
	$('.meta-filter-box select').change(function() {
		var select = $(this);
		var selected = $(this).find('option:selected');

		if ( ! selected.val() )
			return;

		// Remove option
		selected.css('display', 'none');
		select.val('');

		// Add row
		var row = '<tr>'
			+ '<td>' + selected.text() + '</td>'
			+ '<td><input type="text" name="' + selected.val() + '"></td>'
			+ '<td><a class="filter-remove" href="#">x</a></td>'
			+ '</tr>';
		$(this).parents('.meta-filter-box').find('.meta-filters').append(row);

		// Bind autosuggest
		$(this).parents('.meta-filter-box').find('[name=' + selected.val() + ']')
			.suggest(window.cft_suggest_url + selected.val(), {
				resultsClass : 'cft_results',
				selectClass : 'cft_over',
				matchClass : 'cft_match'
			});

		// Handle filter remove button
		$(this).parents('.meta-filter-box').find('.filter-remove').click(function() {
			// Remove row
			row = $(this).parents('tr').remove();

			// Restore option
			var key = row.find('input').attr('name');
			select.find('[value=' + key + ']').css('display', 'block');

			return false;
		});
	});

	// Remove &action=Go
	$('.meta-filter-box').submit(function() {
		$(this).find(':submit').remove();
	});
});
