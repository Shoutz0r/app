'use strict';

import Dropdown from 'bootstrap/js/dist/dropdown';
import Tooltip from 'bootstrap/js/dist/tooltip';
import Popover from 'bootstrap/js/dist/popover';
import noUiSlider from 'nouislider';

(function () {
	/**
	 * Bootstrap4 dropdown plugin.
	 * @link https://getbootstrap.com/docs/4.4/components/dropdowns/
	 */
	let dropdownElementList = [].slice.call(document.querySelectorAll('[data-toggle="dropdown"]'));
	dropdownElementList.map(function (dropdownToggleEl) {
		let options = {};
		return new Dropdown(dropdownToggleEl, options);
	});

	/**
	 * Bootstrap4 tooltip plugin.
	 * @link https://getbootstrap.com/docs/4.4/components/tooltips/
	 */
	let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]'));
	tooltipTriggerList.map(function (tooltipTriggerEl) {
		let options = {
			delay: {show: 50, hide: 50},
			html: true,
			placement: 'auto'
		};
		return new Tooltip(tooltipTriggerEl, options);
	});

	/**
	 * Bootstrap4 popover plugin.
	 * @link https://getbootstrap.com/docs/4.4/components/popovers/
	 */
	let popoverTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="popover"]'));
	popoverTriggerList.map(function (popoverTriggerEl) {
		let options = {
			delay: {show: 50, hide: 50},
			html: true,
			placement: 'auto'
		};
		return new Popover(popoverTriggerEl, options);
	});

  /**
	* noUiSlider - range slider with full touch support.
	* @link https://refreshless.com/nouislider/
	*/
	let sliderTriggerList = [].slice.call(document.querySelectorAll("[data-slider]"));
	sliderTriggerList.map(function (sliderTriggerEl) {
		let options = {};
		if (sliderTriggerEl.getAttribute("data-slider")) {
			options = JSON.parse(sliderTriggerEl.getAttribute("data-slider"));
		}
		let slider = noUiSlider.create(sliderTriggerEl, options);
		if (options['js-name']) {
			window[options['js-name']] = slider;
		}
	});

})();
