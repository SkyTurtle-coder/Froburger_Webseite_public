( function () {
	'use strict';

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

		} );

		archive.setAttribute( 'data-avf-post-archive-ready', 'true' );
	}

	function init() {
		var archives = document.querySelectorAll( '[data-avf-post-archive]' );

		archives.forEach( initPostArchive );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );
