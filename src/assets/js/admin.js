jQuery(document).ready(function($) {
	'use strict';

	var cycles_input = document.querySelector('#cycle_count'),
		$cycles_input = $(cycles_input);
	var cycles_field = document.querySelector('.cycle_count_field'),
		$cycles_field = $(cycles_field);

	/**
	 * Subscription coupon actions.
	 * @type {{init: function, type_options: function, move_field: function}}
	 */
	var vindi_meta_boxes_coupon_actions = {

		/**
		 * Initialize variation actions.
		 */
		init: function() {
			if (cycles_field) {
				$(document.getElementById('discount_type')).on('change', this.type_options).change();
				this.move_field();
			}
		},

		/**
		 * Show/hide fields by coupon type options.
		 */
		type_options: function() {
			var discount_type = $(this).val();

			switch (discount_type) {
				case 'fixed_cart':
					$cycles_field.hide();
					$cycles_input.val('1');
					break;
        default:
          $cycles_field.show();
          $cycles_input.val('0');
          break;
			}
		},

		/**
		 * Move the renewal form field in the DOM to a better location.
		 */
		move_field: function() {
			var parent = document.getElementById('general_coupon_data'),
				shipping = parent.querySelector('.free_shipping_field');

			parent.insertBefore(cycles_field, shipping);
		}
	};

	vindi_meta_boxes_coupon_actions.init();
});