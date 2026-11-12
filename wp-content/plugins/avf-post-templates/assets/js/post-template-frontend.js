( function () {
	'use strict';

	var titleFitTimeout = null;
	var TITLE_MAX_SIZE = 15.68; // 0.98rem at 16px root.
	var TITLE_MIN_SIZE = 11.2; // 0.7rem safeguard.
	var TITLE_STEP = 0.32; // 0.02rem.

	function resetTitleStyles( title ) {
		title.style.fontSize = TITLE_MAX_SIZE + 'px';
		title.classList.remove( 'is-multiword' );
	}

	function fitSingleWordTitle( title ) {
		var fontSize = TITLE_MAX_SIZE;

		while ( title.scrollWidth > title.clientWidth && fontSize > TITLE_MIN_SIZE ) {
			fontSize -= TITLE_STEP;
			title.style.fontSize = fontSize + 'px';
		}
	}

	function fitMultiWordTitle( title ) {
		var fontSize = TITLE_MAX_SIZE;
		var computedStyle = window.getComputedStyle( title );
		var lineHeight = parseFloat( computedStyle.lineHeight ) || fontSize * 1.16;
		var maxLines = parseInt( computedStyle.getPropertyValue( '--avf-card-title-max-lines' ), 10 ) || 2;
		var maxHeight;

		title.classList.add( 'is-multiword' );
		maxHeight = lineHeight * maxLines;

		while ( title.scrollHeight > maxHeight + 1 && fontSize > TITLE_MIN_SIZE ) {
			fontSize -= TITLE_STEP;
			title.style.fontSize = fontSize + 'px';
			lineHeight = parseFloat( window.getComputedStyle( title ).lineHeight ) || fontSize * 1.16;
			maxHeight = lineHeight * maxLines;
		}
	}

	function fitRecommendationTitles( scope ) {
		var root = scope || document;
		var titles = root.querySelectorAll( '.avf-post-recommendations__card-title' );

		titles.forEach( function ( title ) {
			var text = title.textContent || '';
			var hasMultipleWords = /\s+/.test( text.trim() );

			if ( title.clientWidth <= 0 ) {
				return;
			}

			resetTitleStyles( title );

			if ( hasMultipleWords ) {
				fitMultiWordTitle( title );
				return;
			}

			fitSingleWordTitle( title );
		} );
	}

	function scheduleTitleFit( scope ) {
		if ( titleFitTimeout ) {
			window.clearTimeout( titleFitTimeout );
		}

		titleFitTimeout = window.setTimeout( function () {
			fitRecommendationTitles( scope );
		}, 40 );
	}

	function updateArchiveState( archive, button, actions, status, visibleCount, totalCount ) {
		var hasMore = visibleCount < totalCount;
		var initialCount = parseInt( archive.getAttribute( 'data-initial-count' ), 10 ) || 0;

		if ( status ) {
			status.textContent = visibleCount + ' Beitraege sichtbar';
		}

		if ( button ) {
			button.setAttribute( 'aria-expanded', visibleCount > initialCount ? 'true' : 'false' );
		}

		if ( actions ) {
			actions.hidden = ! hasMore;
		}

		archive.setAttribute( 'data-visible-count', String( visibleCount ) );
	}

	function initPostArchive( archive ) {
		var initialCount;
		var stepCount;
		var items;
		var button;
		var actions;
		var status;

		if ( archive.getAttribute( 'data-avf-post-archive-ready' ) === 'true' ) {
			return;
		}

		initialCount = parseInt( archive.getAttribute( 'data-initial-count' ), 10 );
		stepCount = parseInt( archive.getAttribute( 'data-step' ), 10 );
		items = Array.prototype.slice.call(
			archive.querySelectorAll( '[data-avf-post-archive-item]' )
		);
		button = archive.querySelector( '[data-avf-post-archive-button]' );
		actions = archive.querySelector( '.avf-post-archive__actions' );
		status = archive.querySelector( '[data-avf-post-archive-status]' );

		if ( ! initialCount || initialCount < 1 ) {
			initialCount = 6;
		}

		if ( ! stepCount || stepCount < 1 ) {
			stepCount = initialCount;
		}

		items.forEach( function ( item, index ) {
			item.hidden = index >= initialCount;
		} );

		updateArchiveState(
			archive,
			button,
			actions,
			status,
			Math.min( items.length, initialCount ),
			items.length
		);

		if ( ! button || items.length <= initialCount ) {
			scheduleTitleFit( archive );
			archive.setAttribute( 'data-avf-post-archive-ready', 'true' );
			return;
		}

		button.addEventListener( 'click', function () {
			var hiddenItems = items.filter( function ( item ) {
				return item.hidden;
			} );
			var shouldMoveFocus = document.activeElement === button;
			var revealedItems;
			var firstFocusable = null;

			if ( hiddenItems.length === 0 ) {
				updateArchiveState( archive, button, actions, status, items.length, items.length );
				return;
			}

			revealedItems = hiddenItems.slice( 0, stepCount );

			revealedItems.forEach( function ( item ) {
				item.hidden = false;
				item.classList.add( 'is-entering' );

				window.setTimeout( function () {
					item.classList.remove( 'is-entering' );
				}, 260 );
			} );

			updateArchiveState(
				archive,
				button,
				actions,
				status,
				items.length - hiddenItems.length + revealedItems.length,
				items.length
			);

			if ( actions && actions.hidden && shouldMoveFocus ) {
				firstFocusable = revealedItems[0]
					? revealedItems[0].querySelector( 'a[href]' )
					: null;

				if ( firstFocusable ) {
					firstFocusable.focus();
				}
			}

			scheduleTitleFit( archive );
		} );

		scheduleTitleFit( archive );
		archive.setAttribute( 'data-avf-post-archive-ready', 'true' );
	}

	function init() {
		var archives = document.querySelectorAll( '[data-avf-post-archive]' );

		archives.forEach( initPostArchive );
		scheduleTitleFit( document );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	window.addEventListener( 'resize', function () {
		scheduleTitleFit( document );
	} );
}() );
