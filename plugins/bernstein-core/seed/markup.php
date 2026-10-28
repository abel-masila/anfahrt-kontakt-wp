<?php
/**
 * Kleine Helfer, die gültiges Block-Markup erzeugen (so, wie der Block-Editor es speichert).
 */

defined( 'ABSPATH' ) || exit;

function bms_attrs( array $attrs ): string {
	return $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '';
}

/** Absatz. $html darf einfaches Inline-HTML enthalten (Links, <br>, <strong>). */
function bms_p( string $html, string $class = '' ): string {
	$attrs = $class ? array( 'className' => $class ) : array();
	$open  = $class ? '<p class="' . esc_attr( $class ) . '">' : '<p>';
	return "<!-- wp:paragraph" . bms_attrs( $attrs ) . " -->\n{$open}{$html}</p>\n<!-- /wp:paragraph -->";
}

function bms_h( string $text, int $level = 2, string $class = '' ): string {
	$attrs = array();
	if ( 2 !== $level ) {
		$attrs['level'] = $level;
	}
	if ( $class ) {
		$attrs['className'] = $class;
	}
	$classes = trim( 'wp-block-heading ' . $class );
	return "<!-- wp:heading" . bms_attrs( $attrs ) . " -->\n<h{$level} class=\"{$classes}\">" . esc_html( $text ) . "</h{$level}>\n<!-- /wp:heading -->";
}

function bms_group( array $inner, string $class, string $tag = 'div' ): string {
	$attrs = array();
	if ( 'div' !== $tag ) {
		$attrs['tagName'] = $tag;
	}
	$attrs['className'] = $class;
	return "<!-- wp:group" . bms_attrs( $attrs ) . " -->\n<{$tag} class=\"wp-block-group {$class}\">" . implode( "\n\n", $inner ) . "</{$tag}>\n<!-- /wp:group -->";
}

function bms_section( array $inner, string $class = '' ): string {
	return bms_group( $inner, trim( 'bm-section ' . $class ), 'section' );
}

/** Überschriften-Gruppe: Dachzeile, Überschrift, Einleitung. */
function bms_heading( string $eyebrow, string $title, string $intro = '', int $level = 2, string $class = '' ): string {
	$inner = array( bms_p( esc_html( $eyebrow ), 'bm-eyebrow' ), bms_h( $title, $level ) );
	if ( $intro ) {
		$inner[] = bms_p( esc_html( $intro ), 'bm-intro' );
	}
	return bms_group( $inner, trim( 'bm-heading ' . $class ) );
}

/** Dynamischer Block. */
function bms_block( string $name, array $attrs = array() ): string {
	return '<!-- wp:bernstein/' . $name . bms_attrs( $attrs ) . ' /-->';
}

/** Buttons: [ [Text, URL, Stil], … ] – Stil z. B. 'red', 'ghost', 'outline' oder ''. */
function bms_buttons( array $buttons, string $class = '' ): string {
	$items = array();
	foreach ( $buttons as [ $text, $url, $style ] ) {
		$bclass  = $style ? 'is-style-' . $style : '';
		$attrs   = $bclass ? array( 'className' => $bclass ) : array();
		$items[] = "<!-- wp:button" . bms_attrs( $attrs ) . " -->\n<div class=\"" . trim( 'wp-block-button ' . $bclass ) . "\"><a class=\"wp-block-button__link wp-element-button\" href=\"" . esc_attr( $url ) . "\">" . esc_html( $text ) . "</a></div>\n<!-- /wp:button -->";
	}
	$attrs = $class ? array( 'className' => $class ) : array();
	return "<!-- wp:buttons" . bms_attrs( $attrs ) . " -->\n<div class=\"" . trim( 'wp-block-buttons ' . $class ) . "\">" . implode( "\n\n", $items ) . "</div>\n<!-- /wp:buttons -->";
}

/** „Mehr“-Link mit Pfeil. */
function bms_more( string $text, string $url ): string {
	return bms_p( '<a href="' . esc_attr( $url ) . '">' . esc_html( $text ) . '</a>', 'bm-more' );
}

function bms_image( int $id, string $class = '' ): string {
	$attrs = array( 'id' => $id, 'sizeSlug' => 'large', 'linkDestination' => 'none' );
	if ( $class ) {
		$attrs['className'] = $class;
	}
	$alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
	$src = (string) wp_get_attachment_url( $id );
	return "<!-- wp:image" . bms_attrs( $attrs ) . " -->\n<figure class=\"" . trim( 'wp-block-image size-large ' . $class ) . "\"><img src=\"" . esc_url( $src ) . "\" alt=\"" . esc_attr( $alt ) . "\" class=\"wp-image-{$id}\"/></figure>\n<!-- /wp:image -->";
}

/** Zwei Spalten. */
function bms_columns( array $columns, string $class, bool $center = false ): string {
	$cols = array();
	foreach ( $columns as $inner ) {
		if ( $center ) {
			$cols[] = "<!-- wp:column {\"verticalAlignment\":\"center\"} -->\n<div class=\"wp-block-column is-vertically-aligned-center\">" . implode( "\n\n", $inner ) . "</div>\n<!-- /wp:column -->";
		} else {
			$cols[] = "<!-- wp:column -->\n<div class=\"wp-block-column\">" . implode( "\n\n", $inner ) . "</div>\n<!-- /wp:column -->";
		}
	}
	$attrs = $center ? array( 'verticalAlignment' => 'center', 'className' => $class ) : array( 'className' => $class );
	$wrap  = $center ? "wp-block-columns are-vertically-aligned-center {$class}" : "wp-block-columns {$class}";
	return "<!-- wp:columns" . bms_attrs( $attrs ) . " -->\n<div class=\"{$wrap}\">" . implode( "\n\n", $cols ) . "</div>\n<!-- /wp:columns -->";
}

function bms_quote( string $text, string $cite ): string {
	return "<!-- wp:quote {\"className\":\"bm-quote\"} -->\n<blockquote class=\"wp-block-quote bm-quote\">" . bms_p( esc_html( $text ) ) . '<cite>' . esc_html( $cite ) . "</cite></blockquote>\n<!-- /wp:quote -->";
}

/** Titelbild (Cover-Block) für die Startseite. */
function bms_hero( int $image_id, array $inner ): string {
	$src   = esc_url( (string) wp_get_attachment_url( $image_id ) );
	$attrs = array(
		'url'                => (string) wp_get_attachment_url( $image_id ),
		'id'                 => $image_id,
		'dimRatio'           => 70,
		'overlayColor'       => 'navy-dark',
		'isUserOverlayColor' => true,
		'minHeight'          => 80,
		'minHeightUnit'      => 'vh',
		'className'          => 'bm-hero',
	);
	return "<!-- wp:cover" . bms_attrs( $attrs ) . " -->\n"
		. "<div class=\"wp-block-cover bm-hero\" style=\"min-height:80vh\">"
		. "<img class=\"wp-block-cover__image-background wp-image-{$image_id}\" alt=\"\" src=\"{$src}\" data-object-fit=\"cover\"/>"
		. "<span aria-hidden=\"true\" class=\"wp-block-cover__background has-navy-dark-background-color has-background-dim-70 has-background-dim\"></span>"
		. "<div class=\"wp-block-cover__inner-container\">" . implode( "\n\n", $inner ) . "</div></div>\n<!-- /wp:cover -->";
}
