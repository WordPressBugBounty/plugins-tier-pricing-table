/**
 * One-click install and activation of the other U2Code plugins. Install goes through WordPress
 * core's own AJAX action, activation through the plugin's; the page stays where it is.
 */
(function ($) {
	'use strict';

	var cfg = window.tptPluginInstaller || {}, i18n = cfg.i18n || {};

	function busy(button, text) {
		button.prop('disabled', true).addClass('updating-message').text(text);
	}

	function fail(button, message) {
		var error = button.siblings('.tpt-install__error');

		if (!error.length) {
			error = $('<span class="tpt-install__error" role="alert"></span>').insertAfter(button);
		}

		error.empty().text((message || i18n.failed) + ' ');

		if (button.data('fallback')) {
			error.append($('<a></a>').attr('href', button.data('fallback')).text(i18n.fallback));
		}

		button.prop('disabled', false).removeClass('updating-message').text(i18n.retry);
	}

	function done(button) {
		if (button.data('reload')) {
			window.location.reload();
			return;
		}

		button.siblings('.tpt-install__error').remove();
		button.replaceWith($('<span class="tpt-install__state"></span>').text(i18n.active));
	}

	function activate(button) {
		busy(button, i18n.activating);

		$.post(cfg.ajaxUrl, { action: 'tpt_activate_plugin', slug: button.data('slug'), _ajax_nonce: cfg.nonce })
			.done(function (response) {
				if (response && response.success) {
					done(button);
				} else {
					fail(button, response && response.data && response.data.message);
				}
			})
			.fail(function (xhr) {
				var data = xhr.responseJSON && xhr.responseJSON.data;
				fail(button, data && data.message);
			});
	}

	function install(button) {
		busy(button, i18n.installing);

		$.post(cfg.ajaxUrl, { action: 'install-plugin', slug: button.data('slug'), _ajax_nonce: cfg.updatesNonce })
			.done(function (response) {
				if (response && response.success) {
					button.data('action', 'activate');
					activate(button);
				} else {
					fail(button, response && response.data && response.data.errorMessage);
				}
			})
			.fail(function () {
				fail(button);
			});
	}

	$(document).on('click', '.tpt-install[data-action]', function (event) {
		var button = $(this);

		event.preventDefault();

		if (button.prop('disabled')) {
			return;
		}

		if ('install' === button.data('action')) {
			install(button);
		} else {
			activate(button);
		}
	});
})(jQuery);
