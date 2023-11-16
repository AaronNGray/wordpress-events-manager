/* Selectize deselect function */
Selectize.define('click2deselect', function(options) {
	var self = this;
	var setup = self.setup;
	this.setup = function() {
		setup.apply(self, arguments);
		// add additional handler
		self.$dropdown.each( function(){
			this.addEventListener('click', function(e) {
				if( this.matches('.selected[data-selectable]') ) {
					let value = this.getAttribute('data-value');
					self.removeItem(value);
					self.refreshItems();
					self.refreshOptions();
				}
				return false;
			});
		});
		self.on('item_remove', function (value) {
			self.getOption(value).removeClass('selected')
		});
	}
});