/**
 * Tiered Pricing Table: the quote list.
 *
 * The list lives in the browser (localStorage), so no session or cookie is started and pages stay cacheable.
 * The server only prices and checks the lines it is sent. Logged-in customers also keep the list in their
 * account, so it follows them across devices and survives logging in.
 */
(function () {
	'use strict';

	var config = window.tptQuoteListConfig;

	if (!config) {
		return;
	}

	var i18n = config.i18n || {};

	function t(key) {
		var text = i18n[key] || '';

		for (var i = 1; i < arguments.length; i++) {
			text = text.replace('%' + i + '$s', arguments[i]).replace('%s', arguments[i]);
		}

		return text;
	}

	function toInt(value, fallback) {
		var number = parseInt(value, 10);

		return isNaN(number) ? fallback : number;
	}

	/* --- the list, kept in the browser ------------------------------------------------------------ */

	var Store = {
		lines: [],
		listeners: [],

		clean: function (data) {
			var seen = {};
			var lines = [];

			(Array.isArray(data) ? data : []).forEach(function (line) {
				var id = toInt(line && line.id, 0);
				var qty = toInt(line && line.qty, 0);

				if (id > 0 && qty > 0 && !seen[id]) {
					seen[id] = true;

					// the product a variation belongs to, when known: its buttons show "In your quote list"
					var parent = toInt(line.parent, 0);

					lines.push(parent > 0 ? {id: id, qty: qty, parent: parent} : {id: id, qty: qty});
				}
			});

			return lines.slice(0, config.maxLines || 100);
		},

		load: function () {
			try {
				this.lines = this.clean(JSON.parse(window.localStorage.getItem(config.storageKey) || '[]'));
			} catch (error) {
				this.lines = [];
			}
		},

		save: function (fromServer) {
			try {
				if (this.lines.length) {
					window.localStorage.setItem(config.storageKey, JSON.stringify(this.lines));
				} else {
					window.localStorage.removeItem(config.storageKey);
				}
			} catch (error) {
				// private mode or full storage: the list still works for this page view
			}

			this.emit();

			if (!fromServer) {
				Sync.push();
			}
		},

		find: function (id) {
			for (var i = 0; i < this.lines.length; i++) {
				if (this.lines[i].id === id) {
					return this.lines[i];
				}
			}

			return null;
		},

		add: function (id, qty, parent) {
			var line = this.find(id);

			if (line) {
				line.qty += qty;
			} else {
				if (this.lines.length >= (config.maxLines || 100)) {
					return false;
				}

				line = {id: id, qty: qty};

				if (parent && parent !== id) {
					line.parent = parent;
				}

				this.lines.push(line);
			}

			return true;
		},

		has: function (productId) {
			return this.lines.some(function (line) {
				return line.id === productId || line.parent === productId;
			});
		},

		set: function (id, qty) {
			var line = this.find(id);

			if (line) {
				line.qty = qty;
			}
		},

		remove: function (id) {
			this.lines = this.lines.filter(function (line) {
				return line.id !== id;
			});
		},

		count: function () {
			return this.lines.length;
		},

		on: function (listener) {
			this.listeners.push(listener);
		},

		emit: function () {
			this.listeners.forEach(function (listener) {
				listener();
			});
		}
	};

	/* --- the server -------------------------------------------------------------------------------- */

	function ajax(endpoint, body, headers) {
		var init = {
			method: 'POST',
			credentials: 'same-origin',
			headers: Object.assign({'Content-Type': 'application/json'}, headers || {}),
			body: JSON.stringify(body || {})
		};

		return fetch(config.ajaxUrl.replace('%%endpoint%%', endpoint), init).catch(function () {
			throw new Error('');
		}).then(function (response) {
			return response.json().catch(function () {
				return {success: false};
			});
		}).then(function (json) {
			if (!json || !json.success) {
				throw new Error(json && json.data && json.data.message ? json.data.message : '');
			}

			return json.data;
		});
	}

	var Prices = {
		data: null,
		pending: null,
		timer: null,

		// the list priced for this customer; requests in flight are shared, later changes wait a moment
		fetch: function () {
			var self = this;
			var lines = Store.lines.slice();

			this.pending = ajax('tpt_quote_list_items', {lines: lines}).then(function (data) {
				self.data = data;

				// products that are gone or hidden leave the list
				if (data.removed && data.removed.length) {
					data.removed.forEach(function (id) {
						Store.remove(id);
					});
					Store.save();
					announce(t('unavailable'));
				}

				return data;
			});

			return this.pending;
		},

		refreshSoon: function (callback) {
			var self = this;

			window.clearTimeout(this.timer);
			this.timer = window.setTimeout(function () {
				self.fetch().then(callback).catch(function () {
					callback(null);
				});
			}, 250);
		}
	};

	/* --- a logged-in customer's list, kept in the account ---------------------------------------- */

	var Sync = {
		timer: null,
		started: false,

		// once per browser session: merge this browser's list with the account's (a guest who logs in keeps theirs)
		start: function () {
			var key = config.storageKey + '_synced';

			if (!config.isLoggedIn || !config.syncNonce) {
				return Promise.resolve();
			}

			try {
				if (window.sessionStorage.getItem(key)) {
					this.started = true;

					return Promise.resolve();
				}
			} catch (error) {
				// no session storage: merge every page view
			}

			var self = this;

			return this.send('merge').then(function (data) {
				self.started = true;

				try {
					window.sessionStorage.setItem(key, '1');
				} catch (error) {
					// nothing to do
				}

				// lines added while the request was out are kept, and sent to the account too
				var merged = Store.clean(data.lines);
				var addedMeanwhile = false;

				Store.lines.forEach(function (line) {
					var existing = null;

					merged.forEach(function (candidate) {
						if (candidate.id === line.id) {
							existing = candidate;
						}
					});

					if (!existing) {
						merged.push(line);
						addedMeanwhile = true;
					} else if (line.qty > existing.qty) {
						existing.qty = line.qty;
						addedMeanwhile = true;
					}
				});

				Store.lines = Store.clean(merged);
				Store.save(!addedMeanwhile);
			}).catch(function () {
				self.started = true;
			});
		},

		push: function () {
			if (!config.isLoggedIn || !config.syncNonce || !this.started) {
				return;
			}

			var self = this;

			window.clearTimeout(this.timer);
			this.timer = window.setTimeout(function () {
				self.send('replace').catch(function () {
					// the browser keeps the list; the next change tries again
				});
			}, 600);
		},

		send: function (mode) {
			return ajax('tpt_quote_list_sync', {lines: Store.lines, mode: mode}, {'X-TPT-Nonce': config.syncNonce});
		}
	};

	/* --- small UI helpers ------------------------------------------------------------------------- */

	function el(tag, className, text) {
		var node = document.createElement(tag);

		if (className) {
			node.className = className;
		}

		if (undefined !== text && null !== text) {
			node.textContent = text;
		}

		return node;
	}

	function announce(message) {
		var announcer = document.querySelector('[data-tpt-quote-list-announcer]');

		if (announcer) {
			announcer.textContent = '';
			window.setTimeout(function () {
				announcer.textContent = message;
			}, 50);
		}
	}

	var toastTimer = null;

	function toast(message) {
		var node = document.querySelector('[data-tpt-quote-toast]');

		if (!node) {
			window.alert(message);

			return;
		}

		node.textContent = message;
		node.hidden = false;

		window.clearTimeout(toastTimer);
		toastTimer = window.setTimeout(function () {
			node.hidden = true;
		}, 4000);
	}

	function updateCounters() {
		var count = Store.count();

		document.querySelectorAll('[data-tpt-quote-list-count]').forEach(function (node) {
			node.textContent = String(count);
		});

		document.querySelectorAll('[data-tpt-quote-list-count-label]').forEach(function (node) {
			node.textContent = count ? '(' + count + ')' : '';
		});

		document.querySelectorAll('[data-tpt-quote-fab]').forEach(function (node) {
			node.hidden = 0 === count;
		});

		liftFloatingButton();
	}

	/**
	 * Keep the floating button clear of a bar the theme fixes to the bottom of the screen (Storefront's
	 * phone bar with the cart, a cookie bar): measure what sits there and lift the button above it.
	 */
	function liftFloatingButton() {
		var fab = document.querySelector('[data-tpt-quote-fab]');

		if (!fab || fab.hidden || !document.elementsFromPoint) {
			return;
		}

		var viewportHeight = window.innerHeight;
		var lift = 0;

		// under the button's corner and under the middle of the bottom edge
		[[window.innerWidth - 30, viewportHeight - 3], [Math.round(window.innerWidth / 2), viewportHeight - 3]].forEach(function (point) {
			document.elementsFromPoint(point[0], point[1]).forEach(function (node) {
				for (var current = node; current && current !== document.body && current !== document.documentElement; current = current.parentElement) {
					if (current === fab || fab.contains(current) || current.closest('[data-tpt-quote-drawer]')) {
						return;
					}

					var position = window.getComputedStyle(current).position;

					if ('fixed' === position || 'sticky' === position) {
						var rect = current.getBoundingClientRect();

						// a bar along the bottom, not a full-screen overlay
						if (rect.bottom >= viewportHeight - 2 && rect.height > 0 && rect.height < viewportHeight / 2) {
							lift = Math.max(lift, Math.round(viewportHeight - rect.top));
						}

						return;
					}
				}
			});
		});

		document.documentElement.style.setProperty('--tpt-quote-fab-lift', lift + 'px');
	}

	/* --- the lines, as the drawer and the page show them ----------------------------------------- */

	function clampQuantity(qty, item) {
		var min = item.min || 1;
		var step = item.step || 1;

		qty = Math.max(min, qty);

		// a quantity in the product's steps (packs of 6: 6, 12, 18…)
		if (step > 1) {
			qty = Math.ceil(qty / step) * step;
		}

		if (item.max && qty > item.max) {
			qty = item.max - ((item.max - min) % step);
		}

		return qty;
	}

	function renderLines(container, data, options) {
		var compact = options && options.compact;

		container.textContent = '';
		container.setAttribute('aria-busy', 'false');

		var list = el('ul', 'tpt-ql-lines' + (compact ? ' tpt-ql-lines--compact' : ''));

		data.items.forEach(function (item) {
			var row = el('li', 'tpt-ql-line');
			row.setAttribute('data-id', item.id);

			var image = el('img', 'tpt-ql-line__image');
			image.src = item.image;
			image.alt = '';
			image.loading = 'lazy';
			row.appendChild(image);

			var info = el('div', 'tpt-ql-line__info');
			var name = el('a', 'tpt-ql-line__name', item.name);
			name.href = item.url;
			info.appendChild(name);

			if (item.attributes) {
				info.appendChild(el('span', 'tpt-ql-line__attributes', item.attributes));
			}

			if (item.sku && !compact) {
				info.appendChild(el('span', 'tpt-ql-line__sku', t('sku', item.sku)));
			}

			if (item.nextTier) {
				var hint = el('span', 'tpt-ql-line__hint');
				hint.innerHTML = t('nextTier', String(item.nextTier.more), item.nextTier.priceHtml);
				info.appendChild(hint);
			}

			// why the stepper stops: the product's minimum and step
			var minimum = item.min || 1;
			var stepSize = item.step || 1;

			if (minimum > 1 || stepSize > 1) {
				var limits = [];

				if (minimum > 1) {
					limits.push(t('minimum', String(minimum)));
				}

				if (stepSize > 1) {
					limits.push(t('stepOf', String(stepSize)));
				}

				info.appendChild(el('span', 'tpt-ql-line__limits', limits.join(', ')));
			}

			row.appendChild(info);

			// quantity stepper
			var qtyWrap = el('div', 'tpt-ql-line__qty');
			var minus = el('button', 'tpt-ql-step', '−');
			minus.type = 'button';
			minus.setAttribute('data-step', '-1');
			minus.setAttribute('aria-label', t('decrease'));
			minus.disabled = item.qty <= (item.min || 1);

			var input = el('input', 'tpt-ql-line__input');
			input.type = 'number';
			input.inputMode = 'numeric';
			input.min = String(item.min || 1);
			input.step = String(item.step || 1);

			if (item.max) {
				input.max = String(item.max);
			}

			input.value = String(item.qty);
			input.setAttribute('aria-label', t('quantityOf', item.name + (item.attributes ? ' (' + item.attributes + ')' : '')));

			var plus = el('button', 'tpt-ql-step', '+');
			plus.type = 'button';
			plus.setAttribute('data-step', '1');
			plus.setAttribute('aria-label', t('increase'));
			plus.disabled = !!item.max && item.qty >= item.max;

			qtyWrap.appendChild(minus);
			qtyWrap.appendChild(input);
			qtyWrap.appendChild(plus);
			row.appendChild(qtyWrap);

			// estimated price
			var price = el('div', 'tpt-ql-line__price');

			if (item.unitPriceHtml) {
				var total = el('strong', 'tpt-ql-line__total');
				total.innerHTML = item.lineTotalHtml;
				price.appendChild(total);

				var unit = el('span', 'tpt-ql-line__unit');
				unit.innerHTML = item.unitPriceHtml + ' ' + t('each');
				price.appendChild(unit);
			} else {
				price.appendChild(el('span', 'tpt-ql-line__on-request', t('onRequest')));
			}

			row.appendChild(price);

			var remove = el('button', 'tpt-ql-line__remove');
			remove.type = 'button';
			remove.setAttribute('aria-label', t('removeItem', item.name + (item.attributes ? ' (' + item.attributes + ')' : '')));
			remove.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>';
			row.appendChild(remove);

			bindLine(row, item, container, options);
			list.appendChild(row);
		});

		container.appendChild(list);

		var summary = el('div', 'tpt-ql-summary');

		if (null !== data.total && undefined !== data.total && data.showPrices) {
			var line = el('p', 'tpt-ql-summary__total');
			line.appendChild(el('span', '', t('estimatedTotal')));
			var amount = el('strong');
			amount.innerHTML = data.totalHtml;
			line.appendChild(amount);
			summary.appendChild(line);
			summary.appendChild(el('p', 'tpt-ql-summary__note', t('estimateNote')));
		} else {
			summary.appendChild(el('p', 'tpt-ql-summary__note', t('noPricesNote')));
		}

		container.appendChild(summary);
	}

	function bindLine(row, item, container, options) {
		var input = row.querySelector('.tpt-ql-line__input');

		var commit = function (qty) {
			qty = clampQuantity(qty, item);
			input.value = String(qty);

			if (qty === item.qty) {
				return;
			}

			item.qty = qty;
			Store.set(item.id, qty);
			Store.save();
			announce(t('updated'));
			row.classList.add('is-updating');

			Prices.refreshSoon(function (data) {
				if (data) {
					rerender(container, data, options, item.id);
				}
			});
		};

		row.querySelectorAll('.tpt-ql-step').forEach(function (button) {
			button.addEventListener('click', function () {
				var direction = toInt(button.getAttribute('data-step'), 1);
				commit(toInt(input.value, item.qty) + direction * (item.step || 1));
			});
		});

		input.addEventListener('change', function () {
			commit(toInt(input.value, item.qty));
		});

		input.addEventListener('keydown', function (event) {
			if ('Enter' === event.key) {
				event.preventDefault();
				commit(toInt(input.value, item.qty));
			}
		});

		row.querySelector('.tpt-ql-line__remove').addEventListener('click', function () {
			Store.remove(item.id);
			Store.save();
			announce(t('removed'));

			var next = row.nextElementSibling || row.previousElementSibling;
			var nextId = next ? toInt(next.getAttribute('data-id'), 0) : 0;

			if (!Store.count()) {
				Prices.data = {items: [], total: null, showPrices: false};
				refreshViews();

				return;
			}

			row.classList.add('is-updating');

			Prices.fetch().then(function (data) {
				rerender(container, data, options, nextId, true);
			});
		});
	}

	// redraw a list and put the focus back where it was (the same line's quantity, or the next line's remove)
	function rerender(container, data, options, focusId, focusRemove) {
		renderLines(container, data, options);
		refreshOtherViews(container);

		if (focusId) {
			var row = container.querySelector('.tpt-ql-line[data-id="' + focusId + '"]');
			var target = row ? row.querySelector(focusRemove ? '.tpt-ql-line__remove' : '.tpt-ql-line__input') : null;

			if (target) {
				target.focus();
			}
		}
	}

	/* --- the drawer ------------------------------------------------------------------------------- */

	var Drawer = {
		node: null,
		opener: null,

		init: function () {
			this.node = document.querySelector('[data-tpt-quote-drawer]');

			if (!this.node) {
				return;
			}

			var self = this;

			this.node.querySelectorAll('[data-tpt-quote-drawer-close]').forEach(function (button) {
				button.addEventListener('click', function () {
					self.close();
				});
			});

			this.node.addEventListener('keydown', function (event) {
				if ('Escape' === event.key) {
					self.close();
				}

				if ('Tab' === event.key) {
					self.trapFocus(event);
				}
			});
		},

		isOpen: function () {
			return this.node && !this.node.hidden;
		},

		open: function (status) {
			if (!this.node) {
				if (config.pageUrl) {
					window.location.href = config.pageUrl;
				}

				return;
			}

			if (!this.isOpen()) {
				this.opener = document.activeElement;
			}

			this.node.hidden = false;
			document.documentElement.classList.add('tpt-quote-drawer-open');

			var statusNode = this.node.querySelector('[data-tpt-quote-drawer-status]');
			statusNode.textContent = status || '';
			statusNode.hidden = !status;

			this.render();
			this.node.querySelector('.tpt-quote-drawer__panel').focus();
		},

		close: function () {
			if (!this.isOpen()) {
				return;
			}

			this.node.hidden = true;
			document.documentElement.classList.remove('tpt-quote-drawer-open');

			if (this.opener && document.body.contains(this.opener)) {
				this.opener.focus();
			}
		},

		render: function () {
			var lines = this.node.querySelector('[data-tpt-quote-drawer-lines]');
			var total = this.node.querySelector('[data-tpt-quote-drawer-total]');

			if (!Store.count()) {
				lines.textContent = '';
				lines.appendChild(el('p', 'tpt-ql-empty', t('emptyDrawer') || ''));
				total.textContent = '';

				return;
			}

			lines.setAttribute('aria-busy', 'true');

			Prices.fetch().then(function (data) {
				renderLines(lines, data, {compact: true});
				total.textContent = '';
			}).catch(function (error) {
				lines.textContent = '';
				lines.appendChild(el('p', 'tpt-ql-error', error.message || t('loadError')));
			});
		},

		trapFocus: function (event) {
			var focusable = Array.prototype.filter.call(
				this.node.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'),
				function (node) {
					return node.offsetParent !== null;
				}
			);

			if (!focusable.length) {
				return;
			}

			var first = focusable[0];
			var last = focusable[focusable.length - 1];

			if (event.shiftKey && (document.activeElement === first || document.activeElement === this.node.querySelector('.tpt-quote-drawer__panel'))) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		}
	};

	/* --- the list page ---------------------------------------------------------------------------- */

	var Page = {
		node: null,

		init: function () {
			this.node = document.querySelector('[data-tpt-quote-list]');

			if (!this.node) {
				return;
			}

			var self = this;
			var form = this.node.querySelector('[data-tpt-quote-list-form]');

			if (form) {
				form.addEventListener('submit', function (event) {
					event.preventDefault();
					self.submit(form);
				});
			}

			// arriving from "Request a quote for this cart"
			try {
				var notice = window.sessionStorage.getItem(config.storageKey + '_notice');

				if (notice) {
					window.sessionStorage.removeItem(config.storageKey + '_notice');

					var status = this.node.querySelector('[data-tpt-quote-list-status]');

					if (status && 'cartCopied' === notice) {
						status.textContent = t('cartCopied');
						status.hidden = false;
					}
				}
			} catch (error) {
				// no notice
			}

			this.render();
		},

		render: function () {
			if (!this.node) {
				return;
			}

			var lines = this.node.querySelector('[data-tpt-quote-list-lines]');
			var empty = this.node.querySelector('[data-tpt-quote-list-empty]');
			var form = this.node.querySelector('[data-tpt-quote-list-form]');
			var success = this.node.querySelector('[data-tpt-quote-list-success]');

			if (success && !success.hidden) {
				return;
			}

			if (!Store.count()) {
				lines.textContent = '';
				lines.setAttribute('aria-busy', 'false');
				empty.hidden = false;

				if (form) {
					form.hidden = true;
				}

				return;
			}

			empty.hidden = true;

			Prices.fetch().then(function (data) {
				if (!data.items.length) {
					lines.textContent = '';
					empty.hidden = false;

					if (form) {
						form.hidden = true;
					}

					return;
				}

				renderLines(lines, data, {compact: false});

				if (form) {
					form.hidden = false;
				}
			}).catch(function (error) {
				lines.textContent = '';
				lines.appendChild(el('p', 'tpt-ql-error', error.message || t('loadError')));
			});
		},

		submit: function (form) {
			var self = this;
			var message = form.querySelector('[data-tpt-quote-list-message]');
			var button = form.querySelector('[type="submit"]');

			if (button.disabled) {
				return;
			}

			if (form.reportValidity && !form.reportValidity()) {
				return;
			}

			var label = button.textContent;
			var setBusy = function (busy) {
				button.disabled = busy;
				button.textContent = busy ? t('sending') : label;
			};

			var showError = function (text) {
				message.textContent = text || t('genericError');
				setBusy(false);
			};

			message.textContent = '';
			setBusy(true);

			var send = function (token, nonceRefreshed) {
				var data = new FormData(form);

				data.append(config.listParam, JSON.stringify(Store.lines));

				if (token) {
					data.append('g-recaptcha-response', token);
				}

				var headers = {};

				if (config.nonce) {
					headers['X-WP-Nonce'] = config.nonce;
				}

				fetch(config.submitUrl, {method: 'POST', credentials: 'same-origin', headers: headers, body: data}).then(function (response) {
					return response.json().catch(function () {
						return {};
					}).then(function (json) {
						return {status: response.status, json: json};
					});
				}).then(function (result) {
					// the page is older than its nonce: get a fresh one and send again, once
					if (!nonceRefreshed && 403 === result.status && result.json && 'rest_cookie_invalid_nonce' === result.json.code) {
						var body = new FormData();
						body.append('action', config.nonceAjaxAction);

						return fetch(config.adminAjaxUrl, {method: 'POST', credentials: 'same-origin', body: body}).then(function (response) {
							return response.json();
						}).then(function (json) {
							config.nonce = json && json.data ? json.data.nonce : '';
							send(token, true);
						}).catch(function () {
							showError(result.json.message);
						});
					}

					if (!result.json || !result.json.success) {
						showError(result.json && result.json.message);

						return;
					}

					self.done(result.json);
				}).catch(function () {
					showError();
				});
			};

			if (!config.recaptchaKey) {
				send(null);

				return;
			}

			if (!window.grecaptcha || !window.grecaptcha.ready) {
				showError(t('recaptchaFailed'));

				return;
			}

			window.grecaptcha.ready(function () {
				try {
					Promise.resolve(window.grecaptcha.execute(config.recaptchaKey, {action: config.recaptchaAction})).then(function (token) {
						return token ? send(token) : showError(t('recaptchaFailed'));
					}, function () {
						showError(t('recaptchaFailed'));
					});
				} catch (error) {
					showError(t('recaptchaFailed'));
				}
			});
		},

		done: function (response) {
			// what was sent, for the thank-you message (before the list is emptied)
			var sent = (Prices.data && Prices.data.items ? Prices.data.items : []).map(function (item) {
				return item.name + (item.attributes ? ' (' + item.attributes + ')' : '') + ' × ' + item.qty;
			});

			Store.lines = [];
			Store.save();

			// the store's own thank-you page, when the form sends shoppers there (web addresses only)
			if ('redirect' === config.successAction && /^https?:\/\//i.test(config.successUrl || '')) {
				window.location.href = config.successUrl;

				return;
			}

			var lines = this.node.querySelector('[data-tpt-quote-list-lines]');
			var form = this.node.querySelector('[data-tpt-quote-list-form]');
			var success = this.node.querySelector('[data-tpt-quote-list-success]');

			lines.textContent = '';

			if (form) {
				form.hidden = true;
			}

			success.querySelector('[data-tpt-quote-list-success-message]').textContent = config.successMessage || '';
			success.querySelector('[data-tpt-quote-list-reference]').textContent = response.post_id ? t('reference', '#' + response.post_id) : '';

			var copy = success.querySelector('[data-tpt-quote-list-copy]');
			var emailField = form ? form.querySelector('input[type="email"]') : null;
			var email = emailField ? String(emailField.value || '').trim() : '';

			if (copy) {
				copy.textContent = config.customerEmail && email ? t('copySent', email) : '';
				copy.hidden = !copy.textContent;
			}

			var sentNode = success.querySelector('[data-tpt-quote-list-sent]');

			if (sentNode && sent.length) {
				sentNode.textContent = '';
				sentNode.appendChild(el('p', 'tpt-quote-list__sent-title', t('youAskedFor')));

				var list = el('ul', 'tpt-quote-list__sent-list');

				sent.forEach(function (line) {
					list.appendChild(el('li', '', line));
				});

				sentNode.appendChild(list);
			}

			var status = this.node.querySelector('[data-tpt-quote-list-status]');

			if (status) {
				status.hidden = true;
			}

			success.hidden = false;
			success.focus();
			success.scrollIntoView({block: 'start', behavior: 'smooth'});
		}
	};

	function refreshViews() {
		updateCounters();

		if (Page.node) {
			Page.render();
		}

		if (Drawer.isOpen()) {
			Drawer.render();
		}
	}

	// after a change in one view, the other one follows (the drawer on the list page, for example)
	function refreshOtherViews(changed) {
		updateCounters();

		if (Page.node && !Page.node.contains(changed)) {
			Page.render();
		}

		if (Drawer.isOpen() && !Drawer.node.contains(changed)) {
			Drawer.render();
		}
	}

	/* --- adding products ------------------------------------------------------------------------- */

	var lastQuantity = {};

	// (the quote button's own fallback form, for products that cannot be bought, holds no options)
	var PRODUCT_FORMS = 'form.cart:not(.tpt-custom-quote-cart), form.wc-block-add-to-cart-with-options';

	// the product's add to cart form: the classic form or the Add to Cart with Options block
	function productFormFor(trigger, productId) {
		var own = trigger.closest(PRODUCT_FORMS);

		if (own) {
			return own;
		}

		var forms = document.querySelectorAll(PRODUCT_FORMS);

		for (var i = 0; i < forms.length; i++) {
			var form = forms[i];

			if (String(form.getAttribute('data-product_id')) === String(productId) || form.querySelector('[name="add-to-cart"][value="' + productId + '"], [name="product_id"][value="' + productId + '"], button[name="add-to-cart"][value="' + productId + '"]')) {
				return form;
			}
		}

		return 1 === forms.length ? forms[0] : null;
	}

	function quantityFor(trigger, productId, parentId) {
		var form = productFormFor(trigger, parentId || productId);
		var input = form ? form.querySelector('input[name="quantity"], input.qty') : null;
		var fromInput = toInt(input && input.value, 0);

		// the field the shopper typed in wins; the pricing table's last report is the fallback
		if (fromInput > 0) {
			return fromInput;
		}

		return Math.max(1, lastQuantity[productId] || lastQuantity[parentId] || 1);
	}

	// a quote button of a variable product adds the chosen variation
	function resolveProduct(trigger) {
		var productId = toInt(trigger.getAttribute('data-product-id'), 0);
		var parentId = toInt(trigger.getAttribute('data-parent-id'), 0) || productId;
		var form = productId === parentId ? productFormFor(trigger, productId) : null;
		var variationInput = form ? form.querySelector('input[name="variation_id"]') : null;

		if (variationInput) {
			var variation = toInt(variationInput.value, 0);

			if (!variation) {
				return {error: t('chooseOptions')};
			}

			productId = variation;
		}

		return {id: productId, parentId: parentId};
	}

	function clearInlineErrors() {
		document.querySelectorAll('.tpt-quote-inline-error').forEach(function (node) {
			node.remove();
		});
	}

	// next to the button, until the shopper acts on it (a message that fades on its own is easily missed)
	function showInlineError(trigger, message) {
		var node = el('p', 'tpt-quote-inline-error', message);

		node.setAttribute('role', 'alert');

		var after = trigger.nextElementSibling && trigger.nextElementSibling.classList.contains('tpt-in-quote-note') ? trigger.nextElementSibling : trigger;

		after.insertAdjacentElement('afterend', node);
	}

	// inside a pricing layout the entry's own label says the product is in the list: no extra element, no jump
	function setEntryLabel(label, quantity) {
		if (quantity > 0) {
			if (!label.hasAttribute('data-tpt-quote-label-text')) {
				label.setAttribute('data-tpt-quote-label-text', label.textContent);
			}

			label.textContent = t('inListQty', String(quantity));
			label.classList.add('tpt-quote-label--in-list');
		} else if (label.hasAttribute('data-tpt-quote-label-text')) {
			label.textContent = label.getAttribute('data-tpt-quote-label-text');
			label.removeAttribute('data-tpt-quote-label-text');
			label.classList.remove('tpt-quote-label--in-list');
		}
	}

	// "In your quote list" for the buttons of products already in the list: in the label of a pricing layout's
	// entry, or as a note next to a button that stands by itself
	function markInList() {
		document.querySelectorAll('.tpt-request-quote-trigger.tpt-quote-to-list, .tpt-add-to-quote').forEach(function (trigger) {
			var productId = toInt(trigger.getAttribute('data-product-id'), 0);
			var next = trigger.nextElementSibling;
			var note = next && next.classList.contains('tpt-in-quote-note') ? next : null;
			var entry = trigger.closest ? trigger.closest('[data-tpt-quote-row]') : null;
			var label = entry ? entry.querySelector('[data-tpt-quote-label]') : null;

			// how many of this product (or of its variations) the list holds
			var quantity = 0;

			Store.lines.forEach(function (line) {
				if (productId && (line.id === productId || line.parent === productId)) {
					quantity += line.qty;
				}
			});

			if (label) {
				if (note) {
					note.remove();
				}

				setEntryLabel(label, quantity);

				return;
			}

			// a button the layout keeps hidden gets no note
			if (quantity > 0 && (trigger.offsetParent !== null || note)) {
				if (!note) {
					note = el('button', 'tpt-in-quote-note', '');
					note.type = 'button';
					note.addEventListener('click', function () {
						Drawer.open('');
					});
					trigger.insertAdjacentElement('afterend', note);
				}

				note.textContent = t('inListQty', String(quantity));
			} else if (note) {
				note.remove();
			}
		});
	}

	function addLines(lines, status) {
		var added = 0;
		var full = false;

		lines.forEach(function (line) {
			if (Store.add(line.id, line.qty, line.parent)) {
				added++;
			} else {
				full = true;
			}
		});

		if (added) {
			Store.save();
		}

		if (full) {
			toast(t('listFull'));
		}

		return added;
	}

	function bindAdding() {
		document.addEventListener('click', function (event) {
			// quote buttons of forms that add to the list (other forms open their own form, quote-form.js)
			var trigger = event.target.closest('.tpt-request-quote-trigger.tpt-quote-to-list, .tpt-add-to-quote');

			if (trigger) {
				event.preventDefault();

				// a pricing table row clicks its button again when the button itself is clicked: one add per click
				var now = Date.now();

				if (trigger._tptAddedAt && now - trigger._tptAddedAt < 500) {
					return;
				}

				trigger._tptAddedAt = now;

				var target;
				var qty;

				clearInlineErrors();

				if (trigger.classList.contains('tpt-add-to-quote')) {
					target = {id: toInt(trigger.getAttribute('data-product-id'), 0)};
					qty = Math.max(1, toInt(trigger.getAttribute('data-quantity'), 1));

					// a quantity field in the same product card (some themes add one to shop pages)
					var card = trigger.closest('li.product, .wc-block-product, .product');
					var cardInput = card ? card.querySelector('input.qty, input[name="quantity"]') : null;

					if (cardInput && toInt(cardInput.value, 0) > 0) {
						qty = toInt(cardInput.value, qty);
					}
				} else {
					target = resolveProduct(trigger);

					if (target.error) {
						showInlineError(trigger, target.error);

						return;
					}

					qty = quantityFor(trigger, target.id, target.parentId);
				}

				var already = target.id ? Store.find(target.id) : null;
				var before = already ? already.qty : 0;

				if (target.id && addLines([{id: target.id, qty: qty, parent: target.parentId}])) {
					// a product that was already in the list: its quantities are added up, so say the new one
					var message = before ? t('nowInList', String(before + qty)) : t('added');

					announce(message);
					Drawer.open(message);
				}

				return;
			}

			var cartButton = event.target.closest('.tpt-cart-to-quote');

			if (cartButton) {
				event.preventDefault();
				addCart(cartButton);

				return;
			}

			if (event.target.closest('[data-tpt-quote-fab]')) {
				Drawer.open('');
			}
		});

		// quote buttons are links with role="button": a button also answers the Space key
		document.addEventListener('keydown', function (event) {
			if ((' ' !== event.key && 'Spacebar' !== event.key) || event.defaultPrevented || !event.target.closest) {
				return;
			}

			var button = event.target.closest('.tpt-request-quote-trigger, .tpt-add-to-quote, .tpt-cart-to-quote');

			if (button && 'BUTTON' !== button.tagName) {
				event.preventDefault();
				button.click();
			}
		});

		// the whole row of a pricing layout presses its quote button (not a click on the button itself)
		if (!window.tptQuoteRowClicks) {
			window.tptQuoteRowClicks = true;

			document.addEventListener('click', function (event) {
				var row = event.target.closest ? event.target.closest('[data-tpt-quote-row]') : null;

				if (!row || event.target.closest('a, button, input, select, textarea, label')) {
					return;
				}

				var rowButton = document.getElementById(row.getAttribute('data-tpt-quote-row'));

				if (rowButton) {
					rowButton.click();
				}
			});

			// an entry that is a button itself (a pricing block) answers Enter and Space
			document.addEventListener('keydown', function (event) {
				var isKey = 'Enter' === event.key || ' ' === event.key || 'Spacebar' === event.key;
				var row = event.target;

				if (!isKey || event.defaultPrevented || !row.matches || !row.matches('[data-tpt-quote-row][role="button"]')) {
					return;
				}

				event.preventDefault();

				var rowButton = document.getElementById(row.getAttribute('data-tpt-quote-row'));

				if (rowButton) {
					rowButton.click();
				}
			});
		}

		// the shopper works on the product's options (selects, or the option chips of the block form)
		document.addEventListener('change', function (event) {
			if (event.target.closest && event.target.closest(PRODUCT_FORMS)) {
				clearInlineErrors();
			}
		});

		document.addEventListener('click', function (event) {
			if (event.target.closest && event.target.closest(PRODUCT_FORMS) && !event.target.closest('.tpt-request-quote-trigger')) {
				clearInlineErrors();
			}
		}, true);

		// other parts of the store (the Quick Order Form) add many lines at once
		document.addEventListener('tpt:quote-list:add', function (event) {
			var lines = Store.clean(event.detail && event.detail.lines);

			if (lines.length && addLines(lines)) {
				announce(t('addedMany'));

				if (event.detail && event.detail.goToList && config.pageUrl) {
					window.location.href = config.pageUrl;
				} else {
					Drawer.open(t('addedMany'));
				}
			}
		});

		// the quantity the shopper picked on the product page, as the pricing table reports it
		if (window.jQuery) {
			// a variation was chosen: the "choose the options first" message has done its job
			window.jQuery(document).on('found_variation', 'form.variations_form', clearInlineErrors);

			window.jQuery(document).on('tiered_price_update', function (event, data) {
				if (data && data.quantity) {
					lastQuantity[data.productId] = toInt(data.quantity, 1);

					if (data.parentId) {
						lastQuantity[data.parentId] = toInt(data.quantity, 1);
					}
				}
			});

			// a variation's pricing layout arrives after the page: its entry learns what the list holds
			var markTimer;

			window.jQuery(document).on('found_variation tiered_price_update', function () {
				clearTimeout(markTimer);
				markTimer = setTimeout(markInList, 250);
			});
		}
	}

	// every line of the cart, as the Store API reports it (also after the Cart block changed it)
	function addCart(button) {
		button.classList.add('is-busy');

		fetch(config.cartUrl, {credentials: 'same-origin'}).then(function (response) {
			return response.json();
		}).then(function (cart) {
			var lines = (cart && cart.items ? cart.items : []).map(function (item) {
				return {id: toInt(item.id, 0), qty: toInt(item.quantity, 0)};
			});

			lines = Store.clean(lines);

			if (!lines.length) {
				button.classList.remove('is-busy');
				toast(t('cartEmpty'));

				return;
			}

			addLines(lines);

			try {
				window.sessionStorage.setItem(config.storageKey + '_notice', 'cartCopied');
			} catch (error) {
				// the list page just shows the list
			}

			window.location.href = button.getAttribute('href') || config.pageUrl;
		}).catch(function () {
			button.classList.remove('is-busy');
			toast(t('genericError'));
		});
	}

	/* --- start -------------------------------------------------------------------------------------- */

	function start() {
		Store.load();
		Store.on(updateCounters);
		Store.on(markInList);

		Drawer.init();
		bindAdding();
		updateCounters();
		markInList();

		// bars can appear late (cookie notices) or change with the screen size
		var liftTimer;

		window.addEventListener('resize', function () {
			clearTimeout(liftTimer);
			liftTimer = setTimeout(liftFloatingButton, 150);
		});
		window.addEventListener('load', liftFloatingButton);
		setTimeout(liftFloatingButton, 1500);

		// another tab changed the list
		window.addEventListener('storage', function (event) {
			if (event.key === config.storageKey) {
				Store.load();
				refreshViews();
			}
		});

		Sync.start().then(function () {
			updateCounters();
			markInList();
			Page.init();
		});

		// the public API, for themes and other plugins
		window.tptQuoteList = {
			add: function (id, qty) {
				return addLines([{id: toInt(id, 0), qty: Math.max(1, toInt(qty, 1))}]);
			},
			count: function () {
				return Store.count();
			},
			lines: function () {
				return Store.lines.slice();
			},
			open: function () {
				Drawer.open('');
			}
		};
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
