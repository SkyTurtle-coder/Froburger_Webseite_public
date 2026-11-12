( function ( $, wp ) {
	'use strict';

	function updateEnabledState($container) {
		const isEnabled = $container.find('input[name="avf_post_template_enabled"]').is(':checked');
		$container.find('[data-avf-post-template-panel]').toggleClass('is-enabled', isEnabled);
	}

	function updateLayoutState($container) {
		const layout = $container.find('select[name="avf_post_template_layout"]').val() || 'hero';
		$container.find('[data-avf-post-template-gallery]').attr('data-layout', layout);
	}

	function renderPreview($preview, attachments) {
		if (!attachments.length) {
			$preview.html('<p class="description">Noch keine Bilder ausgewaehlt.</p>');
			return;
		}

		const items = attachments.map((attachment) => {
			const thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
			return '<div class="avf-post-template-editor__gallery-item"><img src="' + thumb + '" alt=""></div>';
		});

		$preview.html('<div class="avf-post-template-editor__gallery-grid">' + items.join('') + '</div>');
	}

	$( function () {
		const $container = $('.avf-post-template-editor');

		if (!$container.length || !wp || !wp.media) {
			return;
		}

		const $input = $container.find('[data-avf-post-template-gallery-input]');
		const $preview = $container.find('[data-avf-post-template-gallery-preview]');
		let frame = null;

		updateEnabledState($container);
		updateLayoutState($container);

		$container.on('change', 'input[name="avf_post_template_enabled"]', function () {
			updateEnabledState($container);
		});

		$container.on('change', 'select[name="avf_post_template_layout"]', function () {
			updateLayoutState($container);
		});

		$container.on('click', '[data-avf-post-template-select-gallery]', function (event) {
			event.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: 'Galeriebilder auswaehlen',
				button: {
					text: 'Bilder uebernehmen'
				},
				multiple: true,
				library: {
					type: 'image'
				}
			});

			frame.on('select', function () {
				const selection = frame.state().get('selection').toJSON();
				const ids = selection.map((attachment) => attachment.id);

				$input.val(ids.join(','));
				renderPreview($preview, selection);
			});

			frame.open();
		});

		$container.on('click', '[data-avf-post-template-clear-gallery]', function (event) {
			event.preventDefault();
			$input.val('');
			renderPreview($preview, []);
		});
	} );
}( jQuery, window.wp ) );
