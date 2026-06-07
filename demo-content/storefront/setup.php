<?php
/**
 * Maple & Stone - Storefront showcase setup.
 * Runs once after the WXR import (see build_blueprints.py / blueprint runPHP).
 */
require_once '/wordpress/wp-load.php';

/* ---- Remove default WordPress sample content ---------------------------- */
foreach ( array( 1, 2, 3 ) as $def ) { // Hello world, Sample Page, Privacy Policy
	if ( get_post( $def ) ) {
		wp_delete_post( $def, true );
	}
}

/* ---- Brand palette (warm walnut + caramel) ------------------------------ */
$mods = array(
	'storefront_header_background_color' => '#5b4636',
	'storefront_header_text_color'       => '#e9ddcf',
	'storefront_header_link_color'       => '#ffffff',
	'storefront_heading_color'           => '#3a2e26',
	'storefront_text_color'              => '#5c5048',
	'storefront_accent_color'            => '#b57a52',
	'storefront_hero_heading_color'      => '#ffffff',
	'storefront_hero_text_color'         => '#f3ead8',
	'storefront_button_background_color' => '#5b4636',
	'storefront_button_text_color'       => '#ffffff',
	'storefront_button_alt_background_color' => '#b57a52',
	'storefront_button_alt_text_color'   => '#ffffff',
	'storefront_footer_background_color'  => '#efe7dd',
	'storefront_footer_heading_color'     => '#3a2e26',
	'storefront_footer_text_color'        => '#5c5048',
	'storefront_footer_link_color'        => '#5b4636',
);
foreach ( $mods as $k => $v ) {
	set_theme_mod( $k, $v );
}

/* ---- Static front page (the Homepage template) -------------------------- */
$home = get_page_by_path( 'homepage' );
if ( $home ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home->ID );
}

/* ---- WooCommerce store basics + showcase refresh ------------------------ */
update_option( 'woocommerce_currency', 'EUR' );
update_option( 'woocommerce_currency_pos', 'left_space' );
update_option( 'woocommerce_default_country', 'NL' );
update_option( 'woocommerce_store_address', 'Keizersgracht 123' );
update_option( 'woocommerce_store_city', 'Amsterdam' );
update_option( 'woocommerce_demo_store', 'no' );
// Re-save every product through the WooCommerce CRUD so the product lookup
// table (price, on-sale, featured) is rebuilt - the raw WXR import skips this,
// which otherwise leaves the "On Sale" homepage section empty.
if ( function_exists( 'wc_get_products' ) ) {
	$ids = wc_get_products( array( 'limit' => -1, 'return' => 'ids', 'status' => 'publish' ) );
	foreach ( $ids as $pid ) {
		$p = wc_get_product( $pid );
		if ( $p ) {
			$p->save();
		}
	}
	delete_transient( 'wc_products_onsale' );
	delete_transient( 'wc_featured_products' );
	wc_delete_product_transients();
}

/* ---- Primary menu location --------------------------------------------- */
$menu = get_term_by( 'slug', 'primary-menu', 'nav_menu' );
if ( $menu ) {
	$locations            = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu->term_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/* ---- Footer widgets (block widgets) ------------------------------------ */
$blocks = get_option( 'widget_block', array() );
if ( ! is_array( $blocks ) ) {
	$blocks = array();
}
$sidebars = get_option( 'sidebars_widgets', array() );

function ms_block( &$blocks, $content ) {
	$i = 2;
	while ( isset( $blocks[ $i ] ) ) {
		$i++;
	}
	$blocks[ $i ] = array( 'content' => $content );
	return 'block-' . $i;
}

$sidebars['footer-1'] = array( ms_block( $blocks,
	'<!-- wp:heading {"level":3} --><h3>Maple &amp; Stone</h3><!-- /wp:heading -->'
	. '<!-- wp:paragraph --><p>Beautifully made homeware and decor, chosen to be used and loved for years. Independent makers, natural materials, quiet design.</p><!-- /wp:paragraph -->' ) );

$sidebars['footer-2'] = array( ms_block( $blocks,
	'<!-- wp:heading {"level":3} --><h3>Shop</h3><!-- /wp:heading -->'
	. '<!-- wp:list --><ul>'
	. '<li><a href="/product-category/living/">Living</a></li>'
	. '<li><a href="/product-category/kitchen/">Kitchen</a></li>'
	. '<li><a href="/product-category/decor/">Decor</a></li>'
	. '<li><a href="/shop/">All products</a></li>'
	. '</ul><!-- /wp:list -->' ) );

$sidebars['footer-3'] = array( ms_block( $blocks,
	'<!-- wp:heading {"level":3} --><h3>Customer care</h3><!-- /wp:heading -->'
	. '<!-- wp:list --><ul>'
	. '<li><a href="/about/">About us</a></li>'
	. '<li>Free delivery over &euro;75</li>'
	. '<li>30-day returns</li>'
	. '<li>hello@mapleandstone.example</li>'
	. '</ul><!-- /wp:list -->' ) );

$sidebars['footer-4'] = array( ms_block( $blocks,
	'<!-- wp:heading {"level":3} --><h3>Visit the studio</h3><!-- /wp:heading -->'
	. '<!-- wp:paragraph --><p>Keizersgracht 123<br>1015 CJ Amsterdam<br>Open Thu&ndash;Sun, 10&ndash;18h</p><!-- /wp:paragraph -->' ) );

$blocks['_multiwidget'] = 1;
update_option( 'widget_block', $blocks );
update_option( 'sidebars_widgets', $sidebars );

flush_rewrite_rules();
