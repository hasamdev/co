/**
 * Cypher One — front-end behaviour.
 * Vanilla JS, no build step required. Loaded deferred.
 */
(function () {
	'use strict';

	/* Mobile navigation toggle */
	const toggle = document.querySelector('[data-nav-toggle]');
	const nav = document.querySelector('[data-nav]');

	if (toggle && nav) {
		toggle.addEventListener('click', function () {
			const open = nav.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && nav.classList.contains('is-open')) {
				nav.classList.remove('is-open');
				toggle.setAttribute('aria-expanded', 'false');
				toggle.focus();
			}
		});
	}

	/* Reveal-on-scroll for sections (opt-in via .js-reveal) */
	if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		const observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-visible');
						observer.unobserve(entry.target);
					}
				});
			},
			{ threshold: 0.15 }
		);

		document.querySelectorAll('.js-reveal').forEach(function (el) {
			observer.observe(el);
		});
	}
})();
