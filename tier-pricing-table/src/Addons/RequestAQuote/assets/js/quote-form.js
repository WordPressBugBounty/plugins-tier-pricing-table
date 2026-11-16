jQuery(document).ready(function ($) {

	if (window.tptQuoteFormConfig === undefined || !window.tptQuoteFormConfig.forms) {
		return;
	}

	/**
	 * Represents a single Request a Quote modal form.
	 * 
	 * @param {jQuery} $modal 
	 * @param {int} productId 
	 * @param {object} config 
	 */
	const RequestQuoteForm = function ($modal, productId, config) {
		this.$modal = $modal;
		this.productId = productId;
		this.config = config;
		this.state = {};
		this.previousActiveElement = null;

		this.init = function () {
			this.upgradeMarkup();
			this.bindEvents();
		};

		/**
		 * The popup is a dialog for assistive technology. The plugin's template says so itself; a copy of an
		 * older template in a theme gets the same here.
		 */
		this.upgradeMarkup = function () {
			const i18n = window.tptQuoteFormConfig.i18n || {};
			const $content = this.$modal.find('.tpt-quote-modal-content');
			const $title = this.$modal.find('.tpt-quote-modal-title');
			const $close = this.$modal.find('.tpt-quote-modal-close');

			if (!$content.attr('role')) {
				if (!$title.attr('id')) {
					$title.attr('id', 'tpt-quote-modal-title-' + this.productId);
				}

				$content.attr({ role: 'dialog', 'aria-modal': 'true', 'aria-labelledby': $title.attr('id'), tabindex: '-1' });
			}

			if ($close.length && !$close.is('button')) {
				$close.attr({ role: 'button', tabindex: '0', 'aria-label': i18n.close || 'Close' });
				$close.on('keydown', (e) => {
					if (e.key === 'Enter' || e.key === ' ') {
						e.preventDefault();
						this.close();
					}
				});
			}

			this.$modal.find('.tpt-quote-form-message').attr('role', 'alert');
		};

		this.getFocusable = function () {
			return this.$modal.find('a[href], button, input, select, textarea, [tabindex]').filter(function () {
				const $el = $(this);

				return $el.is(':visible') && !$el.is(':disabled') && $el.attr('tabindex') !== '-1' && $el.attr('type') !== 'hidden';
			});
		};

		this.handleKeyDown = (e) => {
			if (!this.isOpen()) {
				return;
			}

			if (e.key === 'Escape') {
				this.close();

				return;
			}

			// Tab stays inside the popup
			if (e.key === 'Tab') {
				const $focusable = this.getFocusable();

				if (!$focusable.length) {
					e.preventDefault();

					return;
				}

				const first = $focusable[0];
				const last = $focusable[$focusable.length - 1];
				const inside = this.$modal[0].contains(document.activeElement);

				if (e.shiftKey && (document.activeElement === first || !inside)) {
					e.preventDefault();
					last.focus();
				} else if (!e.shiftKey && (document.activeElement === last || !inside)) {
					e.preventDefault();
					first.focus();
				}
			}
		};

		this.bindEvents = function () {
			this.$modal.on('click', '.tpt-quote-modal-close', this.close.bind(this));
			
			let isMouseDownOnOverlay = false;

			this.$modal.on('mousedown', (e) => {
				isMouseDownOnOverlay = (e.target === this.$modal[0]);
			});

			this.$modal.on('mouseup', (e) => {
				if (isMouseDownOnOverlay && e.target === this.$modal[0]) {
					this.close();
				}
				isMouseDownOnOverlay = false;
			});

			this.$modal.find('form').on('submit', this.submit.bind(this));
		};

		this.open = function () {
			this.previousActiveElement = document.activeElement;
			this.renderState();
			this.$modal.show();
			
			// Focus first input field for accessibility
			setTimeout(() => {
				this.$modal.find('input:visible, textarea:visible').not('[type="hidden"], [tabindex="-1"], .tpt-quote-modal-close').first().focus();
			}, 50);
			
			$(document).on('keydown', this.handleKeyDown);
		};

		this.close = function () {
			this.$modal.hide();
			$(document).off('keydown', this.handleKeyDown);

			// after a sent request the popup starts fresh next time
			if (this.$modal.find('.tpt-quote-success').length) {
				this.$modal.find('.tpt-quote-success').remove();
				this.$modal.find('form.tpt-quote-form-element').show()[0].reset();
			}

			// Restore focus
			if (this.previousActiveElement) {
				this.previousActiveElement.focus();
			}
		};

		/**
		 * The request is sent: say so in the popup, with its number and where the copy went, instead of
		 * reloading the page.
		 */
		this.showSuccess = function (response, email) {
			const i18n = window.tptQuoteFormConfig.i18n || {};
			const $form = this.$modal.find('form.tpt-quote-form-element');
			const $panel = $('<div class="tpt-quote-success" tabindex="-1" role="status"></div>');

			$panel.append($('<p class="tpt-quote-success__title"></p>').text(i18n.successTitle || 'Your request is sent'));

			if (this.config.success_message) {
				// the store's own message: text with the safe markup the form builder allows
				$panel.append($('<div class="tpt-quote-success__message"></div>').html(this.config.success_message));
			}

			if (response && response.post_id) {
				$panel.append($('<p></p>').text((i18n.reference || 'Your request number is %s.').replace('%s', '#' + response.post_id)));
			}

			if (email && this.config.enable_customer_email !== false) {
				$panel.append($('<p></p>').text((i18n.copySent || 'We e-mailed a copy to %s.').replace('%s', email)));
			}

			const $button = $('<button type="button" class="button wp-element-button tpt-quote-success__close"></button>').text(i18n.close || 'Close');

			$button.on('click', () => this.close());
			$panel.append($button);

			$form.hide().after($panel);
			$panel.trigger('focus');
		};

		this.updateState = function (newState) {
			this.state = { ...this.state, ...newState };
			if (this.isOpen()) {
				this.renderState();
			}
		};

		this.isOpen = function () {
			return this.$modal.is(':visible');
		};

		this.renderState = function () {
			const $fieldsContainer = this.$modal.find('.tpt-quote-fields-container');

			if (this.state.quantity) {
				const $qtyInputs = $fieldsContainer.find('input.tpt-quote-sync-quantity');
				if ($qtyInputs.length) {
					$qtyInputs.val(this.state.quantity);
				}
			}

			const currentId = (this.state.variationId && this.state.variationId > 0) ? this.state.variationId : this.productId;
			this.$modal.find('.tpt-quote-product-id').val(currentId);

			if (this.state.priceHtml) {
				this.$modal.find('.tpt-quote-product-info .price').html(this.state.priceHtml);
			}

			// a variation's options under the product name ("Color: Blue, Size: Large")
			const summary = (this.state.variationId && this.state.variationId > 0) ? this.getVariationSummary() : '';

			this.$modal.find('.tpt-quote-variation-attributes').text(summary).toggle(summary !== '');
		};

		/**
		 * The options chosen in the product's own form: the classic form's selects, or the hidden inputs of the
		 * block form, whose rows carry the attribute name and the option labels in data-wp-context.
		 */
		this.getVariationSummary = function () {
			let $form = $('form.variations_form[data-product_id="' + this.productId + '"]').first();

			if (!$form.length) {
				$form = $('form.variations_form, form.wc-block-add-to-cart-with-options').first();
			}

			const parts = [];

			$form.find('select[name^="attribute_"], input[type="hidden"][name^="attribute_"]').each(function () {
				const $input = $(this);
				const value = String($input.val() || '');

				if (!value) {
					return;
				}

				let name = '';
				let label = '';

				if ($input.is('select')) {
					const id = $input.attr('id');

					name = id ? $form.find('label[for="' + id + '"]').first().text() : '';
					name = name || $input.closest('tr').find('th').first().text();
					label = $input.find('option:selected').text();
				} else {
					let context = {};

					try {
						context = JSON.parse($input.parent().attr('data-wp-context') || '{}') || {};
					} catch (error) {
						context = {};
					}

					const option = (context.variationAttributeOptions || []).find((item) => String(item.value) === value);

					name = context.name || '';
					label = option ? option.label : '';
				}

				name = $.trim(name).replace(/:$/, '');
				label = $.trim(label) || value;

				parts.push(name ? name + ': ' + label : label);
			});

			return parts.join(', ');
		};

		// only http and https addresses are followed after a request is sent
		this.isWebAddress = function (url) {
			if (!url) {
				return false;
			}

			try {
				const target = new URL(url, window.location.href);

				return target.protocol === 'http:' || target.protocol === 'https:';
			} catch (error) {
				return false;
			}
		};

		this.showError = function (message) {
			// one line per field the server rejected; the container is a live region, so it is announced
			const $message = $('<div class="tpt-quote-error"></div>').text(message);
			this.$modal.find('.tpt-quote-form-message').empty().append($message);
		};

		this.submit = function (e) {
			e.preventDefault();
			const $form = $(e.currentTarget);
			const config = window.tptQuoteFormConfig;
			const i18n = config.i18n || {};
			const $submitBtn = $form.find('button[type="submit"]');

			if ($submitBtn.prop('disabled')) {
				return;
			}

			const originalBtnText = $submitBtn.text();

			const setBusy = (busy) => {
				$submitBtn.prop('disabled', busy).text(busy ? (i18n.defaultSubmittingText || 'Submitting...') : originalBtnText);
			};

			const submitData = (token, nonceRefreshed) => {
				let formData = new FormData($form[0]);
				if (token) {
					formData.append('g-recaptcha-response', token);
				}

				$.ajax({
					url: config.restUrl,
					method: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					beforeSend: (xhr) => {
						// only logged-in customers have a nonce; guests send none
						if (config.nonce) {
							xhr.setRequestHeader('X-WP-Nonce', config.nonce);
						}
					},
					success: (response) => {
						const successAction = this.config.success_action || 'message';
						if (successAction === 'redirect' && this.isWebAddress(this.config.success_redirect_url)) {
							window.location.href = this.config.success_redirect_url;
						} else {
							const email = $.trim($form.find('input[type="email"]').first().val() || '');

							setBusy(false);
							this.showSuccess(response, email);
						}
					},
					error: (xhr) => {
						// The page is older than its nonce (a tab left open for a day): get a fresh one and send again
						if (!nonceRefreshed && xhr.status === 403 && xhr.responseJSON?.code === 'rest_cookie_invalid_nonce' && config.ajaxUrl) {
							$.post(config.ajaxUrl, { action: config.nonceAction || 'tpt_quote_nonce' })
								.done((response) => {
									config.nonce = response?.data?.nonce || '';
									submitData(token, true);
								})
								.fail(() => {
									this.showError(xhr.responseJSON?.message || i18n.genericError || 'An error occurred.');
									setBusy(false);
								});
							return;
						}

						// no answer at all: the connection, not the request
						this.showError(xhr.responseJSON?.message || (0 === xhr.status ? i18n.networkError : '') || i18n.genericError || 'An error occurred.');
						setBusy(false);
					}
				});
			};

			this.$modal.find('.tpt-quote-form-message').empty();
			setBusy(true);

			// Without reCAPTCHA keys the REST nonce protects the form
			if (!config.recaptchaSiteKey) {
				submitData(null);
				return;
			}

			// With keys, every request needs a fresh reCAPTCHA v3 token (they expire after two minutes)
			if (typeof window.grecaptcha === 'undefined' || typeof window.grecaptcha.ready !== 'function') {
				this.showError(i18n.recaptchaUnavailable || 'The spam check could not load. Please reload the page and try again.');
				setBusy(false);
				return;
			}

			const onTokenError = (error) => {
				window.console && console.error('Tiered Pricing Table: reCAPTCHA error', error);
				this.showError(i18n.recaptchaFailed || 'The spam check failed. Please try again.');
				setBusy(false);
			};

			window.grecaptcha.ready(() => {
				try {
					Promise.resolve(window.grecaptcha.execute(config.recaptchaSiteKey, { action: config.recaptchaAction || 'tpt_quote_request' }))
						.then((token) => token ? submitData(token) : onTokenError('empty token'), onTokenError);
				} catch (error) {
					onTokenError(error);
				}
			});
		};
	};

	/**
	 * Manages all Request Quote forms on the page.
	 */
	const RequestQuoteManager = function () {
		this.forms = {};
		this.autoOpened = false;

		this.init = function () {
			this.initializeForms();
			this.bindGlobalEvents();
		};

		this.initializeForms = function () {
			$('.tpt-quote-modal').each((index, el) => {
				const $modal = $(el);
				const productId = $modal.find('.tpt-quote-product-id').val();
				const formId = $modal.find('.tpt-quote-form-id').val();
				
				const config = window.tptQuoteFormConfig.forms.find(f => f.id == formId);
				if (!config || !productId) return;

				const quoteForm = new RequestQuoteForm($modal, productId, config);
				quoteForm.init();
				
				this.forms[productId] = quoteForm;
			});
		};

		/**
		 * Resolve the modal for a trigger: its own product's modal, or the shared modal of its parent
		 * (variations share the parent's modal so triggers inserted by AJAX still have one to open).
		 * Falls back to the enclosing pricing wrapper for themes overriding the button template.
		 */
		this.resolveForm = function ($trigger) {
			const productId = $trigger.data('product-id');

			if (this.forms[productId]) {
				return this.forms[productId];
			}

			const parentId = $trigger.data('parent-id') || $trigger.closest('.tpt__tiered-pricing').data('product-id');

			return parentId ? this.forms[parentId] : undefined;
		};

		this.openForTrigger = function ($trigger) {
			const form = this.resolveForm($trigger);

			if (!form) {
				return;
			}

			const productId = $trigger.data('product-id');

			// Opened from a variation's trigger: make the modal submit that variation.
			if (productId && String(productId) !== String(form.productId)) {
				form.updateState({ variationId: productId, parentId: form.productId });
			}

			form.open();
		};

		this.bindGlobalEvents = function () {
			// quote buttons are links with role="button": a button also answers the Space key
			$(document).on('keydown', '.tpt-request-quote-trigger, .tpt-add-to-quote, .tpt-cart-to-quote', (e) => {
				if ((e.key === ' ' || e.key === 'Spacebar') && !e.isDefaultPrevented() && !$(e.currentTarget).is('button')) {
					e.preventDefault();
					e.currentTarget.click();
				}
			});

			// the whole row of a pricing layout opens its quote button (not a click on the button itself)
			if (!window.tptQuoteRowClicks) {
				window.tptQuoteRowClicks = true;

				$(document).on('click', '[data-tpt-quote-row]', (e) => {
					if ($(e.target).closest('a, button, input, select, textarea, label').length) {
						return;
					}

					const trigger = document.getElementById($(e.currentTarget).attr('data-tpt-quote-row'));

					if (trigger) {
						trigger.click();
					}
				});

				// an entry that is a button itself (a pricing block) answers Enter and Space
				$(document).on('keydown', '[data-tpt-quote-row][role="button"]', (e) => {
					const isKey = e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar';

					if (!isKey || e.target !== e.currentTarget || e.isDefaultPrevented()) {
						return;
					}

					e.preventDefault();

					const trigger = document.getElementById($(e.currentTarget).attr('data-tpt-quote-row'));

					if (trigger) {
						trigger.click();
					}
				});
			}

			// buttons of forms that add to the quote list belong to quote-list.js: no form opens for them
			$(document).on('click', '.tpt-request-quote-trigger:not(.tpt-quote-to-list)', (e) => {
				e.preventDefault();
				this.openForTrigger($(e.currentTarget));
			});

			$(document).on('tiered_price_update', (event, data) => {
				if (!data || !data.__instance || !data.__instance.formatting) return;

				const parentId = data.parentId;
				const variationId = data.productId !== data.parentId ? data.productId : null;
				const priceHtml = data.__instance.formatting.formatPrice(data.price);
				
				const stateUpdate = {
					variationId: variationId,
					parentId: parentId,
					quantity: data.quantity,
					priceHtml: priceHtml
				};

				if (parentId && this.forms[parentId]) {
					this.forms[parentId].updateState(stateUpdate);
					if (data.quantity) {
						this.handleAutoOpen(parseInt(data.quantity, 10), parentId);
					}
				}

				if (variationId && this.forms[variationId]) {
					this.forms[variationId].updateState(stateUpdate);
					if (data.quantity) {
						this.handleAutoOpen(parseInt(data.quantity, 10), variationId);
					}
				}
			});
		};

		this.handleAutoOpen = function (qty, productId) {
			if (this.autoOpened || isNaN(qty)) return;

			let $trigger;
			if (productId) {
				$trigger = $(`.tpt-request-quote-trigger[data-product-id="${productId}"]:not(.tpt-quote-to-list), .tpt-request-quote-trigger[data-parent-id="${productId}"]:not(.tpt-quote-to-list)`).first();
			} else {
				$trigger = $('.tpt-request-quote-trigger:not(.tpt-quote-to-list)').first();
			}

			if ($trigger.length) {
				const form = this.resolveForm($trigger);
				
				if (form) {
					const triggerAttr = $trigger.attr('data-auto-open-quantity');
					let autoOpenQty = null;
					if (triggerAttr !== undefined) {
						// empty string means deactivated, otherwise parse it
						autoOpenQty = triggerAttr === "" ? null : triggerAttr;
					} else {
						// fallback if attribute doesn't exist
						autoOpenQty = form.config?.auto_open_quantity || null;
					}
					
					if (autoOpenQty) {
						const threshold = parseInt(autoOpenQty, 10);
						if (!isNaN(threshold) && threshold > 0 && qty >= threshold) {
							this.autoOpened = true;
							form.open();
						}
					}
				}
			}
		};
	};

	// Initialize Manager
	const manager = new RequestQuoteManager();
	manager.init();
});
