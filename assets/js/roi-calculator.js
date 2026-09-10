/**
 * ROI calculator — front page.
 * hours/month = team × hours/week × 4
 * cost/month  = hours/month × hourly cost
 * savings     = cost/month × 0.8
 */
(function () {
	'use strict';

	var root = document.querySelector('[data-roi-calculator]');
	if (!root) {
		return;
	}

	var inputs = {
		team: root.querySelector('[data-roi="team"]'),
		hours: root.querySelector('[data-roi="hours"]'),
		cost: root.querySelector('[data-roi="cost"]')
	};

	function out(name) {
		return root.querySelectorAll('[data-roi-out="' + name + '"]');
	}

	function setText(name, value) {
		out(name).forEach(function (el) {
			el.textContent = value;
		});
	}

	function money(n) {
		return '$' + Math.round(n).toLocaleString('en-US');
	}

	function update() {
		var team = parseInt(inputs.team.value, 10) || 0;
		var hours = parseInt(inputs.hours.value, 10) || 0;
		var cost = parseInt(inputs.cost.value, 10) || 0;

		var monthlyHours = team * hours * 4;
		var monthlyCost = monthlyHours * cost;
		var savings = monthlyCost * 0.8;

		setText('team', team.toLocaleString('en-US'));
		setText('hours', hours.toLocaleString('en-US'));
		setText('cost', cost.toLocaleString('en-US'));
		setText('monthly-hours', monthlyHours.toLocaleString('en-US'));
		setText('monthly-cost', money(monthlyCost));
		setText('savings', money(savings));
	}

	Object.keys(inputs).forEach(function (key) {
		if (inputs[key]) {
			inputs[key].addEventListener('input', update);
		}
	});

	update();
})();
