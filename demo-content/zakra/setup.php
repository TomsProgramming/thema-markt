<?php
/**
 * Lumen Consulting - Zakra showcase setup.
 * Runs once after the WXR import (see build_blueprints.py / blueprint runPHP).
 */
require_once '/wordpress/wp-load.php';

/* ---- Remove default WordPress sample content ---------------------------- */
foreach ( array( 1, 2, 3 ) as $def ) { // Hello world, Sample Page, Privacy Policy
	if ( get_post( $def ) ) {
		wp_delete_post( $def, true );
	}
}

/* ---- Static front page (Page Builder template) + blog page ------------- */
$home     = get_page_by_path( 'home' );
$insights = get_page_by_path( 'insights' );
if ( $home ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home->ID );
	update_post_meta( $home->ID, '_wp_page_template', 'page-templates/pagebuilder.php' );
}
if ( $insights ) {
	update_option( 'page_for_posts', $insights->ID );
}

/* ---- Brand accent + header button -------------------------------------- */
set_theme_mod( 'zakra_link_color', '#265666' );
set_theme_mod( 'zakra_link_hover_color', '#1a2942' );
set_theme_mod( 'zakra_header_button_color', '#ffffff' );
set_theme_mod( 'zakra_header_button_hover_color', '#ffffff' );
set_theme_mod( 'zakra_header_button_background_color', '#1a2942' );
set_theme_mod( 'zakra_header_button_background_hover_color', '#265666' );
set_theme_mod( 'zakra_footer_copyright_text', 'Lumen Consulting &mdash; Strategy, brand &amp; technology. Made in Amsterdam.' );

/* ---- Primary menu location --------------------------------------------- */
$menu = get_term_by( 'slug', 'primary-menu', 'nav_menu' );
if ( $menu ) {
	$locations                  = get_theme_mod( 'nav_menu_locations', array() );
	$locations['menu-primary'] = $menu->term_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/* ---- Footer widgets ----------------------------------------------------- */
$blocks = get_option( 'widget_block', array() );
if ( ! is_array( $blocks ) ) {
	$blocks = array();
}
$sidebars = get_option( 'sidebars_widgets', array() );

function lc_block( &$blocks, $content ) {
	$i = 2;
	while ( isset( $blocks[ $i ] ) ) {
		$i++;
	}
	$blocks[ $i ] = array( 'content' => $content );
	return 'block-' . $i;
}

$sidebars['footer-sidebar-1'] = array( lc_block( $blocks,
	'<!-- wp:heading {"level":3} --><h3>Lumen Consulting</h3><!-- /wp:heading -->'
	. '<!-- wp:paragraph --><p>We help ambitious teams turn strategy into momentum &mdash; across strategy, brand and technology.</p><!-- /wp:paragraph -->' ) );

$sidebars['footer-sidebar-2'] = array( lc_block( $blocks,
	'<!-- wp:heading {"level":3} --><h3>Services</h3><!-- /wp:heading -->'
	. '<!-- wp:list --><ul><li><a href="/services/">Strategy &amp; Growth</a></li><li><a href="/services/">Brand &amp; Marketing</a></li><li><a href="/services/">Technology &amp; Delivery</a></li><li><a href="/services/">Advisory</a></li></ul><!-- /wp:list -->' ) );

$sidebars['footer-sidebar-3'] = array( lc_block( $blocks,
	'<!-- wp:heading {"level":3} --><h3>Company</h3><!-- /wp:heading -->'
	. '<!-- wp:list --><ul><li><a href="/about/">About</a></li><li><a href="/insights/">Insights</a></li><li><a href="/contact/">Contact</a></li></ul><!-- /wp:list -->' ) );

$sidebars['footer-sidebar-4'] = array( lc_block( $blocks,
	'<!-- wp:heading {"level":3} --><h3>Get in touch</h3><!-- /wp:heading -->'
	. '<!-- wp:paragraph --><p>Keizersgracht 123<br>1015 CJ Amsterdam<br>hello@lumenconsulting.example</p><!-- /wp:paragraph -->' ) );

$blocks['_multiwidget'] = 1;
update_option( 'widget_block', $blocks );
update_option( 'sidebars_widgets', $sidebars );

flush_rewrite_rules();
