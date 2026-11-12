<?php
/**
 * Lightweight smoke tests for the AVF post templates plugin.
 *
 * Runs without a full WordPress bootstrap by stubbing the small subset
 * of functions used by the rendering classes.
 */

define( 'ABSPATH', __DIR__ . '/../../../../' );
define( 'AVF_POST_TEMPLATES_VERSION', '1.1.0-test' );
define( 'AVF_POST_TEMPLATES_DIR', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );
define( 'AVF_POST_TEMPLATES_URL', 'http://example.test/wp-content/plugins/avf-post-templates/' );

class WP_Post {
	public $ID;
	public $post_type;
	public $comment_status;
	public $ping_status;

	public function __construct( $id, $post_type = 'post', $comment_status = 'open', $ping_status = 'open' ) {
		$this->ID             = (int) $id;
		$this->post_type      = $post_type;
		$this->comment_status = $comment_status;
		$this->ping_status    = $ping_status;
	}
}

class WP_Query {
	public $posts = array();

	public function __construct( $args = array() ) {}
}

$GLOBALS['avf_test_post_meta'] = array();
$GLOBALS['avf_test_post_titles'] = array();
$GLOBALS['avf_test_post_dates'] = array();
$GLOBALS['avf_test_post_categories'] = array();
$GLOBALS['avf_test_post_thumbnails'] = array();
$GLOBALS['avf_test_attachment_captions'] = array();
$GLOBALS['avf_test_attachment_images'] = array();
$GLOBALS['avf_test_enqueued_styles'] = array();
$GLOBALS['avf_test_enqueued_scripts'] = array();
$GLOBALS['avf_test_posts'] = array();
$GLOBALS['avf_test_options'] = array();
$GLOBALS['avf_test_removed_meta_boxes'] = array();
$GLOBALS['avf_test_removed_supports'] = array();
$GLOBALS['avf_test_current_post_id'] = 0;
$GLOBALS['avf_test_current_post_type'] = 'post';
$GLOBALS['avf_test_is_singular'] = true;
$GLOBALS['avf_test_in_the_loop'] = true;
$GLOBALS['avf_test_is_main_query'] = true;

function add_shortcode() {}
function add_action() {}
function add_filter() {}
function remove_action() {}
function wp_enqueue_media() {}
function get_current_screen() { return (object) array( 'post_type' => 'post' ); }
function wp_nonce_field() {}
function checked() {}
function selected() {}
function current_user_can() { return true; }
function wp_verify_nonce() { return true; }
function sanitize_text_field( $value ) { return is_string( $value ) ? trim( $value ) : ''; }
function sanitize_textarea_field( $value ) { return is_string( $value ) ? trim( $value ) : ''; }
function sanitize_key( $value ) { return is_string( $value ) ? preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ) : ''; }
function wp_unslash( $value ) { return $value; }
function update_post_meta( $post_id, $key, $value ) { $GLOBALS['avf_test_post_meta'][ $post_id ][ $key ] = $value; }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr_e( $value ) { echo esc_attr( $value ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html_e( $value ) { echo esc_html( $value ); }
function esc_html__( $text ) { return $text; }
function esc_textarea( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function __( $text ) { return $text; }
function esc_url( $url ) { return $url; }
function wp_kses_post( $html ) { return (string) $html; }
function wp_editor() {}
function add_meta_box() {}
function remove_meta_box( $id, $screen, $context ) {
	$GLOBALS['avf_test_removed_meta_boxes'][] = array( $id, $screen, $context );
}
function remove_post_type_support( $post_type, $feature ) {
	$GLOBALS['avf_test_removed_supports'][] = $post_type . ':' . $feature;
}
function wpautop( $text ) {
	$text = trim( (string) $text );
	if ( '' === $text ) {
		return '';
	}

	return '<p>' . str_replace( "\n", "</p>\n<p>", $text ) . '</p>';
}
function shortcode_atts( $pairs, $atts, $shortcode = '' ) { return array_merge( $pairs, is_array( $atts ) ? $atts : array() ); }
function get_post_meta( $post_id, $key, $single = true ) { return $GLOBALS['avf_test_post_meta'][ $post_id ][ $key ] ?? ''; }
function get_the_title( $post_id ) { return $GLOBALS['avf_test_post_titles'][ $post_id ] ?? ''; }
function get_the_date( $format, $post_id ) { return $GLOBALS['avf_test_post_dates'][ $post_id ] ?? ''; }
function get_post_time( $format, $gmt = false, $post_id = 0 ) {
	if ( 'c' === $format ) {
		return '2026-07-27T00:00:00+00:00';
	}

	return 1785110400;
}
function wp_date( $format, $timestamp = null ) { return '27. Juli 2026'; }
function get_the_category( $post_id ) { return $GLOBALS['avf_test_post_categories'][ $post_id ] ?? array(); }
function wp_get_post_categories( $post_id, $args = array() ) { return array( 1 ); }
function has_post_thumbnail( $post_id ) { return isset( $GLOBALS['avf_test_post_thumbnails'][ $post_id ] ); }
function get_post_thumbnail_id( $post_id ) { return $GLOBALS['avf_test_post_thumbnails'][ $post_id ] ?? 0; }
function get_the_post_thumbnail( $post_id, $size, $attrs = array() ) {
	$class = isset( $attrs['class'] ) ? $attrs['class'] : '';
	return '<img class="' . esc_attr( $class ) . '" src="thumb-' . (int) $post_id . '.jpg" alt="">';
}
function wp_get_attachment_caption( $attachment_id ) { return $GLOBALS['avf_test_attachment_captions'][ $attachment_id ] ?? ''; }
function wp_get_attachment_image( $attachment_id, $size, $icon = false, $attrs = array() ) {
	$class = isset( $attrs['class'] ) ? $attrs['class'] : '';

	if ( empty( $GLOBALS['avf_test_attachment_images'][ $attachment_id ] ) ) {
		return '';
	}

	return '<img class="' . esc_attr( $class ) . '" src="attachment-' . (int) $attachment_id . '.jpg" alt="">';
}
function wp_get_attachment_image_src( $attachment_id, $size ) {
	if ( empty( $GLOBALS['avf_test_attachment_images'][ $attachment_id ] ) ) {
		return false;
	}

	if ( 'full' === $size ) {
		return array( 'full-' . (int) $attachment_id . '.jpg', 2200, 1460, true );
	}

	return array( 'preview-' . (int) $attachment_id . '.jpg', 1200, 800, true );
}
function wp_get_attachment_image_srcset( $attachment_id, $size ) {
	if ( empty( $GLOBALS['avf_test_attachment_images'][ $attachment_id ] ) ) {
		return '';
	}

	return 'preview-' . (int) $attachment_id . '-768.jpg 768w, preview-' . (int) $attachment_id . '.jpg 1200w';
}
function wp_get_attachment_image_sizes( $attachment_id, $size ) {
	if ( empty( $GLOBALS['avf_test_attachment_images'][ $attachment_id ] ) ) {
		return '';
	}

	return '(max-width: 800px) 100vw, 800px';
}
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false ) { $GLOBALS['avf_test_enqueued_styles'][] = $handle; }
function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) { $GLOBALS['avf_test_enqueued_scripts'][] = $handle; }
function absint( $value ) { return abs( (int) $value ); }
function wp_strip_all_tags( $value ) { return trim( strip_tags( (string) $value ) ); }
if ( ! function_exists( 'mb_strlen' ) ) {
	function mb_strlen( $value ) { return strlen( (string) $value ); }
}
function is_singular( $post_type = null ) {
	if ( false === $GLOBALS['avf_test_is_singular'] ) {
		return false;
	}

	return null === $post_type ? true : $GLOBALS['avf_test_current_post_type'] === $post_type;
}
function get_queried_object() {
	return get_post( $GLOBALS['avf_test_current_post_id'] );
}
function get_the_ID() {
	return $GLOBALS['avf_test_current_post_id'];
}
function in_the_loop() {
	return $GLOBALS['avf_test_in_the_loop'];
}
function is_main_query() {
	return $GLOBALS['avf_test_is_main_query'];
}
function get_option( $key ) {
	return $GLOBALS['avf_test_options'][ $key ] ?? false;
}
function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['avf_test_options'][ $key ] = $value;
	return true;
}
function get_posts( $args = array() ) {
	$ids = array();

	foreach ( $GLOBALS['avf_test_posts'] as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		if ( isset( $args['post_type'] ) && $post->post_type !== $args['post_type'] ) {
			continue;
		}

		if ( isset( $args['meta_key'], $args['meta_value'] ) ) {
			$meta_value = get_post_meta( $post->ID, $args['meta_key'], true );
			if ( $args['meta_value'] !== $meta_value ) {
				continue;
			}
		}

		$ids[] = $post->ID;
	}

	return $ids;
}
function get_post( $post_id ) {
	return $GLOBALS['avf_test_posts'][ $post_id ] ?? null;
}
function get_permalink( $post_id ) { return 'https://example.test/?p=' . (int) $post_id; }
function get_the_excerpt( $post_id ) { return 'Kurztext fuer verwandte Beitraege.'; }
function wp_trim_words( $text, $num_words = 55 ) { return trim( (string) $text ); }
function wp_update_post( $update ) {
	$post_id = isset( $update['ID'] ) ? (int) $update['ID'] : 0;
	if ( ! isset( $GLOBALS['avf_test_posts'][ $post_id ] ) ) {
		return 0;
	}

	if ( isset( $update['comment_status'] ) ) {
		$GLOBALS['avf_test_posts'][ $post_id ]->comment_status = $update['comment_status'];
	}

	if ( isset( $update['ping_status'] ) ) {
		$GLOBALS['avf_test_posts'][ $post_id ]->ping_status = $update['ping_status'];
	}

	return $post_id;
}
function wp_is_post_autosave() { return false; }
function wp_is_post_revision() { return false; }

require_once dirname( __DIR__ ) . '/includes/class-avf-post-carousel-shortcode.php';
require_once dirname( __DIR__ ) . '/includes/class-avf-post-templates.php';

function avf_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "Assertion failed: {$message}\n" );
		exit( 1 );
	}
}

$GLOBALS['avf_test_attachment_images'] = array(
	11 => true,
	12 => true,
	13 => true,
);

$GLOBALS['avf_test_attachment_captions'] = array(
	11 => 'Bildlegende Eins',
	12 => '',
	13 => 'Bildlegende Drei',
	99 => 'Titelbild Caption',
);

$GLOBALS['avf_test_post_meta'][11]['_wp_attachment_image_alt'] = 'Alt Eins';
$GLOBALS['avf_test_post_meta'][12]['_wp_attachment_image_alt'] = 'Alt Zwei';
$GLOBALS['avf_test_post_meta'][13]['_wp_attachment_image_alt'] = 'Alt Drei';

$post_id = 201;
$page_id = 202;
$legacy_post_id = 203;

$GLOBALS['avf_test_posts'][ $post_id ] = new WP_Post( $post_id, 'post', 'open', 'open' );
$GLOBALS['avf_test_posts'][ $page_id ] = new WP_Post( $page_id, 'page', 'open', 'open' );
$GLOBALS['avf_test_posts'][ $legacy_post_id ] = new WP_Post( $legacy_post_id, 'post', 'open', 'open' );

$GLOBALS['avf_test_current_post_id'] = $post_id;
$GLOBALS['avf_test_current_post_type'] = 'post';
$GLOBALS['avf_test_post_titles'][ $post_id ] = 'Langer Beispielbeitrag fuer das Editorial System';
$GLOBALS['avf_test_post_dates'][ $post_id ] = '27. Juli 2026';
$GLOBALS['avf_test_post_categories'][ $post_id ] = array(
	(object) array(
		'name' => 'Rueckblick',
	),
);
$GLOBALS['avf_test_post_thumbnails'][ $post_id ] = 99;
$GLOBALS['avf_test_post_meta'][ $post_id ] = array(
	AVF_Post_Templates::META_ENABLED    => '1',
	AVF_Post_Templates::META_LAYOUT     => 'hero',
	AVF_Post_Templates::META_INTRO      => 'Kurze Einleitung.',
	AVF_Post_Templates::META_BODY       => '<h2>Zwischentitel</h2><p style="text-align:right">Hauptinhalt</p><p>' . str_repeat( 'Lorem ipsum dolor sit amet. ', 60 ) . '</p>',
	AVF_Post_Templates::META_OUTRO      => 'Abschluss.',
	AVF_Post_Templates::META_GALLERY    => '11,12,13',
	AVF_Post_Templates::META_TEXT_ALIGN => '',
);
$GLOBALS['avf_test_post_meta'][ $legacy_post_id ] = array(
	AVF_Post_Templates::META_ENABLED => '0',
);

$carousel = AVF_Post_Carousel_Shortcode::instance();
$carousel_markup = $carousel->render_from_ids( array( 11, 12 ) );
$single_markup = $carousel->render_from_ids( array( 11 ) );

avf_assert( false !== strpos( $carousel_markup, 'avf-post-carousel__button' ), 'Carousel buttons should render for multiple images.' );
avf_assert( false !== strpos( $carousel_markup, 'avf-post-carousel__dialog' ), 'Lightbox dialog markup should render.' );
avf_assert( false !== strpos( $carousel_markup, 'loading="eager"' ), 'First carousel image should load eagerly.' );
avf_assert( false !== strpos( $carousel_markup, 'loading="lazy"' ), 'Non-primary images should lazy load.' );
avf_assert( false !== strpos( $carousel_markup, 'data-full-src="full-11.jpg"' ), 'Dialog slides should carry the full-size source URL.' );
avf_assert( false === strpos( $carousel_markup, 'avf-post-carousel__dialog-image' ), 'Full-size dialog images should not be rendered before the lightbox opens.' );
avf_assert( false !== strpos( $carousel_markup, 'is-empty' ), 'Empty captions should render with a stable empty state class.' );
avf_assert( false === strpos( $single_markup, 'avf-post-carousel__controls' ), 'Single-image carousels should not render slider controls.' );
avf_assert( false !== strpos( $single_markup, 'Einzelbild' ), 'Single-image carousels should expose dedicated status text.' );
avf_assert( in_array( 'avf-post-carousel', $GLOBALS['avf_test_enqueued_styles'], true ), 'Carousel stylesheet should be enqueued.' );
avf_assert( in_array( 'avf-post-carousel', $GLOBALS['avf_test_enqueued_scripts'], true ), 'Carousel script should be enqueued.' );

$templates = AVF_Post_Templates::instance();
$build_markup = new ReflectionMethod( 'AVF_Post_Templates', 'build_frontend_markup' );
$build_markup->setAccessible( true );
$adjust_meta_boxes = new ReflectionMethod( 'AVF_Post_Templates', 'adjust_discussion_meta_boxes' );
$adjust_meta_boxes->setAccessible( true );

$templates->disable_post_discussion_support();
avf_assert( in_array( 'post:comments', $GLOBALS['avf_test_removed_supports'], true ), 'Post comment support should be removed.' );
avf_assert( in_array( 'post:trackbacks', $GLOBALS['avf_test_removed_supports'], true ), 'Post trackback support should be removed.' );

$hero_markup = $build_markup->invoke( $templates, $post_id );
avf_assert( false !== strpos( $hero_markup, '<article class="avf-structured-post avf-structured-post--hero"' ), 'Structured posts should render as an article.' );
avf_assert( false === strpos( $hero_markup, 'avf-has-zirkel-bg' ), 'Structured post articles should no longer carry the local zirkel background class.' );
avf_assert( false !== strpos( $hero_markup, 'avf-post-content--align-justify' ), 'Structured posts should default to justified text.' );
avf_assert( false !== strpos( $hero_markup, 'avf-structured-post__hero-copy' ), 'Hero-specific wrapper should render.' );
avf_assert( false !== strpos( $hero_markup, 'avf-structured-post__body--journal-ready' ), 'Justified text should enable the journal body class.' );
avf_assert( false === strpos( $hero_markup, 'text-align:right' ), 'Inline text alignment should be removed from rendered body markup.' );
avf_assert( false !== strpos( $hero_markup, '27. Juli 2026' ), 'Hero metadata should render a prose-friendly date.' );
avf_assert( false !== strpos( $hero_markup, 'Min. Lesezeit' ), 'Hero metadata should expose the estimated reading time.' );

$GLOBALS['avf_test_post_meta'][ $post_id ][ AVF_Post_Templates::META_LAYOUT ] = 'split';
$legacy_split_markup = $build_markup->invoke( $templates, $post_id );
avf_assert( false !== strpos( $legacy_split_markup, '<article class="avf-structured-post avf-structured-post--hero"' ), 'Legacy split layouts should fall back to hero.' );
avf_assert( false !== strpos( $legacy_split_markup, 'avf-structured-post__hero-copy' ), 'Legacy split layouts should render the hero wrapper.' );

$GLOBALS['avf_test_post_meta'][ $post_id ][ AVF_Post_Templates::META_LAYOUT ] = 'carousel';
$GLOBALS['avf_test_post_meta'][ $post_id ][ AVF_Post_Templates::META_TEXT_ALIGN ] = 'justify';
$carousel_post_markup = $build_markup->invoke( $templates, $post_id );
avf_assert( false !== strpos( $carousel_post_markup, 'avf-structured-post__media--carousel' ), 'Carousel layout should render the carousel stage.' );
avf_assert( false !== strpos( $carousel_post_markup, 'Bildergalerie' ), 'Carousel stage label should be present.' );
avf_assert( false !== strpos( $carousel_post_markup, 'avf-post-content--align-justify' ), 'Justified alignment should produce the dedicated frontend class.' );

$GLOBALS['avf_test_post_meta'][ $post_id ][ AVF_Post_Templates::META_TEXT_ALIGN ] = 'not-valid';
$fallback_markup = $build_markup->invoke( $templates, $post_id );
avf_assert( false !== strpos( $fallback_markup, 'avf-post-content--align-justify' ), 'Invalid alignment values should fall back to justified text.' );
avf_assert( false !== strpos( $fallback_markup, 'avf-structured-post__body--journal-ready' ), 'Fallback justified text should still enable the journal body class.' );

$GLOBALS['avf_test_post_meta'][ $post_id ][ AVF_Post_Templates::META_TEXT_ALIGN ] = 'left';
$left_aligned_markup = $build_markup->invoke( $templates, $post_id );
avf_assert( false !== strpos( $left_aligned_markup, 'avf-post-content--align-left' ), 'Explicit left alignment should render the dedicated frontend class.' );
avf_assert( false === strpos( $left_aligned_markup, 'avf-structured-post__body--journal-ready' ), 'Left-aligned text should not force the journal body class.' );

unset( $GLOBALS['avf_test_post_thumbnails'][ $post_id ] );
$GLOBALS['avf_test_post_meta'][ $post_id ][ AVF_Post_Templates::META_LAYOUT ] = 'hero';
$no_image_markup = $build_markup->invoke( $templates, $post_id );
avf_assert( false === strpos( $no_image_markup, 'thumb-201.jpg' ), 'Missing featured images should be handled without broken markup.' );

avf_assert( false === $templates->filter_comments_open( true, $post_id ), 'Structured posts should always report comments as closed.' );
avf_assert( false === $templates->filter_comments_open( true, $legacy_post_id ), 'Regular posts without the guided layout should also report comments as closed.' );
avf_assert( false === $templates->filter_pings_open( true, $post_id ), 'Structured posts should always report pings as closed.' );
avf_assert( false === $templates->filter_pings_open( true, $legacy_post_id ), 'Regular posts without the guided layout should also report pings as closed.' );
avf_assert( true === $templates->filter_comments_open( true, $page_id ), 'Other post types should keep their original comment state.' );
avf_assert( array() === $templates->filter_comments_array( array( 'comment' ), get_post( $post_id ) ), 'Structured posts should suppress all frontend comments.' );
avf_assert( array() === $templates->filter_comments_array( array( 'comment' ), get_post( $legacy_post_id ) ), 'Regular posts should suppress all frontend comments.' );
avf_assert( array( 'comment' ) === $templates->filter_comments_array( array( 'comment' ), get_post( $page_id ) ), 'Other post types should keep their comments array.' );
avf_assert( 0 === $templates->filter_comments_number( 7, $post_id ), 'Structured posts should expose zero comments.' );
avf_assert( 0 === $templates->filter_comments_number( 7, $legacy_post_id ), 'Regular posts should expose zero comments.' );
avf_assert( 7 === $templates->filter_comments_number( 7, $page_id ), 'Other post types should keep their comment count.' );

$comments_template = $templates->filter_comments_template( 'theme-comments.php' );
avf_assert( false !== strpos( $comments_template, 'empty-comments.php' ), 'Posts should use the empty comments template.' );

$body_classes = $templates->filter_body_classes( array( 'single-post' ) );
avf_assert( in_array( 'avf-structured-post-page', $body_classes, true ), 'Structured posts should opt the body into the page-wide wrapper class.' );
avf_assert( ! in_array( 'avf-has-zirkel-bg', $body_classes, true ), 'Hero posts should no longer opt the body into the global zirkel background class.' );
avf_assert( false === $templates->filter_theme_page_title( true ), 'Structured posts should suppress the Hello Elementor page title.' );

$GLOBALS['avf_test_post_meta'][ $post_id ][ AVF_Post_Templates::META_LAYOUT ] = 'carousel';
$carousel_body_classes = $templates->filter_body_classes( array( 'single-post' ) );
avf_assert( in_array( 'avf-has-zirkel-bg', $carousel_body_classes, true ), 'Non-hero structured posts should keep the global zirkel background class.' );

$adjust_meta_boxes->invoke( $templates, get_post( $post_id ) );
avf_assert( 3 === count( $GLOBALS['avf_test_removed_meta_boxes'] ), 'Editorial posts should remove discussion-related meta boxes in the editor.' );

$insert_data = $templates->filter_insert_post_data(
	array(
		'post_type'      => 'post',
		'comment_status' => 'open',
		'ping_status'    => 'open',
	),
	array()
);
avf_assert( 'closed' === $insert_data['comment_status'], 'New posts should default to closed comments.' );
avf_assert( 'closed' === $insert_data['ping_status'], 'New posts should default to closed pings.' );

$_POST = array(
	'avf_post_template_nonce'       => 'nonce',
	'avf_post_template_enabled'     => '1',
	'avf_post_template_layout'      => 'hero',
	'avf_post_template_text_align'  => 'justify',
	'avf_post_template_intro'       => 'Lead',
	'avf_post_template_body'        => '<p>Body</p>',
	'avf_post_template_outro'       => 'Outro',
	'avf_post_template_gallery_ids' => '11,12',
);

$templates->save_post( $post_id );
avf_assert( 'closed' === $GLOBALS['avf_test_posts'][ $post_id ]->comment_status, 'Saving a structured post should close comments.' );
avf_assert( 'closed' === $GLOBALS['avf_test_posts'][ $post_id ]->ping_status, 'Saving a structured post should close pings.' );
avf_assert( 'justify' === $GLOBALS['avf_test_post_meta'][ $post_id ][ AVF_Post_Templates::META_TEXT_ALIGN ], 'Selected text alignment should be stored.' );

$_POST['avf_post_template_text_align'] = '';
$templates->save_post( $post_id );
avf_assert( 'justify' === $GLOBALS['avf_test_post_meta'][ $post_id ][ AVF_Post_Templates::META_TEXT_ALIGN ], 'Empty alignment values should fall back to justified text on save.' );

$templates->maybe_migrate_structured_post_discussion_state();
avf_assert( 'closed' === $GLOBALS['avf_test_posts'][ $legacy_post_id ]->comment_status, 'Legacy posts should be migrated to closed comments.' );
avf_assert( 'closed' === $GLOBALS['avf_test_posts'][ $legacy_post_id ]->ping_status, 'Legacy posts should be migrated to closed pings.' );
avf_assert( '1' === $GLOBALS['avf_test_options'][ AVF_Post_Templates::OPTION_MIGRATED ], 'Migration should persist its completion flag.' );

echo "All editorial smoke tests passed.\n";
