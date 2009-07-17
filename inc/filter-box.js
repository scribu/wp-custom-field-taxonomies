(function($) {
	var filter_box = function(el) {
		var self = this;

		self.box = $(el);
		self.box.submit(function(ev) { self.submit(ev); });

		self.select = self.box.find('select');
		self.select.change(function(ev) { self.change(ev); });
	}

	filter_box.prototype = {
	  change : function(ev) {
	  	var self = this;

		var $selected = self.select.find('option:selected');

		if ( ! $selected.val() )
			return;

		// Remove option
		$selected.hide();
		self.select.val('');

		// Add row
		self.box.find('.meta-filters').append(
			'<tr>'
			+ '<td>' + $selected.text() + '</td>'
			+ '<td><input type="text" name="' + $selected.val() + '"></td>'
			+ '<td><a class="filter-remove" href="#">x</a></td>'
			+ '</tr>'
		);

		// Autosuggest
		self.box.find('[name=' + $selected.val() + ']')
			.suggest(window.cft_suggest_url + $selected.val(), {
				resultsClass : 'cft_results',
				selectClass : 'cft_over',
				matchClass : 'cft_match'
			});

		// Remove button
		self.box.find('.filter-remove').click(function() {
			var $row = $(this).parents('tr').remove();

			// Restore option
			var key = $row.find('input').attr('name');
			self.select.find('[value=' + key + ']').show();

			return false;
		});
	  },
	
	  submit : function(ev) {
		this.box.find(':submit').remove();
		this.select.remove();
	  }
	};

	$('.meta-filter-box').each(function() { new filter_box(this); });
})(jQuery);
