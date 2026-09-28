/**
 * FSE Lease Hub settings helpers (copy shortcode buttons).
 */
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
