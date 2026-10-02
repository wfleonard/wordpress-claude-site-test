<?php
/**
 * Settings, SEO basics screen and admin assets.
 *
 * @package SaxonSeoBasics
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default option values.
 *
 * @return array
 */
function saxon_seo_defaults() {
	return array(
		'home_description' => '',
		'default_image'    => 0,
		'org_type'         => 'Organization',
		'org_name'         => '',
		'org_phone'        => '',
		'org_email'        => '',
		'social'           => array(),
		'twitter_site'     => '',
	);
}

/**
 * One option value, falling back to the default.
 *
 * @param string $key Option key.
 * @return mixed
 */
function saxon_seo_option( $key ) {
	$options  = get_option( 'saxon_seo_options', array() );
	$defaults = saxon_seo_defaults();
	return is_array( $options ) && array_key_exists( $key, $options ) ? $options[ $key ] : ( $defaults[ $key ] ?? '' );
}

/**
 * Organization types offered in the settings.
 *
 * @return string[]
 */
function saxon_seo_org_types() {
	return array(
		'Organization'        => __( 'Organization', 'saxon-seo-basics' ),
		'LocalBusiness'       => __( 'Local business', 'saxon-seo-basics' ),
		'ProfessionalService' => __( 'Professional service', 'saxon-seo-basics' ),
		'Corporation'         => __( 'Corporation', 'saxon-seo-basics' ),
	);
}

/**
 * Register the option and fields.
 */
function saxon_seo_register_settings() {
	register_setting(
		'saxon_seo',
		'saxon_seo_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'saxon_seo_sanitize_options',
			'default'           => array(),
		)
	);

	add_settings_section( 'saxon_seo_site', __( 'Home page and sharing', 'saxon-seo-basics' ), '__return_false', 'saxon-seo-basics' );
	add_settings_section(
		'saxon_seo_org',
		__( 'Organization', 'saxon-seo-basics' ),
		static function () {
			echo '<p>' . esc_html__( 'Used for the structured data search engines read. Empty fields fall back to the site title, the custom logo and the theme\'s contact details.', 'saxon-seo-basics' ) . '</p>';
		},
		'saxon-seo-basics'
	);

	$fields = array(
		'home_description' => array( __( 'Home page description', 'saxon-seo-basics' ), 'saxon_seo_site' ),
		'default_image'    => array( __( 'Default share image', 'saxon-seo-basics' ), 'saxon_seo_site' ),
		'twitter_site'     => array( __( 'X (Twitter) username', 'saxon-seo-basics' ), 'saxon_seo_site' ),
		'org_type'         => array( __( 'Type', 'saxon-seo-basics' ), 'saxon_seo_org' ),
		'org_name'         => array( __( 'Name', 'saxon-seo-basics' ), 'saxon_seo_org' ),
		'org_phone'        => array( __( 'Phone', 'saxon-seo-basics' ), 'saxon_seo_org' ),
		'org_email'        => array( __( 'Email', 'saxon-seo-basics' ), 'saxon_seo_org' ),
		'social'           => array( __( 'Social profiles', 'saxon-seo-basics' ), 'saxon_seo_org' ),
	);
	foreach ( $fields as $key => $field ) {
		add_settings_field( 'saxon_seo_' . $key, $field[0], 'saxon_seo_field_' . $key, 'saxon-seo-basics', $field[1], array( 'label_for' => 'saxon_seo_' . $key ) );
	}
}
add_action( 'admin_init', 'saxon_seo_register_settings' );

/**
 * Sanitize the saved options.
 *
 * @param mixed $input Raw input.
 * @return array
 */
function saxon_seo_sanitize_options( $input ) {
	$input = is_array( $input ) ? $input : array();

	$social = array();
	foreach ( preg_split( '/\s+/', (string) ( $input['social'] ?? '' ) ) as $url ) {
		$url = esc_url_raw( trim( $url ), array( 'https', 'http' ) );
		if ( $url ) {
			$social[] = $url;
		}
	}

	$image = absint( $input['default_image'] ?? 0 );
	if ( $image && ! wp_attachment_is_image( $image ) ) {
		$image = 0;
	}

	$type = (string) ( $input['org_type'] ?? 'Organization' );

	return array(
		'home_description' => sanitize_textarea_field( $input['home_description'] ?? '' ),
		'default_image'    => $image,
		'org_type'         => array_key_exists( $type, saxon_seo_org_types() ) ? $type : 'Organization',
		'org_name'         => sanitize_text_field( $input['org_name'] ?? '' ),
		'org_phone'        => sanitize_text_field( $input['org_phone'] ?? '' ),
		'org_email'        => sanitize_email( $input['org_email'] ?? '' ),
		'social'           => array_values( array_unique( $social ) ),
		'twitter_site'     => preg_replace( '/[^A-Za-z0-9_]/', '', (string) ( $input['twitter_site'] ?? '' ) ),
	);
}

/**
 * Add the settings page.
 */
function saxon_seo_admin_menu() {
	add_options_page(
		__( 'SEO basics', 'saxon-seo-basics' ),
		__( 'SEO basics', 'saxon-seo-basics' ),
		'manage_options',
		'saxon-seo-basics',
		'saxon_seo_settings_page'
	);
}
add_action( 'admin_menu', 'saxon_seo_admin_menu' );

/**
 * Settings page markup.
 */
function saxon_seo_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p>
			<?php
			printf(
				/* translators: %s: sitemap URL. */
				esc_html__( 'WordPress already provides the title tag, canonical links and the XML sitemap at %s. This page sets what core does not cover.', 'saxon-seo-basics' ),
				'<a href="' . esc_url( get_sitemap_url( 'index' ) ) . '">' . esc_html( get_sitemap_url( 'index' ) ) . '</a>'
			);
			?>
		</p>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'saxon_seo' );
			do_settings_sections( 'saxon-seo-basics' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Home description field.
 */
function saxon_seo_field_home_description() {
	printf(
		'<textarea class="large-text" rows="3" id="saxon_seo_home_description" name="saxon_seo_options[home_description]" maxlength="320" data-saxon-seo-count="160" aria-describedby="saxon_seo_home_description_help">%1$s</textarea><p class="description" id="saxon_seo_home_description_help">%2$s</p>',
		esc_textarea( saxon_seo_option( 'home_description' ) ),
		esc_html__( 'Shown under the site name in search results. Aim for 120 to 160 characters. Empty uses the site tagline.', 'saxon-seo-basics' )
	);
}

/**
 * Default image field with the media picker.
 */
function saxon_seo_field_default_image() {
	$id  = (int) saxon_seo_option( 'default_image' );
	$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
	?>
	<div class="saxon-seo-image" data-saxon-seo-image>
		<input type="hidden" id="saxon_seo_default_image" name="saxon_seo_options[default_image]" value="<?php echo esc_attr( $id ? (string) $id : '' ); ?>">
		<img class="saxon-seo-image__preview" src="<?php echo esc_url( (string) $src ); ?>" alt="" <?php echo $src ? '' : 'hidden'; ?>>
		<p>
			<button type="button" class="button" data-saxon-seo-image-choose><?php esc_html_e( 'Choose image', 'saxon-seo-basics' ); ?></button>
			<button type="button" class="button-link button-link-delete" data-saxon-seo-image-remove <?php echo $id ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'saxon-seo-basics' ); ?></button>
		</p>
		<p class="description"><?php esc_html_e( 'Used when a page has no featured image. 1200 by 630 pixels works best on social sites.', 'saxon-seo-basics' ); ?></p>
	</div>
	<?php
}

/**
 * Twitter username field.
 */
function saxon_seo_field_twitter_site() {
	printf(
		'<input type="text" class="regular-text" id="saxon_seo_twitter_site" name="saxon_seo_options[twitter_site]" value="%s" placeholder="yourcompany" spellcheck="false">',
		esc_attr( saxon_seo_option( 'twitter_site' ) )
	);
}

/**
 * Organization type field.
 */
function saxon_seo_field_org_type() {
	echo '<select id="saxon_seo_org_type" name="saxon_seo_options[org_type]">';
	foreach ( saxon_seo_org_types() as $value => $label ) {
		printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $value ), selected( saxon_seo_option( 'org_type' ), $value, false ), esc_html( $label ) );
	}
	echo '</select>';
}

/**
 * Organization name field.
 */
function saxon_seo_field_org_name() {
	printf(
		'<input type="text" class="regular-text" id="saxon_seo_org_name" name="saxon_seo_options[org_name]" value="%1$s" placeholder="%2$s">',
		esc_attr( saxon_seo_option( 'org_name' ) ),
		esc_attr( get_bloginfo( 'name' ) )
	);
}

/**
 * Organization phone field.
 */
function saxon_seo_field_org_phone() {
	printf(
		'<input type="tel" class="regular-text" id="saxon_seo_org_phone" name="saxon_seo_options[org_phone]" value="%s">',
		esc_attr( saxon_seo_option( 'org_phone' ) )
	);
}

/**
 * Organization email field.
 */
function saxon_seo_field_org_email() {
	printf(
		'<input type="email" class="regular-text" id="saxon_seo_org_email" name="saxon_seo_options[org_email]" value="%s">',
		esc_attr( saxon_seo_option( 'org_email' ) )
	);
}

/**
 * Social profiles field.
 */
function saxon_seo_field_social() {
	printf(
		'<textarea class="large-text code" rows="4" id="saxon_seo_social" name="saxon_seo_options[social]" aria-describedby="saxon_seo_social_help">%1$s</textarea><p class="description" id="saxon_seo_social_help">%2$s</p>',
		esc_textarea( implode( "\n", (array) saxon_seo_option( 'social' ) ) ),
		esc_html__( 'One profile URL per line, for example your LinkedIn company page.', 'saxon-seo-basics' )
	);
}

/**
 * Admin assets on the settings page and post editing screens.
 *
 * @param string $hook Admin page hook.
 */
function saxon_seo_admin_assets( $hook ) {
	$is_settings = 'settings_page_saxon-seo-basics' === $hook;
	$is_editor   = in_array( $hook, array( 'post.php', 'post-new.php' ), true );
	if ( ! $is_settings && ! $is_editor ) {
		return;
	}

	if ( $is_settings ) {
		wp_enqueue_media();
	}

	wp_enqueue_style( 'saxon-seo-admin', SAXON_SEO_URL . 'assets/css/admin.css', array(), saxon_seo_asset_version( 'assets/css/admin.css' ) );
	wp_enqueue_script( 'saxon-seo-admin', SAXON_SEO_URL . 'assets/js/admin.js', array(), saxon_seo_asset_version( 'assets/js/admin.js' ), true );
	wp_localize_script(
		'saxon-seo-admin',
		'saxonSeoAdmin',
		array(
			/* translators: 1: characters used, 2: recommended maximum. */
			'counter'     => __( '%1$d of about %2$d characters', 'saxon-seo-basics' ),
			'chooseTitle' => __( 'Choose a default share image', 'saxon-seo-basics' ),
			'chooseLabel' => __( 'Use this image', 'saxon-seo-basics' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'saxon_seo_admin_assets' );

/**
 * Cache busting version based on file modification time.
 *
 * @param string $relative_path Path relative to the plugin root.
 * @return string
 */
function saxon_seo_asset_version( $relative_path ) {
	$file = SAXON_SEO_DIR . $relative_path;
	return file_exists( $file ) ? SAXON_SEO_VERSION . '.' . filemtime( $file ) : SAXON_SEO_VERSION;
}
