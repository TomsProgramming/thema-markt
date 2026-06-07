<?php
/**
 * Petal & Bloom - Blossom Feminine showcase setup.
 * Runs once after the WXR import (see build_blueprints.py / blueprint runPHP).
 */
require_once '/wordpress/wp-load.php';

/* ---- Remove default WordPress sample content ---------------------------- */
foreach ( array( 1, 2, 3 ) as $def ) { // Hello world, Sample Page, Privacy Policy
	if ( get_post( $def ) ) {
		wp_delete_post( $def, true );
	}
}

/* ---- Homepage sections (banner slider + featured cards) ----------------- */
set_theme_mod( 'primary_color', '#e89ab9' );
set_theme_mod( 'ed_slider', true );
set_theme_mod( 'slider_type', 'latest_posts' );
set_theme_mod( 'no_of_slides', 3 );
set_theme_mod( 'ed_featured_area', true );

$slugs = array( 'featured_content_one' => 'fashion', 'featured_content_two' => 'travel', 'featured_content_three' => 'beauty' );
foreach ( $slugs as $mod => $slug ) {
	$p = get_page_by_path( $slug );
	if ( $p ) {
		set_theme_mod( $mod, $p->ID );
	}
}

/* keep the empty newsletter / instagram sections switched off */
set_theme_mod( 'ed_newsletter', false );
set_theme_mod( 'ed_instagram', false );

/* ---- Primary menu location --------------------------------------------- */
$menu = get_term_by( 'slug', 'primary-menu', 'nav_menu' );
if ( $menu ) {
	$locations            = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu->term_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/* ---- Widgets (sidebar + four footer columns) --------------------------- */
$img = 'https://raw.githubusercontent.com/TomsProgramming/thema-markt/main/demo-content/blossom-feminine/images/about-emma.jpg';

$blocks = get_option( 'widget_block', array() );
if ( ! is_array( $blocks ) ) {
	$blocks = array();
}
$sidebars = get_option( 'sidebars_widgets', array() );

function pb_block( &$blocks, $content ) {
	$i = 2;
	while ( isset( $blocks[ $i ] ) ) {
		$i++;
	}
	$blocks[ $i ] = array( 'content' => $content );
	return 'block-' . $i;
}

$about = '<!-- wp:heading {"level":3} --><h3>About Petal &amp; Bloom</h3><!-- /wp:heading -->'
	. '<!-- wp:image {"sizeSlug":"large","className":"is-style-rounded"} --><figure class="wp-block-image size-large is-style-rounded"><img src="' . $img . '" alt="Emma"/></figure><!-- /wp:image -->'
	. '<!-- wp:paragraph --><p>Hi, I\'m Emma. Petal &amp; Bloom is my little corner of the internet for slow living, seasonal style and beautiful places. So glad you\'re here.</p><!-- /wp:paragraph -->';

$explore = '<!-- wp:heading {"level":3} --><h3>Explore</h3><!-- /wp:heading -->'
	. '<!-- wp:categories {"showPostCounts":true} /-->';

$latest = '<!-- wp:heading {"level":3} --><h3>On the journal</h3><!-- /wp:heading -->'
	. '<!-- wp:latest-posts {"postsToShow":4,"displayPostDate":true,"displayFeaturedImage":true,"featuredImageSizeSlug":"thumbnail"} /-->';

$follow = '<!-- wp:heading {"level":3} --><h3>Stay in touch</h3><!-- /wp:heading -->'
	. '<!-- wp:paragraph --><p>New stories every week on lifestyle, fashion, travel, food and beauty.</p><!-- /wp:paragraph -->'
	. '<!-- wp:social-links --><ul class="wp-block-social-links"><!-- wp:social-link {"service":"instagram"} /--><!-- wp:social-link {"service":"pinterest"} /--><!-- wp:social-link {"service":"tiktok"} /--></ul><!-- /wp:social-links -->';

$search = '<!-- wp:search {"label":"Search","buttonText":"Search"} /-->';

$sidebars['sidebar']      = array(
	pb_block( $blocks, $about ),
	pb_block( $blocks, $search ),
	pb_block( $blocks, $latest ),
	pb_block( $blocks, $explore ),
);
$sidebars['footer-one']   = array( pb_block( $blocks, $about ) );
$sidebars['footer-two']   = array( pb_block( $blocks, $explore ) );
$sidebars['footer-three'] = array( pb_block( $blocks, $latest ) );
$sidebars['footer-four']  = array( pb_block( $blocks, $follow ) );

$blocks['_multiwidget'] = 1;
update_option( 'widget_block', $blocks );
update_option( 'sidebars_widgets', $sidebars );

flush_rewrite_rules();
