<?php
/**
 * Provides a guided editorial workflow for standardised posts.
 *
 * @package AVF_Post_Templates
 */

defined( 'ABSPATH' ) || exit;

class AVF_Post_Templates {

	const META_ENABLED    = '_avf_post_template_enabled';
	const META_LAYOUT     = '_avf_post_template_layout';
	const META_INTRO      = '_avf_post_template_intro';
	const META_BODY       = '_avf_post_template_body';
	const META_OUTRO      = '_avf_post_template_outro';
	const META_GALLERY    = '_avf_post_template_gallery_ids';
	const META_TEXT_ALIGN = '_avf_post_template_text_align';
	const OPTION_MIGRATED = 'avf_post_templates_discussion_migrated_v2';
	const AKTUELLES_PAGE_ID          = 26;
	const AKTUELLES_PAGE_SLUG        = 'aktuelles';
	const AKTUELLES_LOOP_TEMPLATE_ID = '311';
	const AKTUELLES_LOOP_WIDGET_ID   = 'bbb862b';
	const ARCHIVE_INITIAL_COUNT      = 6;
	const ARCHIVE_STEP_COUNT         = 6;
	/**
	 * Singleton instance.
	 *
	 * @var AVF_Post_Templates|null
	 */
	private static $instance = null;

	/**
	 * Returns singleton instance.
	 *
	 * @return AVF_Post_Templates
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks registration callbacks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'disable_post_discussion_support' ), 20 );
		add_action( 'init', array( $this, 'maybe_migrate_structured_post_discussion_state' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'add_meta_boxes_post', array( $this, 'adjust_discussion_meta_boxes' ), 20 );
		add_action( 'save_post_post', array( $this, 'save_post' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_archive_assets' ) );
		add_filter( 'the_content', array( $this, 'render_structured_post_content' ) );
		add_filter( 'body_class', array( $this, 'filter_body_classes' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'filter_insert_post_data' ), 10, 2 );
		add_filter( 'comments_open', array( $this, 'filter_comments_open' ), 20, 2 );
		add_filter( 'pings_open', array( $this, 'filter_pings_open' ), 20, 2 );
		add_filter( 'comments_array', array( $this, 'filter_comments_array' ), 20, 2 );
		add_filter( 'get_comments_number', array( $this, 'filter_comments_number' ), 20, 2 );
		add_filter( 'comments_template', array( $this, 'filter_comments_template' ) );
		add_filter( 'hello_elementor_page_title', array( $this, 'filter_theme_page_title' ) );
		add_filter( 'elementor/widget/render_content', array( $this, 'replace_aktuelles_loop_grid_widget' ), 20, 2 );
	}

	/**
	 * Returns available layout options.
	 *
	 * @return array<string, string>
	 */
	private function get_layout_options() {
		return array(
			'hero'     => __( 'Titelbild oben', 'avf-post-templates' ),
			'carousel' => __( 'Bilderkarussell', 'avf-post-templates' ),
		);
	}

	/**
	 * Returns text alignment options.
	 *
	 * @return array<string, string>
	 */
	private function get_text_alignment_options() {
		return array(
			'left'    => __( 'Linksbündig', 'avf-post-templates' ),
			'justify' => __( 'Blocksatz', 'avf-post-templates' ),
		);
	}

	/**
	 * Disables native discussion supports for regular posts.
	 *
	 * @return void
	 */
	public function disable_post_discussion_support() {
		remove_post_type_support( 'post', 'comments' );
		remove_post_type_support( 'post', 'trackbacks' );
	}

	/**
	 * Registers the guided editor meta box.
	 *
	 * @return void
	 */
	public function register_meta_box() {
		add_meta_box(
			'avf-post-template-editor',
			__( 'AVF Beitragsformular', 'avf-post-templates' ),
			array( $this, 'render_meta_box' ),
			'post',
			'normal',
			'high'
		);
	}

	/**
	 * Removes discussion meta boxes for editorial posts.
	 *
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function adjust_discussion_meta_boxes( $post ) {
		if ( ! $post instanceof WP_Post || ! $this->is_editorial_post_type( $post ) ) {
			return;
		}

		remove_meta_box( 'commentstatusdiv', 'post', 'normal' );
		remove_meta_box( 'commentsdiv', 'post', 'normal' );
		remove_meta_box( 'trackbacksdiv', 'post', 'normal' );
	}

	/**
	 * Renders the editorial form.
	 *
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'avf_post_template_save', 'avf_post_template_nonce' );

		$enabled     = '1' === get_post_meta( $post->ID, self::META_ENABLED, true );
		$layout      = $this->sanitize_layout( get_post_meta( $post->ID, self::META_LAYOUT, true ) );
		$intro       = get_post_meta( $post->ID, self::META_INTRO, true );
		$body        = get_post_meta( $post->ID, self::META_BODY, true );
		$outro       = get_post_meta( $post->ID, self::META_OUTRO, true );
		$gallery_ids = $this->parse_gallery_ids( get_post_meta( $post->ID, self::META_GALLERY, true ) );
		$text_align  = $this->sanitize_text_alignment( get_post_meta( $post->ID, self::META_TEXT_ALIGN, true ) );
		?>
		<div class="avf-post-template-editor">
			<p class="avf-post-template-editor__intro">
				<?php esc_html_e( 'Titel, Datum, Kategorie und Beitragsbild werden ueber WordPress gepflegt.', 'avf-post-templates' ); ?>
			</p>

			<label class="avf-post-template-editor__toggle">
				<input type="checkbox" name="avf_post_template_enabled" value="1" <?php checked( $enabled ); ?> />
				<?php esc_html_e( 'Gefuehrtes Beitragslayout verwenden', 'avf-post-templates' ); ?>
			</label>

			<div class="avf-post-template-editor__panel<?php echo $enabled ? ' is-enabled' : ''; ?>" data-avf-post-template-panel>
				<p class="description">
					<?php esc_html_e( 'Das Layout steuert nur die redaktionelle Darstellung dieses Beitrags.', 'avf-post-templates' ); ?>
				</p>

				<div class="avf-post-template-editor__grid">
					<p>
						<label for="avf-post-template-layout"><strong><?php esc_html_e( 'Layouttyp', 'avf-post-templates' ); ?></strong></label><br />
						<select id="avf-post-template-layout" name="avf_post_template_layout">
							<?php foreach ( $this->get_layout_options() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $layout, $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>

					<p>
						<label for="avf-post-template-text-align"><strong><?php esc_html_e( 'Textausrichtung', 'avf-post-templates' ); ?></strong></label><br />
						<select id="avf-post-template-text-align" name="avf_post_template_text_align">
							<?php foreach ( $this->get_text_alignment_options() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $text_align, $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>
				</div>

				<p>
					<label for="avf-post-template-intro"><strong><?php esc_html_e( 'Einleitung / Lead (optional)', 'avf-post-templates' ); ?></strong></label>
					<span class="description"><?php esc_html_e( 'Kurzer Vorspann oberhalb des Haupttexts.', 'avf-post-templates' ); ?></span>
					<textarea id="avf-post-template-intro" name="avf_post_template_intro" rows="4" class="widefat"><?php echo esc_textarea( is_string( $intro ) ? $intro : '' ); ?></textarea>
				</p>

				<div class="avf-post-template-editor__wysiwyg">
					<label><strong><?php esc_html_e( 'Haupttext', 'avf-post-templates' ); ?></strong></label>
					<?php
					wp_editor(
						is_string( $body ) ? $body : '',
						'avf_post_template_body',
						array(
							'textarea_name' => 'avf_post_template_body',
							'textarea_rows' => 12,
							'media_buttons' => false,
							'teeny'         => true,
						)
					);
					?>
				</div>

				<div class="avf-post-template-editor__gallery" data-avf-post-template-gallery data-layout="<?php echo esc_attr( $layout ); ?>">
					<label><strong><?php esc_html_e( 'Galeriebilder fuer Karussell', 'avf-post-templates' ); ?></strong></label>
					<p class="description">
						<?php esc_html_e( 'Fuer das Layout "Bilderkarussell" koennen auch einzelne Bilder verwendet werden. Bildunterschriften und Alt-Texte werden direkt in der Mediathek gepflegt.', 'avf-post-templates' ); ?>
					</p>
					<input type="hidden" name="avf_post_template_gallery_ids" value="<?php echo esc_attr( implode( ',', $gallery_ids ) ); ?>" data-avf-post-template-gallery-input />
					<p class="avf-post-template-editor__gallery-actions">
						<button type="button" class="button button-secondary" data-avf-post-template-select-gallery>
							<?php esc_html_e( 'Bilder auswaehlen', 'avf-post-templates' ); ?>
						</button>
						<button type="button" class="button-link-delete" data-avf-post-template-clear-gallery>
							<?php esc_html_e( 'Galerie leeren', 'avf-post-templates' ); ?>
						</button>
					</p>
					<div class="avf-post-template-editor__gallery-preview" data-avf-post-template-gallery-preview>
						<?php echo $this->get_gallery_preview_markup( $gallery_ids ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>

				<p>
					<label for="avf-post-template-outro"><strong><?php esc_html_e( 'Abschluss / Hinweis (optional)', 'avf-post-templates' ); ?></strong></label>
					<span class="description"><?php esc_html_e( 'Optionaler Schlusshinweis unter dem Haupttext.', 'avf-post-templates' ); ?></span>
					<textarea id="avf-post-template-outro" name="avf_post_template_outro" rows="4" class="widefat"><?php echo esc_textarea( is_string( $outro ) ? $outro : '' ); ?></textarea>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Saves the editorial form values.
	 *
	 * @param int $post_id Current post id.
	 * @return void
	 */
	public function save_post( $post_id ) {
		if ( ! isset( $_POST['avf_post_template_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['avf_post_template_nonce'] ) ), 'avf_post_template_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$enabled = isset( $_POST['avf_post_template_enabled'] ) ? '1' : '0';
		update_post_meta( $post_id, self::META_ENABLED, $enabled );

		$layout = isset( $_POST['avf_post_template_layout'] ) ? $this->sanitize_layout( wp_unslash( $_POST['avf_post_template_layout'] ) ) : 'hero';
		update_post_meta( $post_id, self::META_LAYOUT, $layout );

		$text_align = isset( $_POST['avf_post_template_text_align'] ) ? $this->sanitize_text_alignment( wp_unslash( $_POST['avf_post_template_text_align'] ) ) : 'justify';
		update_post_meta( $post_id, self::META_TEXT_ALIGN, $text_align );

		$intro = isset( $_POST['avf_post_template_intro'] ) ? sanitize_textarea_field( wp_unslash( $_POST['avf_post_template_intro'] ) ) : '';
		update_post_meta( $post_id, self::META_INTRO, $intro );

		$body = isset( $_POST['avf_post_template_body'] ) ? wp_kses_post( wp_unslash( $_POST['avf_post_template_body'] ) ) : '';
		update_post_meta( $post_id, self::META_BODY, $body );

		$outro = isset( $_POST['avf_post_template_outro'] ) ? sanitize_textarea_field( wp_unslash( $_POST['avf_post_template_outro'] ) ) : '';
		update_post_meta( $post_id, self::META_OUTRO, $outro );

		$gallery = isset( $_POST['avf_post_template_gallery_ids'] ) ? implode( ',', $this->parse_gallery_ids( wp_unslash( $_POST['avf_post_template_gallery_ids'] ) ) ) : '';
		update_post_meta( $post_id, self::META_GALLERY, $gallery );

		$this->ensure_discussion_is_closed( $post_id );
	}

	/**
	 * Forces closed discussion defaults for regular posts.
	 *
	 * @param array $data    Sanitized post data.
	 * @param array $postarr Original submitted data.
	 * @return array
	 */
	public function filter_insert_post_data( $data, $postarr ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		if ( empty( $data['post_type'] ) || 'post' !== $data['post_type'] ) {
			return $data;
		}

		$data['comment_status'] = 'closed';
		$data['ping_status']    = 'closed';

		return $data;
	}

	/**
	 * Loads admin assets for the post editor.
	 *
	 * @param string $hook_suffix Current admin screen hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post-new.php', 'post.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'avf-post-template-admin',
			AVF_POST_TEMPLATES_URL . 'assets/css/post-template-admin.css',
			array(),
			AVF_POST_TEMPLATES_VERSION
		);

		wp_enqueue_script(
			'avf-post-template-admin',
			AVF_POST_TEMPLATES_URL . 'assets/js/post-template-admin.js',
			array( 'jquery' ),
			AVF_POST_TEMPLATES_VERSION,
			true
		);
	}

	/**
	 * Loads frontend assets only when structured posts are rendered.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || ! $this->is_structured_post( $post->ID ) ) {
			return;
		}

		$this->enqueue_shared_frontend_assets();
	}

	/**
	 * Loads the shared archive assets on the dedicated "Aktuelles" page.
	 *
	 * @return void
	 */
	public function maybe_enqueue_archive_assets() {
		if ( ! $this->is_aktuelles_page() ) {
			return;
		}

		$this->enqueue_shared_frontend_assets();
	}

	/**
	 * Enqueues the shared frontend stylesheet and script once per request.
	 *
	 * @return void
	 */
	private function enqueue_shared_frontend_assets() {
		$frontend_css_path = AVF_POST_TEMPLATES_DIR . 'assets/css/post-template-frontend.css';
		$frontend_version  = file_exists( $frontend_css_path )
			? (string) filemtime( $frontend_css_path )
			: AVF_POST_TEMPLATES_VERSION;

		wp_enqueue_style(
			'avf-post-template-frontend',
			AVF_POST_TEMPLATES_URL . 'assets/css/post-template-frontend.css',
			array(),
			$frontend_version
		);

		$frontend_js_path = AVF_POST_TEMPLATES_DIR . 'assets/js/post-template-frontend.js';
		$frontend_js_ver  = file_exists( $frontend_js_path )
			? (string) filemtime( $frontend_js_path )
			: AVF_POST_TEMPLATES_VERSION;

		wp_enqueue_script(
			'avf-post-template-frontend',
			AVF_POST_TEMPLATES_URL . 'assets/js/post-template-frontend.js',
			array(),
			$frontend_js_ver,
			true
		);
	}

	/**
	 * Replaces the legacy Elementor loop widget on /aktuelles/ with the shared
	 * recommendation card archive.
	 *
	 * @param string $widget_content Existing Elementor widget markup.
	 * @param mixed  $widget         Current Elementor widget instance.
	 * @return string
	 */
	public function replace_aktuelles_loop_grid_widget( $widget_content, $widget ) {
		if ( ! $this->should_replace_aktuelles_loop_widget( $widget ) ) {
			return $widget_content;
		}

		$archive_markup = $this->get_archive_posts_markup();

		return '' !== $archive_markup ? $archive_markup : $widget_content;
	}

	/**
	 * Adds page-level classes for structured single posts.
	 *
	 * @param array $classes Existing body classes.
	 * @return array
	 */
	public function filter_body_classes( $classes ) {
		if ( ! is_array( $classes ) || ! is_singular( 'post' ) ) {
			return $classes;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || ! $this->is_structured_post( $post->ID ) ) {
			return $classes;
		}

		$classes[] = 'avf-structured-post-page';
		$classes[] = 'avf-has-zirkel-bg';

		return array_values( array_unique( $classes ) );
	}

	/**
	 * Hides Hello Elementor's native page title when the guided post template
	 * renders its own semantic article heading.
	 *
	 * @param bool $show_title Whether the theme should render the page title.
	 * @return bool
	 */
	public function filter_theme_page_title( $show_title ) {
		if ( ! $show_title || ! is_singular( 'post' ) ) {
			return $show_title;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || ! $this->is_structured_post( $post->ID ) ) {
			return $show_title;
		}

		return false;
	}

	/**
	 * Detects the dedicated "Aktuelles" landing page.
	 *
	 * @return bool
	 */
	private function is_aktuelles_page() {
		return is_page( self::AKTUELLES_PAGE_ID ) || is_page( self::AKTUELLES_PAGE_SLUG );
	}

	/**
	 * Determines whether the current Elementor widget should be replaced by the
	 * shared post archive component.
	 *
	 * @param mixed $widget Elementor widget instance.
	 * @return bool
	 */
	private function should_replace_aktuelles_loop_widget( $widget ) {
		if ( ! $this->is_aktuelles_page() || ! is_object( $widget ) ) {
			return false;
		}

		if ( ! method_exists( $widget, 'get_name' ) || 'loop-grid' !== $widget->get_name() ) {
			return false;
		}

		$settings    = method_exists( $widget, 'get_settings' ) ? $widget->get_settings() : array();
		$template_id = isset( $settings['template_id'] ) ? (string) $settings['template_id'] : '';
		$widget_id   = method_exists( $widget, 'get_id' ) ? (string) $widget->get_id() : '';

		return self::AKTUELLES_LOOP_TEMPLATE_ID === $template_id || self::AKTUELLES_LOOP_WIDGET_ID === $widget_id;
	}

	/**
	 * Replaces the normal post content with the guided layout output.
	 *
	 * @param string $content Original post content.
	 * @return string
	 */
	public function render_structured_post_content( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( ! $post_id || ! $this->is_structured_post( $post_id ) ) {
			return $content;
		}

		return $this->build_frontend_markup( $post_id );
	}

	/**
	 * Closes comments for editorial posts on the frontend.
	 *
	 * @param bool $open    Current open state.
	 * @param int  $post_id Post id.
	 * @return bool
	 */
	public function filter_comments_open( $open, $post_id ) {
		return $this->is_editorial_post_type( $post_id ) ? false : $open;
	}

	/**
	 * Closes pings for editorial posts on the frontend.
	 *
	 * @param bool $open    Current open state.
	 * @param int  $post_id Post id.
	 * @return bool
	 */
	public function filter_pings_open( $open, $post_id ) {
		return $this->is_editorial_post_type( $post_id ) ? false : $open;
	}

	/**
	 * Removes comments from editorial posts.
	 *
	 * @param array   $comments Current comments list.
	 * @param WP_Post $post     Post object.
	 * @return array
	 */
	public function filter_comments_array( $comments, $post ) {
		if ( $post instanceof WP_Post && $this->is_editorial_post_type( $post ) ) {
			return array();
		}

		return $comments;
	}

	/**
	 * Forces a zero comment count for editorial posts.
	 *
	 * @param int $count   Current comment count.
	 * @param int $post_id Post id.
	 * @return int
	 */
	public function filter_comments_number( $count, $post_id ) {
		return $this->is_editorial_post_type( $post_id ) ? 0 : $count;
	}

	/**
	 * Points editorial posts at an empty comments template.
	 *
	 * @param string $template Current comments template path.
	 * @return string
	 */
	public function filter_comments_template( $template ) {
		if ( ! is_singular( 'post' ) ) {
			return $template;
		}

		$post_id = get_the_ID();
		if ( ! $post_id || ! $this->is_editorial_post_type( $post_id ) ) {
			return $template;
		}

		$empty_template = AVF_POST_TEMPLATES_DIR . 'includes/templates/empty-comments.php';
		return file_exists( $empty_template ) ? $empty_template : $template;
	}

	/**
	 * Closes comment and ping status for all regular posts once.
	 *
	 * @return void
	 */
	public function maybe_migrate_structured_post_discussion_state() {
		if ( get_option( self::OPTION_MIGRATED ) ) {
			return;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			)
		);

		if ( is_array( $posts ) ) {
			foreach ( $posts as $post_id ) {
				$this->ensure_discussion_is_closed( (int) $post_id );
			}
		}

		update_option( self::OPTION_MIGRATED, '1', false );
	}

	/**
	 * Determines whether the current post uses the guided layout.
	 *
	 * @param int $post_id Post id.
	 * @return bool
	 */
	private function is_structured_post( $post_id ) {
		return '1' === get_post_meta( $post_id, self::META_ENABLED, true );
	}

	/**
	 * Determines whether a value refers to a regular WordPress post.
	 *
	 * @param int|WP_Post $post Post identifier or object.
	 * @return bool
	 */
	private function is_editorial_post_type( $post ) {
		if ( $post instanceof WP_Post ) {
			return 'post' === $post->post_type;
		}

		$post = get_post( (int) $post );

		return $post instanceof WP_Post && 'post' === $post->post_type;
	}

	/**
	 * Normalises and validates layout values.
	 *
	 * @param mixed $layout Raw layout value.
	 * @return string
	 */
	private function sanitize_layout( $layout ) {
		$layout  = is_string( $layout ) ? sanitize_key( $layout ) : '';
		$options = $this->get_layout_options();

		return array_key_exists( $layout, $options ) ? $layout : 'hero';
	}

	/**
	 * Normalises and validates text alignment values.
	 *
	 * @param mixed $value Raw alignment value.
	 * @return string
	 */
	private function sanitize_text_alignment( $value ) {
		$value   = is_string( $value ) ? sanitize_key( $value ) : '';
		$options = $this->get_text_alignment_options();

		return array_key_exists( $value, $options ) ? $value : 'justify';
	}

	/**
	 * Parses stored gallery ids.
	 *
	 * @param mixed $raw_ids Raw list.
	 * @return int[]
	 */
	private function parse_gallery_ids( $raw_ids ) {
		if ( ! is_string( $raw_ids ) || '' === trim( $raw_ids ) ) {
			return array();
		}

		$ids = array_map( 'trim', explode( ',', $raw_ids ) );
		$ids = array_map( 'absint', $ids );
		$ids = array_filter( $ids );

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Renders gallery image previews in the editor.
	 *
	 * @param int[] $gallery_ids Attachment ids.
	 * @return string
	 */
	private function get_gallery_preview_markup( array $gallery_ids ) {
		if ( empty( $gallery_ids ) ) {
			return '<p class="description">' . esc_html__( 'Noch keine Bilder ausgewaehlt.', 'avf-post-templates' ) . '</p>';
		}

		$html = '<div class="avf-post-template-editor__gallery-grid">';

		foreach ( $gallery_ids as $attachment_id ) {
			$thumb = wp_get_attachment_image( $attachment_id, 'thumbnail' );
			if ( '' === $thumb ) {
				continue;
			}

			$html .= '<div class="avf-post-template-editor__gallery-item">' . $thumb . '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Closes comment and ping status for a post.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	private function ensure_discussion_is_closed( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
			return;
		}

		$update = array(
			'ID' => $post_id,
		);

		if ( 'closed' !== $post->comment_status ) {
			$update['comment_status'] = 'closed';
		}

		if ( 'closed' !== $post->ping_status ) {
			$update['ping_status'] = 'closed';
		}

		if ( 1 < count( $update ) ) {
			remove_action( 'save_post_post', array( $this, 'save_post' ) );
			wp_update_post( $update );
			add_action( 'save_post_post', array( $this, 'save_post' ) );
		}
	}

	/**
	 * Builds the guided frontend markup.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	private function build_frontend_markup( $post_id ) {
		$layout      = $this->sanitize_layout( get_post_meta( $post_id, self::META_LAYOUT, true ) );
		$intro       = get_post_meta( $post_id, self::META_INTRO, true );
		$body        = get_post_meta( $post_id, self::META_BODY, true );
		$outro       = get_post_meta( $post_id, self::META_OUTRO, true );
		$gallery_ids = $this->parse_gallery_ids( get_post_meta( $post_id, self::META_GALLERY, true ) );
		$text_align  = $this->sanitize_text_alignment( get_post_meta( $post_id, self::META_TEXT_ALIGN, true ) );
		$title       = get_the_title( $post_id );
		$date        = $this->get_editorial_date_label( $post_id );
		$categories  = get_the_category( $post_id );
		$category    = ( ! empty( $categories ) && is_array( $categories ) ) ? $categories[0]->name : '';
		$eyebrow     = '' !== $category ? $category : __( 'Beitrag', 'avf-post-templates' );
		$section_id  = 'avf-structured-post-title-' . $post_id;

		$intro_html        = '' !== $intro ? wpautop( esc_html( $intro ) ) : '';
		$body_html         = '' !== $body ? wpautop( wp_kses_post( $body ) ) : '';
		$body_html         = $this->normalise_body_markup( $body_html );
		$reading_time      = $this->get_estimated_reading_time_label( trim( $intro . ' ' . wp_strip_all_tags( $body_html ) . ' ' . $outro ) );
		$body_class        = 'avf-structured-post__body';
		$outro_html        = '' !== $outro ? wpautop( esc_html( $outro ) ) : '';
		$meta_html         = $this->get_post_meta_markup(
			array_filter(
				array(
					$date,
					$reading_time,
				)
			)
		);

		$media_html = 'carousel' === $layout
			? AVF_Post_Carousel_Shortcode::instance()->render_from_ids( $gallery_ids )
			: $this->get_featured_media_markup( $post_id );

		if ( 'justify' === $text_align ) {
			$body_class .= ' avf-structured-post__body--journal-ready';
		}

		$classes = 'avf-structured-post avf-structured-post--' . $layout;

		ob_start();
		?>
		<div class="avf-structured-post-shell">
			<article class="<?php echo esc_attr( $classes ); ?>" aria-labelledby="<?php echo esc_attr( $section_id ); ?>">
				<?php if ( 'hero' === $layout ) : ?>
					<header class="avf-structured-post__hero-head">
						<div class="avf-structured-post__hero-shell">
							<div class="avf-structured-post__hero-copy">
								<p class="avf-structured-post__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
								<h1 class="avf-structured-post__title" id="<?php echo esc_attr( $section_id ); ?>"><?php echo esc_html( $title ); ?></h1>
								<?php echo $meta_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php if ( '' !== $intro_html ) : ?>
									<div class="avf-structured-post__lead"><?php echo $intro_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
								<?php endif; ?>
							</div>
							<?php if ( '' !== $media_html ) : ?>
								<div class="avf-structured-post__hero-media">
									<?php echo $media_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
							<?php endif; ?>
						</div>
					</header>
					<div class="avf-structured-post__content avf-structured-post__content--hero">
						<?php echo $this->get_text_content_markup( '', $body_html, $outro_html, $text_align, $body_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php else : ?>
					<header class="avf-structured-post__masthead avf-structured-post__masthead--carousel">
						<p class="avf-structured-post__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
						<h1 class="avf-structured-post__title" id="<?php echo esc_attr( $section_id ); ?>"><?php echo esc_html( $title ); ?></h1>
						<?php echo $meta_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( '' !== $intro_html ) : ?>
							<div class="avf-structured-post__lead"><?php echo $intro_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<?php endif; ?>
					</header>
					<?php if ( '' !== $media_html ) : ?>
						<div class="avf-structured-post__media avf-structured-post__media--carousel">
							<?php echo $media_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>
					<div class="avf-structured-post__content avf-structured-post__content--carousel">
						<?php echo $this->get_text_content_markup( '', $body_html, $outro_html, $text_align, $body_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endif; ?>
			</article>
			<?php echo $this->get_related_posts_markup( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Renders a small recommendation section with additional posts.
	 *
	 * @param int $post_id Current post id.
	 * @return string
	 */
	private function get_related_posts_markup( $post_id ) {
		$posts = $this->get_related_posts( $post_id, 3 );

		if ( empty( $posts ) ) {
			return '';
		}

		ob_start();
		?>
		<section class="avf-post-recommendations" aria-labelledby="avf-post-recommendations-title-<?php echo esc_attr( $post_id ); ?>">
			<div class="avf-post-recommendations__head">
				<h2 class="avf-post-recommendations__title" id="avf-post-recommendations-title-<?php echo esc_attr( $post_id ); ?>">
					<?php esc_html_e( 'Weitere Beiträge entdecken', 'avf-post-templates' ); ?>
				</h2>
				<p class="avf-post-recommendations__intro">
					<?php esc_html_e( 'Hier sind noch mehr Beiträge, die dich vielleicht interessieren.', 'avf-post-templates' ); ?>
				</p>
			</div>
			<div class="avf-post-recommendations__grid">
				<?php foreach ( $posts as $related_post ) : ?>
					<?php echo $this->get_related_post_card_markup( $related_post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Renders the shared post archive for the /aktuelles/ page.
	 *
	 * @return string
	 */
	private function get_archive_posts_markup() {
		$posts = $this->get_archive_posts();

		if ( empty( $posts ) ) {
			return '';
		}

		$grid_id     = wp_unique_id( 'avf-post-archive-grid-' );
		$total_posts = count( $posts );
		$initial     = self::ARCHIVE_INITIAL_COUNT;
		$step        = self::ARCHIVE_STEP_COUNT;

		ob_start();
		?>
		<section
			class="avf-post-archive"
			data-avf-post-archive
			data-initial-count="<?php echo esc_attr( $initial ); ?>"
			data-step="<?php echo esc_attr( $step ); ?>"
		>
			<div class="avf-post-recommendations__grid avf-post-archive__grid" id="<?php echo esc_attr( $grid_id ); ?>" role="list">
				<?php foreach ( $posts as $index => $archive_post ) : ?>
					<?php echo $this->get_archive_post_card_markup( $archive_post, $index >= $initial ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>
			<div class="avf-post-archive__actions"<?php echo $total_posts > $initial ? '' : ' hidden'; ?>>
				<button
					type="button"
					class="avf-post-archive__button avf-btn-primary"
					data-avf-post-archive-button
					aria-controls="<?php echo esc_attr( $grid_id ); ?>"
					aria-expanded="false"
				>
					<?php esc_html_e( 'Mehr anzeigen', 'avf-post-templates' ); ?>
				</button>
				<span class="avf-post-archive__status" data-avf-post-archive-status aria-live="polite">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of visible posts. */
							__( '%d Beitraege sichtbar', 'avf-post-templates' ),
							min( $total_posts, $initial )
						)
					);
					?>
				</span>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Returns up to a given number of recommended posts.
	 *
	 * Prefers posts from the same categories and fills remaining slots with
	 * the latest published posts.
	 *
	 * @param int $post_id Current post id.
	 * @param int $limit   Maximum number of posts.
	 * @return WP_Post[]
	 */
	private function get_related_posts( $post_id, $limit ) {
		$limit = max( 1, (int) $limit );

		$category_ids = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
		$exclude_ids  = array( (int) $post_id );
		$results      = array();

		if ( ! empty( $category_ids ) ) {
			$category_query = new WP_Query(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => $limit,
					'post__not_in'        => $exclude_ids,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'orderby'             => 'date',
					'order'               => 'DESC',
					'category__in'        => array_map( 'absint', $category_ids ),
				)
			);

			if ( ! empty( $category_query->posts ) && is_array( $category_query->posts ) ) {
				foreach ( $category_query->posts as $related_post ) {
					if ( ! $related_post instanceof WP_Post ) {
						continue;
					}

					$results[]    = $related_post;
					$exclude_ids[] = (int) $related_post->ID;
				}
			}
		}

		$remaining = $limit - count( $results );

		if ( $remaining > 0 ) {
			$fallback_query = new WP_Query(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => $remaining,
					'post__not_in'        => $exclude_ids,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'orderby'             => 'date',
					'order'               => 'DESC',
				)
			);

			if ( ! empty( $fallback_query->posts ) && is_array( $fallback_query->posts ) ) {
				foreach ( $fallback_query->posts as $related_post ) {
					if ( $related_post instanceof WP_Post ) {
						$results[] = $related_post;
					}
				}
			}
		}

		return array_slice( $results, 0, $limit );
	}

	/**
	 * Returns the published posts for the dedicated archive in reverse
	 * chronological order.
	 *
	 * @return WP_Post[]
	 */
	private function get_archive_posts() {
		$query = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => -1,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'orderby'             => 'date',
				'order'               => 'DESC',
			)
		);

		return ! empty( $query->posts ) && is_array( $query->posts ) ? $query->posts : array();
	}

	/**
	 * Builds one recommendation card.
	 *
	 * @param WP_Post $post Related post object.
	 * @return string
	 */
	private function get_related_post_card_markup( $post ) {
		return $this->get_post_card_markup( $post, array( 'avf-post-recommendations__card' ) );
	}

	/**
	 * Builds one archive card and optionally keeps it hidden until the user
	 * reveals more posts.
	 *
	 * @param WP_Post $post      Archive post object.
	 * @param bool    $is_hidden Whether the card starts hidden.
	 * @return string
	 */
	private function get_archive_post_card_markup( $post, $is_hidden ) {
		$attributes = array(
			'data-avf-post-archive-item' => 'true',
			'role'                       => 'listitem',
		);

		if ( $is_hidden ) {
			$attributes['hidden'] = 'hidden';
		}

		return $this->get_post_card_markup(
			$post,
			array(
				'avf-post-recommendations__card',
				'avf-post-archive__item',
			),
			$attributes
		);
	}

	/**
	 * Builds one shared card instance.
	 *
	 * @param WP_Post               $post            Post object.
	 * @param string[]              $article_classes Card wrapper classes.
	 * @param array<string, string> $attributes      Optional article attributes.
	 * @return string
	 */
	private function get_post_card_markup( $post, array $article_classes, array $attributes = array() ) {
		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$post_id    = (int) $post->ID;
		$title      = get_the_title( $post_id );
		$permalink  = get_permalink( $post_id );
		$date       = get_the_date( '', $post_id );
		$excerpt    = get_the_excerpt( $post_id );
		$excerpt    = is_string( $excerpt ) ? wp_trim_words( wp_strip_all_tags( $excerpt ), 22 ) : '';
		$categories = get_the_category( $post_id );
		$category   = ( ! empty( $categories ) && is_array( $categories ) ) ? $categories[0]->name : __( 'Beitrag', 'avf-post-templates' );
		$image_html = $this->get_related_post_image_markup( $post_id, $title );

		if ( ! is_string( $permalink ) || '' === $permalink ) {
			return '';
		}

		$article_class = implode(
			' ',
			array_values(
				array_unique(
					array_filter(
						array_map( 'sanitize_html_class', $article_classes )
					)
				)
			)
		);

		if ( '' === $article_class ) {
			$article_class = 'avf-post-recommendations__card';
		}

		$attribute_markup = '';
		foreach ( $attributes as $attribute_name => $attribute_value ) {
			$attribute_name = sanitize_key( (string) $attribute_name );

			if ( '' === $attribute_name ) {
				continue;
			}

			if ( 'hidden' === $attribute_name ) {
				$attribute_markup .= ' hidden';
				continue;
			}

			$attribute_markup .= sprintf(
				' %1$s="%2$s"',
				esc_attr( $attribute_name ),
				esc_attr( (string) $attribute_value )
			);
		}

		ob_start();
		?>
		<article class="<?php echo esc_attr( $article_class ); ?>"<?php echo $attribute_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<a class="avf-post-recommendations__card-link" href="<?php echo esc_url( $permalink ); ?>">
				<?php if ( '' !== $image_html ) : ?>
					<div class="avf-post-recommendations__media">
						<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endif; ?>
				<div class="avf-post-recommendations__content">
					<p class="avf-post-recommendations__category"><?php echo esc_html( $category ); ?></p>
					<h3 class="avf-post-recommendations__card-title"><?php echo esc_html( $title ); ?></h3>
					<?php if ( '' !== $excerpt ) : ?>
						<p class="avf-post-recommendations__excerpt"><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>
					<div class="avf-post-recommendations__meta">
						<?php if ( '' !== $date ) : ?>
							<time datetime="<?php echo esc_attr( get_post_time( 'c', false, $post_id ) ); ?>"><?php echo esc_html( $date ); ?></time>
						<?php endif; ?>
						<span class="avf-post-recommendations__cta">
							<?php esc_html_e( 'Weiterlesen', 'avf-post-templates' ); ?>
							<span aria-hidden="true">→</span>
						</span>
					</div>
				</div>
			</a>
		</article>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Builds the card image markup for one related post.
	 *
	 * @param int    $post_id Post id.
	 * @param string $title   Post title fallback for alt text.
	 * @return string
	 */
	private function get_related_post_image_markup( $post_id, $title ) {
		if ( ! has_post_thumbnail( $post_id ) ) {
			return '';
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		$alt          = is_int( $thumbnail_id ) ? get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) : '';
		$alt          = is_string( $alt ) && '' !== trim( $alt ) ? $alt : $title;

		return get_the_post_thumbnail(
			$post_id,
			'medium_large',
			array(
				'class'   => 'avf-post-recommendations__image',
				'alt'     => $alt,
				'loading' => 'lazy',
			)
		);
	}

	/**
	 * Renders the shared post meta line.
	 *
	 * @param string[] $items Meta item labels.
	 * @return string
	 */
	private function get_post_meta_markup( array $items ) {
		$items = array_values(
			array_filter(
				array_map( 'trim', $items )
			)
		);

		if ( empty( $items ) ) {
			return '';
		}

		ob_start();
		?>
		<p class="avf-structured-post__meta">
			<?php foreach ( $items as $index => $item ) : ?>
				<?php if ( $index > 0 ) : ?>
					<span class="avf-structured-post__meta-separator" aria-hidden="true">&middot;</span>
				<?php endif; ?>
				<span class="avf-structured-post__meta-item"><?php echo esc_html( $item ); ?></span>
			<?php endforeach; ?>
		</p>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Formats the editorial date in a prose-friendly style.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	private function get_editorial_date_label( $post_id ) {
		$timestamp = (int) get_post_time( 'U', false, $post_id );
		if ( $timestamp > 0 ) {
			return wp_date( 'j. F Y', $timestamp );
		}

		return get_the_date( '', $post_id );
	}

	/**
	 * Estimates the reading time label for a post.
	 *
	 * @param string $text Plain-text post content.
	 * @return string
	 */
	private function get_estimated_reading_time_label( $text ) {
		$text = trim( wp_strip_all_tags( (string) $text ) );
		if ( '' === $text ) {
			return '';
		}

		$words = preg_split( '/\s+/u', $text );
		if ( ! is_array( $words ) ) {
			return '';
		}

		$word_count = count(
			array_filter(
				$words,
				static function ( $word ) {
					return '' !== trim( (string) $word );
				}
			)
		);

		if ( $word_count <= 0 ) {
			return '';
		}

		$minutes = max( 1, (int) ceil( $word_count / 200 ) );

		return sprintf(
			/* translators: %d is the estimated reading time in minutes. */
			__( '%d Min. Lesezeit', 'avf-post-templates' ),
			$minutes
		);
	}

	/**
	 * Renders the text sections of a structured post.
	 *
	 * @param string $intro_html Intro html.
	 * @param string $body_html  Body html.
	 * @param string $outro_html Outro html.
	 * @param string $text_align Alignment value.
	 * @param string $body_class Body wrapper class list.
	 * @return string
	 */
	private function get_text_content_markup( $intro_html, $body_html, $outro_html, $text_align, $body_class ) {
		$html = '<div class="avf-structured-post__text avf-post-content avf-post-content--align-' . esc_attr( $text_align ) . '">';

		if ( '' !== $intro_html ) {
			$html .= '<div class="avf-structured-post__intro">' . $intro_html . '</div>';
		}

		if ( '' !== $body_html ) {
			$html .= '<div class="' . esc_attr( $body_class ) . '">' . $body_html . '</div>';
		}

		if ( '' !== $outro_html ) {
			$html .= '<div class="avf-structured-post__outro">' . $outro_html . '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Removes inline text alignment styles so the selected frontend layout wins.
	 *
	 * @param string $html Prepared body markup.
	 * @return string
	 */
	private function normalise_body_markup( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return '';
		}

		$html = preg_replace_callback(
			'/\sstyle=("|\')(.*?)\1/i',
			static function ( $matches ) {
				$style = isset( $matches[2] ) ? (string) $matches[2] : '';
				$style = preg_replace( '/(?:^|;)\s*text-align\s*:\s*[^;]+;?/i', ';', $style );
				$style = preg_replace( '/;{2,}/', ';', $style );
				$style = trim( trim( (string) $style ), ';' );

				if ( '' === $style ) {
					return '';
				}

				return ' style="' . esc_attr( $style ) . '"';
			},
			$html
		);

		return is_string( $html ) ? $html : '';
	}

	/**
	 * Builds the featured image markup for hero and split layouts.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	private function get_featured_media_markup( $post_id ) {
		if ( ! has_post_thumbnail( $post_id ) ) {
			return '';
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		$caption      = is_int( $thumbnail_id ) ? wp_get_attachment_caption( $thumbnail_id ) : '';

		ob_start();
		?>
		<figure class="avf-structured-post__figure">
			<?php echo get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'avf-structured-post__image' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( is_string( $caption ) && '' !== trim( $caption ) ) : ?>
				<figcaption class="avf-structured-post__caption"><?php echo esc_html( $caption ); ?></figcaption>
			<?php endif; ?>
		</figure>
		<?php

		return (string) ob_get_clean();
	}
}
