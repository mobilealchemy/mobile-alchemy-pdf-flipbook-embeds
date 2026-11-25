<?php
/**
 * Plugin Name:       Mobile Alchemy PDF Flipbook Embeds
 * Plugin URI:        https://flipbookmaker.co/
 * Description:       Show your FlipbookMaker flipbooks on your site. Paste a flipbook link into a post, or use the [flipbookmaker] shortcode.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Mobile Alchemy Ltd
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mobile-alchemy-pdf-flipbook-embeds
 *
 * @package FlipbookMaker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A flipbook's share or embed link: https://flipbookmaker.co/v/{slug} or /e/{slug}. The slug is a random code
 * or a name with single hyphens between letters and digits, like spring-catalog.
 */
const FLIPBOOKMAKER_LINK = '#^https?://(?:www\.)?flipbookmaker\.co/[ve]/([A-Za-z0-9]+(?:-[A-Za-z0-9]+)*)/?(?:[?\#].*)?$#i';

/**
 * Pasted FlipbookMaker links become embedded flipbooks, in the block editor and the classic editor.
 */
function flipbookmaker_register_oembed() {
	wp_oembed_add_provider( '#https?://(www\.)?flipbookmaker\.co/v/.+#i', 'https://flipbookmaker.co/oembed', true );
}
add_action( 'init', 'flipbookmaker_register_oembed' );

/**
 * The share slug in a FlipbookMaker link, or '' when the link isn't one.
 *
 * @param mixed $url A flipbook link.
 * @return string
 */
function flipbookmaker_slug( $url ) {
	if ( is_string( $url ) && preg_match( FLIPBOOKMAKER_LINK, trim( $url ), $match ) ) {
		return $match[1];
	}
	return '';
}

/**
 * [flipbookmaker url="https://flipbookmaker.co/v/your-book" height="600" title="Spring catalog"]
 *
 * Without a height, the flipbook fills the width in the same 16:10 frame as FlipbookMaker's own
 * embed code.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function flipbookmaker_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'url'    => '',
			'height' => '',
			'title'  => '',
		),
		$atts,
		'flipbookmaker'
	);

	$slug = flipbookmaker_slug( $atts['url'] );
	if ( '' === $slug ) {
		// Only people who can fix the post see why nothing shows.
		if ( current_user_can( 'edit_posts' ) ) {
			return '<p>' . esc_html__( 'FlipbookMaker: add a flipbook link to the shortcode, like url="https://flipbookmaker.co/v/your-book".', 'mobile-alchemy-pdf-flipbook-embeds' ) . '</p>';
		}
		return '';
	}

	$src    = 'https://flipbookmaker.co/e/' . rawurlencode( $slug );
	$title  = '' !== trim( (string) $atts['title'] ) ? (string) $atts['title'] : __( 'Flipbook', 'mobile-alchemy-pdf-flipbook-embeds' );
	$height = absint( $atts['height'] );

	if ( $height > 0 ) {
		return sprintf(
			'<iframe class="flipbookmaker-embed" src="%1$s" width="100%%" height="%2$d" style="border:0;border-radius:12px" allowfullscreen loading="lazy" title="%3$s"></iframe>',
			esc_url( $src ),
			$height,
			esc_attr( $title )
		);
	}

	return sprintf(
		'<div class="flipbookmaker-embed" style="position:relative;width:100%%;aspect-ratio:16/10"><iframe src="%1$s" style="position:absolute;inset:0;width:100%%;height:100%%;border:0;border-radius:12px" allowfullscreen loading="lazy" title="%2$s"></iframe></div>',
		esc_url( $src ),
		esc_attr( $title )
	);
}
add_shortcode( 'flipbookmaker', 'flipbookmaker_shortcode' );
