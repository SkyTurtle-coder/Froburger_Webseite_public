<?php
/**
 * Registers and renders an editorial image carousel.
 *
 * @package AVF_Post_Templates
 */

defined( 'ABSPATH' ) || exit;

class AVF_Post_Carousel_Shortcode {

	/**
	 * Singleton instance.
	 *
	 * @var AVF_Post_Carousel_Shortcode|null
	 */
	private static $instance = null;

	/**
	 * Incrementing instance counter for unique DOM ids.
	 *
	 * @var int
	 */
	private $instance_count = 0;

	/**
	 * Returns singleton instance.
	 *
	 * @return AVF_Post_Carousel_Shortcode
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks shortcode registration.
	 */
	private function __construct() {
		add_shortcode( 'avf_post_carousel', array( $this, 'render' ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'ids' => '',
			),
			$atts,
			'avf_post_carousel'
		);

		$ids = $this->parse_ids( $atts['ids'] );
		return $this->render_from_ids( $ids );
	}

	/**
	 * Renders a carousel directly from attachment ids.
	 *
	 * @param int[] $ids Attachment ids.
	 * @return string
	 */
	public function render_from_ids( array $ids ) {
		$slides = $this->build_slides( $ids );
		if ( empty( $slides ) ) {
			return '';
		}

		$this->enqueue_assets();

		$this->instance_count++;
		$carousel_id  = 'avf-post-carousel-' . $this->instance_count;
		$dialog_id    = $carousel_id . '-dialog';
		$status_id    = $carousel_id . '-status';
		$label_id     = $carousel_id . '-label';
		$caption_id   = $carousel_id . '-caption';
		$has_multiple = count( $slides ) > 1;

		ob_start();
		?>
		<section
			class="avf-post-carousel<?php echo $has_multiple ? '' : ' avf-post-carousel--single'; ?>"
			id="<?php echo esc_attr( $carousel_id ); ?>"
			data-avf-post-carousel
			data-dialog-id="<?php echo esc_attr( $dialog_id ); ?>"
			aria-labelledby="<?php echo esc_attr( $label_id ); ?>"
			aria-describedby="<?php echo esc_attr( $status_id ); ?>"
		>
			<div class="avf-post-carousel__shell">
				<div class="avf-post-carousel__header">
					<p class="avf-post-carousel__eyebrow" id="<?php echo esc_attr( $label_id ); ?>">
						<?php esc_html_e( 'Bildergalerie', 'avf-post-templates' ); ?>
					</p>
					<p class="avf-post-carousel__status" id="<?php echo esc_attr( $status_id ); ?>" aria-live="polite">
						<?php
						echo esc_html(
							$has_multiple
								? sprintf( __( 'Bild %1$d von %2$d', 'avf-post-templates' ), 1, count( $slides ) )
								: __( 'Einzelbild', 'avf-post-templates' )
						);
						?>
					</p>
				</div>

				<div class="avf-post-carousel__viewport" tabindex="0">
					<div class="avf-post-carousel__track">
						<?php foreach ( $slides as $index => $slide ) : ?>
							<figure
								class="avf-post-carousel__slide<?php echo 0 === $index ? ' is-active' : ''; ?>"
								data-slide-index="<?php echo esc_attr( (string) $index ); ?>"
								aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>"
								<?php if ( 0 === $index ) : ?>
									data-active="true"
								<?php endif; ?>
							>
								<div class="avf-post-carousel__frame">
									<button
										type="button"
										class="avf-post-carousel__trigger"
										data-avf-carousel-open
										data-slide-index="<?php echo esc_attr( (string) $index ); ?>"
										aria-haspopup="dialog"
										aria-controls="<?php echo esc_attr( $dialog_id ); ?>"
										aria-label="<?php echo esc_attr( sprintf( __( 'Bild %d vergrössern', 'avf-post-templates' ), $index + 1 ) ); ?>"
									>
										<?php echo $slide['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</button>
								</div>
								<figcaption class="avf-post-carousel__caption<?php echo '' === $slide['caption'] ? ' is-empty' : ''; ?>" id="<?php echo esc_attr( $caption_id . '-' . $index ); ?>">
									<?php if ( '' !== $slide['caption'] ) : ?>
										<?php echo esc_html( $slide['caption'] ); ?>
									<?php else : ?>
										<span class="screen-reader-text"><?php esc_html_e( 'Keine Bildunterschrift vorhanden', 'avf-post-templates' ); ?></span>
									<?php endif; ?>
								</figcaption>
							</figure>
						<?php endforeach; ?>
					</div>
				</div>

				<?php if ( $has_multiple ) : ?>
					<div class="avf-post-carousel__controls">
						<button type="button" class="avf-post-carousel__button" data-direction="prev" aria-label="<?php esc_attr_e( 'Vorheriges Bild', 'avf-post-templates' ); ?>">
							<span aria-hidden="true">&#8249;</span>
						</button>
						<div class="avf-post-carousel__dots" role="tablist" aria-label="<?php esc_attr_e( 'Bildauswahl', 'avf-post-templates' ); ?>">
							<?php foreach ( $slides as $index => $slide ) : ?>
								<button
									type="button"
									class="avf-post-carousel__dot<?php echo 0 === $index ? ' is-active' : ''; ?>"
									data-slide-index="<?php echo esc_attr( (string) $index ); ?>"
									role="tab"
									aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
									aria-controls="<?php echo esc_attr( $carousel_id ); ?>"
									aria-label="<?php echo esc_attr( sprintf( __( 'Bild %d anzeigen', 'avf-post-templates' ), $index + 1 ) ); ?>"
								></button>
							<?php endforeach; ?>
						</div>
						<button type="button" class="avf-post-carousel__button" data-direction="next" aria-label="<?php esc_attr_e( 'Nächstes Bild', 'avf-post-templates' ); ?>">
							<span aria-hidden="true">&#8250;</span>
						</button>
					</div>
				<?php endif; ?>
			</div>

			<dialog class="avf-post-carousel__dialog" id="<?php echo esc_attr( $dialog_id ); ?>" aria-labelledby="<?php echo esc_attr( $label_id ); ?>" aria-describedby="<?php echo esc_attr( $caption_id ); ?>">
				<div class="avf-post-carousel__dialog-inner">
					<button type="button" class="avf-post-carousel__dialog-close" data-avf-carousel-close aria-label="<?php esc_attr_e( 'Grossansicht schliessen', 'avf-post-templates' ); ?>">
						<span aria-hidden="true">&times;</span>
					</button>
					<?php if ( $has_multiple ) : ?>
						<button type="button" class="avf-post-carousel__dialog-nav avf-post-carousel__dialog-nav--prev" data-direction="prev" data-avf-carousel-dialog-nav aria-label="<?php esc_attr_e( 'Vorheriges Bild', 'avf-post-templates' ); ?>">
							<span aria-hidden="true">&#8249;</span>
						</button>
						<button type="button" class="avf-post-carousel__dialog-nav avf-post-carousel__dialog-nav--next" data-direction="next" data-avf-carousel-dialog-nav aria-label="<?php esc_attr_e( 'Nächstes Bild', 'avf-post-templates' ); ?>">
							<span aria-hidden="true">&#8250;</span>
						</button>
					<?php endif; ?>
					<figure class="avf-post-carousel__dialog-figure">
						<div class="avf-post-carousel__dialog-stage" data-avf-carousel-dialog-stage>
							<?php foreach ( $slides as $index => $slide ) : ?>
								<div
									class="avf-post-carousel__dialog-slide<?php echo 0 === $index ? ' is-active' : ''; ?>"
									data-slide-index="<?php echo esc_attr( (string) $index ); ?>"
									data-full-src="<?php echo esc_attr( $slide['full_src'] ); ?>"
									data-full-alt="<?php echo esc_attr( $slide['alt'] ); ?>"
									data-full-width="<?php echo esc_attr( (string) $slide['full_width'] ); ?>"
									data-full-height="<?php echo esc_attr( (string) $slide['full_height'] ); ?>"
									aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>"
								>
									<div class="avf-post-carousel__dialog-placeholder" aria-hidden="true"></div>
								</div>
							<?php endforeach; ?>
						</div>
						<figcaption class="avf-post-carousel__dialog-caption" id="<?php echo esc_attr( $caption_id ); ?>" data-avf-carousel-dialog-caption>
							<?php echo esc_html( $slides[0]['caption'] ); ?>
						</figcaption>
					</figure>
				</div>
			</dialog>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Parses a comma-separated ids list.
	 *
	 * @param mixed $raw_ids Raw shortcode value.
	 * @return int[]
	 */
	private function parse_ids( $raw_ids ) {
		if ( ! is_string( $raw_ids ) || '' === trim( $raw_ids ) ) {
			return array();
		}

		$ids = array_map( 'trim', explode( ',', $raw_ids ) );
		$ids = array_map( 'absint', $ids );
		$ids = array_filter( $ids );

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Builds slide payloads from attachment ids.
	 *
	 * @param int[] $ids Attachment ids.
	 * @return array<int, array<string, int|string>>
	 */
	private function build_slides( array $ids ) {
		$slides = array();

		foreach ( $ids as $index => $id ) {
			$preview = $this->get_attachment_image_payload( $id, 'large' );
			$full    = $this->get_attachment_image_payload( $id, 'full' );

			if ( empty( $preview['src'] ) || empty( $full['src'] ) ) {
				continue;
			}

			$caption = wp_get_attachment_caption( $id );
			$caption = is_string( $caption ) ? trim( wp_strip_all_tags( $caption ) ) : '';

			$slides[] = array(
				'image'       => $this->build_preview_image_tag( $preview, 0 === $index ),
				'caption'     => $caption,
				'alt'         => isset( $full['alt'] ) ? $full['alt'] : '',
				'full_src'    => isset( $full['src'] ) ? $full['src'] : '',
				'full_width'  => isset( $full['width'] ) ? (int) $full['width'] : 0,
				'full_height' => isset( $full['height'] ) ? (int) $full['height'] : 0,
			);
		}

		return $slides;
	}

	/**
	 * Returns responsive image data for one attachment and size.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $size          Requested image size.
	 * @return array<string, int|string>
	 */
	private function get_attachment_image_payload( $attachment_id, $size ) {
		$src = wp_get_attachment_image_src( $attachment_id, $size );
		if ( ! is_array( $src ) || empty( $src[0] ) ) {
			return array();
		}

		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$alt = is_string( $alt ) ? trim( $alt ) : '';

		$payload = array(
			'src'    => $src[0],
			'width'  => isset( $src[1] ) ? (int) $src[1] : 0,
			'height' => isset( $src[2] ) ? (int) $src[2] : 0,
			'alt'    => $alt,
		);

		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$srcset = wp_get_attachment_image_srcset( $attachment_id, $size );
			if ( is_string( $srcset ) && '' !== $srcset ) {
				$payload['srcset'] = $srcset;
			}
		}

		if ( function_exists( 'wp_get_attachment_image_sizes' ) ) {
			$sizes = wp_get_attachment_image_sizes( $attachment_id, $size );
			if ( is_string( $sizes ) && '' !== $sizes ) {
				$payload['sizes'] = $sizes;
			}
		}

		return $payload;
	}

	/**
	 * Builds the preview image tag used inside the inline carousel.
	 *
	 * @param array<string, int|string> $payload    Prepared preview image data.
	 * @param bool                      $is_primary Whether this is the first slide.
	 * @return string
	 */
	private function build_preview_image_tag( array $payload, $is_primary ) {
		$attributes = array(
			'class'         => 'avf-post-carousel__image',
			'src'           => isset( $payload['src'] ) ? $payload['src'] : '',
			'alt'           => isset( $payload['alt'] ) ? $payload['alt'] : '',
			'width'         => isset( $payload['width'] ) ? (string) $payload['width'] : '',
			'height'        => isset( $payload['height'] ) ? (string) $payload['height'] : '',
			'decoding'      => 'async',
			'fetchpriority' => $is_primary ? 'high' : 'auto',
			'loading'       => $is_primary ? 'eager' : 'lazy',
		);

		if ( ! empty( $payload['srcset'] ) ) {
			$attributes['srcset'] = $payload['srcset'];
		}

		if ( ! empty( $payload['sizes'] ) ) {
			$attributes['sizes'] = $payload['sizes'];
		}

		return $this->build_image_tag( $attributes );
	}

	/**
	 * Builds a generic image tag from an attributes array.
	 *
	 * @param array<string, string> $attributes Image attributes.
	 * @return string
	 */
	private function build_image_tag( array $attributes ) {
		$parts = array();

		foreach ( $attributes as $name => $value ) {
			if ( '' === $value ) {
				continue;
			}

			$parts[] = sprintf( '%s="%s"', $name, esc_attr( $value ) );
		}

		return '<img ' . implode( ' ', $parts ) . '>';
	}

	/**
	 * Enqueues frontend assets on demand.
	 *
	 * @return void
	 */
	private function enqueue_assets() {
		$css_path = AVF_POST_TEMPLATES_DIR . 'assets/css/post-carousel.css';
		$js_path  = AVF_POST_TEMPLATES_DIR . 'assets/js/post-carousel.js';
		$version  = AVF_POST_TEMPLATES_VERSION;

		if ( file_exists( $css_path ) || file_exists( $js_path ) ) {
			$version = (string) max(
				file_exists( $css_path ) ? filemtime( $css_path ) : 0,
				file_exists( $js_path ) ? filemtime( $js_path ) : 0
			);
		}

		wp_enqueue_style(
			'avf-post-carousel',
			AVF_POST_TEMPLATES_URL . 'assets/css/post-carousel.css',
			array(),
			$version
		);

		wp_enqueue_script(
			'avf-post-carousel',
			AVF_POST_TEMPLATES_URL . 'assets/js/post-carousel.js',
			array(),
			$version,
			true
		);
	}
}
