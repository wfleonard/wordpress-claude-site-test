<?php
/**
 * Saxon theme bootstrap.
 *
 * @package Saxon
 * @author  William Leonard, CTI Global <bill.leonard@cticorp.com>
 */

defined( 'ABSPATH' ) || exit;

define( 'SAXON_VERSION', '1.0.0' );
define( 'SAXON_DIR', get_template_directory() );
define( 'SAXON_URI', get_template_directory_uri() );

require SAXON_DIR . '/inc/template-tags.php';
require SAXON_DIR . '/inc/customizer.php';

/**
 * Register theme supports, menus and image sizes.
 */
function saxon_setup() {
	load_theme_textdomain( 'saxon', SAXON_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 96,
			'width'       => 320,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary navigation', 'saxon' ),
			'footer'  => __( 'Footer navigation', 'saxon' ),
		)
	);

	add_image_size( 'saxon-card', 720, 450, true );
}
add_action( 'after_setup_theme', 'saxon_setup' );

/**
 * Content width for embeds and media.
 */
function saxon_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'saxon_content_width', 760 );
}
add_action( 'after_setup_theme', 'saxon_content_width', 0 );

/**
 * Load block styles only for blocks on the page instead of the whole
 * block library, which classic themes otherwise get on every request.
 */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

/**
 * Enqueue front end styles and scripts.
 */
function saxon_enqueue_assets() {
	wp_enqueue_style( 'saxon-main', SAXON_URI . '/assets/css/main.css', array(), saxon_asset_version( 'assets/css/main.css' ) );

	wp_enqueue_script(
		'saxon-navigation',
		SAXON_URI . '/assets/js/navigation.js',
		array(),
		saxon_asset_version( 'assets/js/navigation.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'saxon_enqueue_assets' );

/**
 * Cache busting version based on file modification time.
 *
 * @param string $relative_path Path relative to the theme root.
 * @return string
 */
function saxon_asset_version( $relative_path ) {
	$file = SAXON_DIR . '/' . $relative_path;
	return file_exists( $file ) ? SAXON_VERSION . '.' . filemtime( $file ) : SAXON_VERSION;
}

/**
 * Register the footer widget area.
 */
function saxon_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Footer', 'saxon' ),
			'id'            => 'footer-1',
			'description'   => __( 'Widgets shown in the site footer.', 'saxon' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'saxon_widgets_init' );

/**
 * Add a "no-js" to "js" swap so CSS can style the menu before scripts run.
 */
function saxon_js_detection() {
	wp_print_inline_script_tag( "document.documentElement.classList.replace('no-js','js');" );
}
add_action( 'wp_head', 'saxon_js_detection', 0 );

/**
 * Body classes for layout hooks.
 *
 * @param string[] $classes Existing classes.
 * @return string[]
 */
function saxon_body_classes( $classes ) {
	if ( ! is_singular() ) {
		$classes[] = 'is-listing';
	}
	if ( is_active_sidebar( 'footer-1' ) ) {
		$classes[] = 'has-footer-widgets';
	}
	return $classes;
}
add_filter( 'body_class', 'saxon_body_classes' );

/**
 * Shorter, cleaner excerpt ending.
 *
 * @return string
 */
function saxon_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'saxon_excerpt_more' );

/**
 * Remove the emoji detection script and styles, which the theme does not need.
 */
function saxon_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'saxon_disable_emojis' );

/**
 * Register a "Card" style for Group and Column blocks.
 */
function saxon_register_block_styles() {
	foreach ( array( 'core/group', 'core/column' ) as $block ) {
		register_block_style(
			$block,
			array(
				'name'  => 'card',
				'label' => __( 'Card', 'saxon' ),
			)
		);
	}
}
add_action( 'init', 'saxon_register_block_styles' );
