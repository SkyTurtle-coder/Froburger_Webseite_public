( function () {
	'use strict';

	function initCarousel(root) {
		if (!root || root.dataset.avfCarouselReady === 'true') {
			return;
		}

		root.dataset.avfCarouselReady = 'true';

		const slides = Array.from(root.querySelectorAll('.avf-post-carousel__slide'));
		const dots = Array.from(root.querySelectorAll('.avf-post-carousel__dot'));
		const prevButton = root.querySelector('.avf-post-carousel__controls [data-direction="prev"]');
		const nextButton = root.querySelector('.avf-post-carousel__controls [data-direction="next"]');
		const viewport = root.querySelector('.avf-post-carousel__viewport');
		const track = root.querySelector('.avf-post-carousel__track');
		const status = root.querySelector('.avf-post-carousel__status');
		const openButtons = Array.from(root.querySelectorAll('[data-avf-carousel-open]'));
		const dialog = root.querySelector('.avf-post-carousel__dialog');
		const dialogStage = root.querySelector('[data-avf-carousel-dialog-stage]');
		const dialogSlides = Array.from(root.querySelectorAll('.avf-post-carousel__dialog-slide'));
		const dialogCaption = root.querySelector('[data-avf-carousel-dialog-caption]');
		const dialogCloseButton = root.querySelector('[data-avf-carousel-close]');
		const dialogNavButtons = Array.from(root.querySelectorAll('[data-avf-carousel-dialog-nav]'));
		const hasMultiple = slides.length > 1;
		const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
		let activeIndex = 0;
		let lastTrigger = null;
		let touchStartX = null;
		let touchStartY = null;
		let resizeObserver = null;

		if (!slides.length || !viewport || !track || !status) {
			return;
		}

		function getFocusableElements() {
			if (!dialog) {
				return [];
			}

			return Array.from(
				dialog.querySelectorAll(
					'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
				)
			).filter(function (element) {
				return element.offsetParent !== null || element === document.activeElement;
			});
		}

		function getCaptionText(index) {
			const caption = slides[index] ? slides[index].querySelector('.avf-post-carousel__caption') : null;
			return caption ? caption.textContent.trim() : '';
		}

		function updateStatus(index) {
			status.textContent = hasMultiple
				? 'Bild ' + (index + 1) + ' von ' + slides.length
				: 'Einzelbild';
		}

		function getActiveImageHeight() {
			const slide = slides[activeIndex];
			const image = slide ? slide.querySelector('img') : null;
			if (!image) {
				return 0;
			}

			return Math.ceil(image.getBoundingClientRect().height);
		}

		function updateViewportHeight() {
			window.requestAnimationFrame(function () {
				const height = getActiveImageHeight();
				if (height > 0) {
					track.style.height = height + 'px';
				}
			});
		}

		function ensureDialogImage(index) {
			const slide = dialogSlides[index];
			if (!slide) {
				return null;
			}

			let image = slide.querySelector('img');
			if (image) {
				return image;
			}

			const src = slide.getAttribute('data-full-src') || '';
			if (!src) {
				return null;
			}

			image = document.createElement('img');
			image.className = 'avf-post-carousel__dialog-image';
			image.src = src;
			image.alt = slide.getAttribute('data-full-alt') || '';
			image.decoding = 'async';
			image.loading = 'eager';

			const width = parseInt(slide.getAttribute('data-full-width') || '0', 10);
			const height = parseInt(slide.getAttribute('data-full-height') || '0', 10);

			if (width > 0) {
				image.width = width;
			}

			if (height > 0) {
				image.height = height;
			}

			slide.appendChild(image);
			return image;
		}

		function render(index, shouldFocusDot) {
			activeIndex = index;

			slides.forEach(function (slide, slideIndex) {
				const isActive = slideIndex === activeIndex;
				slide.classList.toggle('is-active', isActive);
				slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
				slide.toggleAttribute('data-active', isActive);
			});

			dots.forEach(function (dot, dotIndex) {
				const isActive = dotIndex === activeIndex;
				dot.classList.toggle('is-active', isActive);
				dot.setAttribute('aria-pressed', isActive ? 'true' : 'false');
				dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
				dot.tabIndex = isActive ? 0 : -1;

				if (isActive && shouldFocusDot) {
					dot.focus();
				}
			});

			if (dialogSlides.length) {
				ensureDialogImage(activeIndex);

				dialogSlides.forEach(function (slide, slideIndex) {
					const isActive = slideIndex === activeIndex;
					slide.classList.toggle('is-active', isActive);
					slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
				});
			}

			if (dialogCaption) {
				dialogCaption.textContent = getCaptionText(activeIndex);
			}

			updateStatus(activeIndex);
			updateViewportHeight();
		}

		function showPrevious(shouldFocusDot) {
			const nextIndex = activeIndex === 0 ? slides.length - 1 : activeIndex - 1;
			render(nextIndex, !!shouldFocusDot);
		}

		function showNext(shouldFocusDot) {
			const nextIndex = activeIndex === slides.length - 1 ? 0 : activeIndex + 1;
			render(nextIndex, !!shouldFocusDot);
		}

		function lockBodyScroll() {
			document.body.classList.add('avf-post-carousel-lightbox-open');
		}

		function unlockBodyScroll() {
			document.body.classList.remove('avf-post-carousel-lightbox-open');
		}

		function openDialog(index, trigger) {
			if (!dialog) {
				return;
			}

			lastTrigger = trigger || document.activeElement;
			render(index, false);

			if (typeof dialog.showModal === 'function' && !dialog.open) {
				dialog.showModal();
			} else {
				dialog.setAttribute('open', 'open');
			}

			dialog.classList.add('is-open');
			lockBodyScroll();

			window.requestAnimationFrame(function () {
				if (dialogCloseButton) {
					dialogCloseButton.focus();
				}
			});
		}

		function closeDialog() {
			if (!dialog) {
				return;
			}

			if (typeof dialog.close === 'function' && dialog.open) {
				dialog.close();
			} else {
				dialog.removeAttribute('open');
			}

			dialog.classList.remove('is-open');
			unlockBodyScroll();

			if (lastTrigger && typeof lastTrigger.focus === 'function') {
				lastTrigger.focus();
			}
		}

		function handleFocusTrap(event) {
			const focusable = getFocusableElements();
			if (!focusable.length) {
				return;
			}

			const first = focusable[0];
			const last = focusable[focusable.length - 1];

			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		}

		function onViewportKeydown(event) {
			if (!hasMultiple) {
				return;
			}

			if (event.key === 'ArrowLeft') {
				event.preventDefault();
				showPrevious(false);
			}

			if (event.key === 'ArrowRight') {
				event.preventDefault();
				showNext(false);
			}
		}

		function onDialogKeydown(event) {
			if (event.key === 'Escape') {
				event.preventDefault();
				closeDialog();
				return;
			}

			if (event.key === 'Tab') {
				handleFocusTrap(event);
			}

			if (!hasMultiple) {
				return;
			}

			if (event.key === 'ArrowLeft') {
				event.preventDefault();
				showPrevious(false);
			}

			if (event.key === 'ArrowRight') {
				event.preventDefault();
				showNext(false);
			}
		}

		function onTouchStart(event) {
			if (!hasMultiple || !event.touches || event.touches.length !== 1) {
				return;
			}

			touchStartX = event.touches[0].clientX;
			touchStartY = event.touches[0].clientY;
		}

		function onTouchEnd(event) {
			let deltaX;
			let deltaY;

			if (!hasMultiple || touchStartX === null || touchStartY === null || !event.changedTouches || event.changedTouches.length !== 1) {
				return;
			}

			deltaX = event.changedTouches[0].clientX - touchStartX;
			deltaY = event.changedTouches[0].clientY - touchStartY;
			touchStartX = null;
			touchStartY = null;

			if (Math.abs(deltaX) < 36 || Math.abs(deltaX) <= Math.abs(deltaY)) {
				return;
			}

			if (deltaX > 0) {
				showPrevious(false);
				return;
			}

			showNext(false);
		}

		function bindImageEvents(image) {
			if (!image) {
				return;
			}

			if (image.complete) {
				updateViewportHeight();
			}

			image.addEventListener('load', updateViewportHeight);
			image.addEventListener('error', updateViewportHeight);
		}

		if (hasMultiple && prevButton && nextButton) {
			prevButton.addEventListener('click', function () {
				showPrevious(false);
			});

			nextButton.addEventListener('click', function () {
				showNext(false);
			});
		}

		dots.forEach(function (dot, dotIndex) {
			dot.addEventListener('click', function () {
				render(dotIndex, false);
			});

			dot.addEventListener('keydown', function (event) {
				if (event.key === 'ArrowLeft') {
					event.preventDefault();
					render(dotIndex === 0 ? slides.length - 1 : dotIndex - 1, true);
				}

				if (event.key === 'ArrowRight') {
					event.preventDefault();
					render(dotIndex === slides.length - 1 ? 0 : dotIndex + 1, true);
				}

				if (event.key === 'Home') {
					event.preventDefault();
					render(0, true);
				}

				if (event.key === 'End') {
					event.preventDefault();
					render(slides.length - 1, true);
				}
			});
		});

		openButtons.forEach(function (button) {
			button.addEventListener('click', function () {
				const index = Number(button.getAttribute('data-slide-index')) || 0;
				openDialog(index, button);
			});
		});

		dialogNavButtons.forEach(function (button) {
			button.addEventListener('click', function () {
				if (button.getAttribute('data-direction') === 'prev') {
					showPrevious(false);
					return;
				}

				showNext(false);
			});
		});

		slides.forEach(function (slide) {
			bindImageEvents(slide.querySelector('img'));
		});

		if (viewport) {
			viewport.addEventListener('keydown', onViewportKeydown);
			viewport.addEventListener('touchstart', onTouchStart, { passive: true });
			viewport.addEventListener('touchend', onTouchEnd, { passive: true });
		}

		if (dialogStage) {
			dialogStage.addEventListener('touchstart', onTouchStart, { passive: true });
			dialogStage.addEventListener('touchend', onTouchEnd, { passive: true });
		}

		if (dialog) {
			dialog.addEventListener('keydown', onDialogKeydown);

			dialog.addEventListener('click', function (event) {
				if (event.target === dialog) {
					closeDialog();
				}
			});

			dialog.addEventListener('cancel', function (event) {
				event.preventDefault();
				closeDialog();
			});
		}

		if (dialogCloseButton) {
			dialogCloseButton.addEventListener('click', closeDialog);
		}

		if (typeof ResizeObserver === 'function') {
			resizeObserver = new ResizeObserver(updateViewportHeight);
			slides.forEach(function (slide) {
				const image = slide.querySelector('img');
				if (image) {
					resizeObserver.observe(image);
				}
			});
		}

		window.addEventListener('resize', updateViewportHeight);

		if (reducedMotion && typeof reducedMotion.addEventListener === 'function') {
			reducedMotion.addEventListener('change', updateViewportHeight);
		}

		render(0, false);
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-avf-post-carousel]').forEach(initCarousel);
	});
}() );
