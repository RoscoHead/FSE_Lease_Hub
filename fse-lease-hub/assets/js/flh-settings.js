/**
 * FSE Lease Hub settings helpers (copy shortcode buttons + color picker).
 */
(function () {
	if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.wpColorPicker) {
		jQuery(function ($) {
			$('.flh-color-field').wpColorPicker();
		});
	}
	// Height mode selector: disable the fixed px input when "Auto" is chosen.
	function syncHeightMode() {
		var mode = document.querySelector('.flh-height-mode');
		var fixed = document.querySelector('.flh-height-fixed');
		if (!mode || !fixed) {
			return;
		}
		fixed.disabled = (mode.value === 'auto');
	}
	document.addEventListener('change', function (e) {
		if (e.target && e.target.classList && e.target.classList.contains('flh-height-mode')) {
			syncHeightMode();
		}
	});
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', syncHeightMode);
	} else {
		syncHeightMode();
	}
})();
document.addEventListener('click', function (e) {
	var btn = e.target && e.target.closest ? e.target.closest('.flh-copy') : null;
	if (!btn) {
		return;
	}
	var text = btn.getAttribute('data-copy') || '';
	function done() {
		var original = btn.textContent;
		btn.textContent = 'Copied!';
		btn.disabled = true;
		setTimeout(function () {
			btn.textContent = original;
			btn.disabled = false;
		}, 1500);
	}
	if (navigator.clipboard && navigator.clipboard.writeText) {
		navigator.clipboard.writeText(text).then(done, done);
	} else {
		var tmp = document.createElement('textarea');
		tmp.value = text;
		document.body.appendChild(tmp);
		tmp.select();
		try {
			document.execCommand('copy');
		} catch (err) {
			/* ignore */
		}
		document.body.removeChild(tmp);
		done();
	}
});
