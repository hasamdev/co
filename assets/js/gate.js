/**
 * Access gate modal.
 *
 * The dialog is rendered closed; a <noscript> stylesheet in the template
 * covers the scripting-off case. Here it is opened as a true modal —
 * backdrop, focus trapping, Escape to close — and the two steps are wired
 * up. Browsers without showModal() fall back to the `open` attribute.
 */
(function () {
	'use strict';

	var dialog = document.getElementById('co-gate-dialog');

	if (!dialog) {
		return;
	}

	var supportsModal = typeof dialog.showModal === 'function';

	function show() {
		if (!supportsModal) {
			dialog.setAttribute('open', '');
			return;
		}

		if (!dialog.open) {
			dialog.showModal();
		}
	}

	function hide() {
		if (supportsModal && dialog.open) {
			dialog.close();
			return;
		}

		dialog.removeAttribute('open');
	}

	/**
	 * Switch between the email and token panels.
	 *
	 * @param {string} step Either 'email' or 'token'.
	 */
	function setStep(step) {
		dialog.setAttribute('data-step', step);

		var focusTarget = dialog.querySelector('[data-step-panel="' + step + '"] input:not([type="hidden"]):not([tabindex="-1"])');

		if (focusTarget) {
			focusTarget.focus();
		}
	}

	// The dialog is rendered closed to avoid a flash of unstyled panel;
	// open it now that we can do so properly.
	show();

	document.addEventListener('click', function (event) {
		var open = event.target.closest('[data-co-gate-open]');
		var close = event.target.closest('[data-co-gate-close]');
		var step = event.target.closest('[data-co-step]');

		if (open) {
			event.preventDefault();
			show();
		}

		if (close) {
			event.preventDefault();
			hide();
		}

		if (step) {
			event.preventDefault();
			setStep(step.getAttribute('data-co-step'));
		}
	});

	// Clicking the backdrop closes: the dialog element itself is only the
	// backdrop area, since all content sits inside .co-modal__inner.
	dialog.addEventListener('click', function (event) {
		if (event.target === dialog) {
			hide();
		}
	});

	/*
	 * Format the code as it is typed: upper-case, dashes every four
	 * characters. The server normalises anyway, so this is purely so what
	 * people see matches what was emailed to them.
	 */
	var token = document.getElementById('co-token');

	if (token) {
		token.addEventListener('input', function () {
			var caretAtEnd = token.selectionStart === token.value.length;
			var raw = token.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 12);
			var groups = raw.match(/.{1,4}/g);
			var formatted = groups ? groups.join('-') : '';

			if (formatted === token.value) {
				return;
			}

			token.value = formatted;

			if (caretAtEnd) {
				token.setSelectionRange(formatted.length, formatted.length);
			}
		});
	}
}());
