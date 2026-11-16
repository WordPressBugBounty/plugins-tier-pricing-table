/**
 * The quote request screen: totals follow the quantities and unit prices while they are typed, and Convert to
 * Order asks before it creates an order.
 */
(function () {
	'use strict';

	var settings = window.tptQuoteAdmin || {};
	var currency = settings.currency || {};

	function formatPrice(amount) {
		var decimals = parseInt(currency.decimals, 10);

		decimals = isNaN(decimals) ? 2 : decimals;

		var parts = Math.abs(amount).toFixed(decimals).split('.');
		var whole = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, currency.thousandSeparator || '');
		var number = whole + (parts[1] ? (currency.decimalSeparator || '.') + parts[1] : '');

		// WooCommerce's own price format: %1$s is the symbol, %2$s the number
		return (amount < 0 ? '-' : '') + String(currency.format || '%1$s%2$s').replace('%1$s', currency.symbol || '').replace('%2$s', number);
	}

	function update(table) {
		var sum = 0;
		var complete = true;

		table.querySelectorAll('[data-tpt-quote-line]').forEach(function (row) {
			var quantity = parseFloat((row.querySelector('[data-tpt-quote-qty]') || {}).value);
			var priceField = row.querySelector('[data-tpt-quote-price]');
			var price = priceField && '' !== priceField.value.trim() ? parseFloat(priceField.value) : NaN;
			var cell = row.querySelector('[data-tpt-quote-line-total]');
			var known = !isNaN(quantity) && !isNaN(price) && quantity > 0 && price >= 0;

			if (cell) {
				cell.textContent = known ? formatPrice(quantity * price) : '–';
			}

			if (known) {
				sum += quantity * price;
			} else {
				complete = false;
			}
		});

		var total = table.querySelector('[data-tpt-quote-total]');

		if (total) {
			// a total only when every line has a price
			total.textContent = complete ? formatPrice(sum) : '–';
		}
	}

	document.querySelectorAll('[data-tpt-quote-items]').forEach(function (table) {
		table.addEventListener('input', function () {
			update(table);
		});
	});

	document.addEventListener('click', function (event) {
		var button = event.target.closest ? event.target.closest('[name="tpt_convert_to_order"]') : null;

		if (button && settings.confirmConvert && !window.confirm(settings.confirmConvert)) {
			event.preventDefault();
		}
	});
})();
