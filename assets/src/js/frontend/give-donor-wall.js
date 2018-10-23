/* globals jQuery, Give, give_global_vars */
import '../plugins/dynamicListener.js';

/**
 * This class handles donor wall shortcode related features
 *
 * @since 2.2.0
 *
 */
class GiveDonorWall {
	constructor() {
		window.addEventListener(
			'load', function () {
				/**
				 * Add events
				 */
				window.addDynamicEventListener(document, 'click', '.give-donor__read-more', GiveDonorWall.readMoreBtnEvent);
				window.addDynamicEventListener(document, 'click', '.give-donor__load_more', GiveDonorWall.loadMoreBtnEvent);

			}, false
		);

		// Run code on after window load.
		window.addEventListener('load', function () {
			GiveDonorWall.loadGravatars();
		});
	}

	/**
	 * Add click event to read more link
	 *
	 * @since  2.2.0
	 *
	 * @param {object} evt
	 */
	static readMoreBtnEvent(evt) {
		evt.preventDefault();

		jQuery.magnificPopup.open(
			{
				items: {
					src: evt.target.parentNode.parentNode.parentNode.parentNode.getElementsByClassName('give-donor__comment')[0].innerHTML,
					type: 'inline',
				},
				mainClass: 'give-modal give-donor-wall-modal',
				closeOnBgClick: false,
			}
		);

		return false;
	}

	/**
	 * Add click event to load more link
	 *
	 * @since  2.2.0
	 *
	 * @param {object} evt
	 */
	static loadMoreBtnEvent(evt) {
		evt.preventDefault();

		let loaderButton = evt.target,
			parent = loaderButton.parentNode,
			shortcodeAttrHiddenField = parent.getElementsByClassName('give-donor-wall-shortcode-attrs');

		jQuery.ajax({
			url: Give.fn.getGlobalVar('ajaxurl'),
			method: 'POST',
			data: {
				action: 'give_get_donor_comments',
				data: shortcodeAttrHiddenField.getAttribute('data-shortcode')
			},
			beforeSend() {
				loaderButton.className += ' give-active';
				loaderButton.setAttribute('disabled', 'disabled');
			}
		}).done(function (res) {
			loaderButton.classList.remove('give-active');
			loaderButton.removeAttribute('disabled', 'disabled');

			// Add donor comment.
			if (res.html.length) {
				parent
					.getElementsByClassName('give-grid')[0]
					.insertAdjacentHTML('beforeend', res.html);
			}

			// Update data-shortcode attribute.
			if (res.shortcode.length) {
				shortcodeAttrHiddenField.setAttribute('data-shortcode', res.shortcode);
			}

			// Remove load more button if not any donor comment exist.
			if (!res.remaining) {
				loaderButton.remove();
			}

			GiveDonorWall.loadGravatar(shortcodeAttrHiddenField);
		});

		return false;
	}

	/**
	 * Handle gravatars loading
	 *
	 * @since 2.3.0
	 */
	static loadGravatars() {
		/**
		 * Loop through the number of donor list on the page.
		 *
		 * @since 2.3.0
		 *
		 */
		let shortcodeAttrHiddenFields = document.querySelectorAll('.give-donor-wall-shortcode-attrs');

		shortcodeAttrHiddenFields.forEach(function (shortcodeAttrHiddenField, index) {
			GiveDonorWall.loadGravatar( shortcodeAttrHiddenField );
		});
	}


	/**
	 * Handle gravatar loading
	 *
	 * @since 2.3.0
	 */
	static loadGravatar( shortcodeAttrHiddenField ){
		const gravatar = require('gravatar');

		/**
		 * Loop through the number of donor list on the page.
		 *
		 * @since 2.3.0
		 *
		 */
		let gridWraps,
			gravatarContainer,
			donorEmail,
			isShowGravatar,
			hasValidGravatar;

		isShowGravatar = '1' === Give.fn.getParameterByName('show_avatar', decodeURIComponent(shortcodeAttrHiddenField.getAttribute('data-shortcode') ) );

		// Bailout.
		if( ! isShowGravatar ) {
			return false;
		}

		gridWraps = shortcodeAttrHiddenField.parentNode.querySelectorAll('.give-grid__item');

		gridWraps.forEach(function (gridWrap, index) {
			gravatarContainer = gridWrap.querySelector('.give-donor__image');

			// Bailout out if already tried to load gravatar.
			if (gravatarContainer.classList.contains('gravatar-loaded')) {
				return;
			}

			donorEmail       = gravatarContainer.getAttribute('data-donor_email');
			hasValidGravatar = '1' === gravatarContainer.getAttribute('data-has-valid-gravatar');

			if (hasValidGravatar) {
				let gravatarElement = document.createElement('IMG');

				gravatarContainer.innerHTML = '';
				gravatarElement.setAttribute('src', gravatar.url(donorEmail));
				gravatarElement.setAttribute('width', '60');
				gravatarElement.setAttribute('height', '60');
				gravatarContainer.appendChild(gravatarElement);
			}

			gravatarContainer.className += ' gravatar-loaded';
		});
	}
}

let giveDonorWall = new GiveDonorWall();
