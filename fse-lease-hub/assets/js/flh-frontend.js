/**
 * FSE Lease Hub frontend: auto-height resizer for the leases iframe.
 *
 * Cross-origin iframes cannot be measured from the parent page, so the
 * embedded FSELeaseHub page must announce its height with postMessage, e.g.:
 *
 *   window.parent.postMessage({ source: 'flh-embed', board: 123, height: 800 }, '*');
 *
 * Any of these payload shapes is accepted (height in px):
 *   800 | { height: 800 } | { flhHeight: 800 } | { frameHeight: 800 }
 *   { source: 'flh-embed', height: 800 } | { event: 'size', height: 800 }
 *   { type: 'resize', height: 800 }
 *
 * Messages are only accepted from https://fseleasehub.com and only applied
 * to the iframe that sent them. Until a message arrives, the Height setting
 * is used as the fallback.
 */
(function () {
	'use strict';

	var TRUSTED_ORIGIN = 'https://fseleasehub.com';
	var MIN_HEIGHT = 100;
	var MAX_HEIGHT = 3000;

	function toHeight(value) {
		var h = parseInt(value, 10);
		if (isNaN(h)) {
			return 0;
		}
		if (h < MIN_HEIGHT) {
			return MIN_HEIGHT;
		}
		if (h > MAX_HEIGHT) {
			return MAX_HEIGHT;
		}
		return h;
	}

	function extractHeight(data) {
		if (data === null || data === undefined) {
			return 0;
		}
		if (typeof data === 'number') {
			return toHeight(data);
		}
		if (typeof data === 'string') {
			try {
				var parsed = JSON.parse(data);
				if (parsed !== null && typeof parsed === 'object') {
					return extractHeight(parsed);
				}
			} catch (e) {
				/* not JSON, fall through to plain number parsing */
			}
			return toHeight(data);
		}
		if (typeof data === 'object') {
			var keys = ['height', 'flhHeight', 'frameHeight', 'contentHeight', 'value'];
			for (var i = 0; i < keys.length; i++) {
				if (data[keys[i]] !== undefined) {
					var h = toHeight(data[keys[i]]);
					if (h > 0) {
						return h;
					}
				}
			}
		}
		return 0;
	}

	function findFrame(source) {
		var frames = document.querySelectorAll('iframe.flh-auto-frame[data-flh-auto="1"]');
		for (var i = 0; i < frames.length; i++) {
			try {
				if (frames[i].contentWindow === source) {
					return frames[i];
				}
			} catch (e) {
				/* cross-origin access to contentWindow throws on some browsers */
			}
		}
		return null;
	}

	function onMessage(event) {
		if (event.origin !== TRUSTED_ORIGIN) {
			return;
		}
		var height = extractHeight(event.data);
		if (height <= 0) {
			return;
		}
		var frame = findFrame(event.source);
		if (!frame) {
			return;
		}
		frame.style.height = height + 'px';
		frame.setAttribute('data-flh-auto-applied', String(height));
	}

	if (typeof window !== 'undefined' && window.addEventListener) {
		window.addEventListener('message', onMessage, false);
	}
})();
