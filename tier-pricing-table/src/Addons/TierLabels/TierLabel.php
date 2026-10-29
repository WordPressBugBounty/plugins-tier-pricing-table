<?php namespace TierPricingTable\Addons\TierLabels;

class TierLabel {
	
	private $id;
	private $title;
	private $backgroundColor;
	private $textColor;
	private $style;
	private $borderColor;
	private $CSSClass;
	
	private $fontSize;
	private $borderWidth;
	private $paddingX;
	private $paddingY;
	private $borderRadius;
	private $borderStyle;
	private $icon;
	private $activeStyle;
	private $activeBackgroundColor;
	private $activeTextColor;

	const ACTIVE_INVERT = 'invert';
	const ACTIVE_KEEP   = 'keep';
	const ACTIVE_CUSTOM = 'custom';
	
	public function __construct( $id, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'title'            => '',
			'background_color' => '#333',
			'text_color'       => '#fff',
			'border_color'     => '',
			'style'            => 'square',
			'css_class'        => '',
			'font_size'        => 12,
			'border_width'     => 0,
			'padding_x'        => 8,
			'padding_y'        => 2,
			'border_radius'    => 4,
			'border_style'     => 'solid',
			'icon'             => '',
			// on the active tier of a table the label swaps to its active colours; inverted by default
			'active_style'            => self::ACTIVE_INVERT,
			'active_background_color' => '',
			'active_text_color'       => '',
		) );
		
		$this->id              = $id;
		$this->title           = $args['title'];
		$this->backgroundColor = $args['background_color'];
		$this->textColor       = $args['text_color'];
		$this->borderColor     = $args['border_color'];
		$this->style           = $args['style'];
		$this->CSSClass        = $args['css_class'];
		$this->fontSize        = isset($args['font_size']) ? (int) $args['font_size'] : 12;
		$this->borderWidth     = isset($args['border_width']) ? (int) $args['border_width'] : 0;
		$this->paddingX        = isset($args['padding_x']) ? (int) $args['padding_x'] : 8;
		$this->paddingY        = isset($args['padding_y']) ? (int) $args['padding_y'] : 2;
		$this->borderRadius    = isset($args['border_radius']) ? (int) $args['border_radius'] : 4;
		$this->borderStyle     = $args['border_style'] ?? 'solid';
		$this->icon            = $args['icon'] ?? '';

		$activeStyle                 = (string) ( $args['active_style'] ?? self::ACTIVE_INVERT );
		$this->activeStyle           = in_array( $activeStyle, self::getActiveStyles(), true ) ? $activeStyle : self::ACTIVE_INVERT;
		$this->activeBackgroundColor = (string) ( $args['active_background_color'] ?? '' );
		$this->activeTextColor       = (string) ( $args['active_text_color'] ?? '' );
	}

	/**
	 * How the label behaves on the active tier: inverted colours, unchanged, or its own pair.
	 */
	public static function getActiveStyles(): array {
		return array( self::ACTIVE_INVERT, self::ACTIVE_KEEP, self::ACTIVE_CUSTOM );
	}

	public function getActiveStyle(): string {
		return $this->activeStyle;
	}

	public function getActiveBackgroundColor(): string {
		return $this->activeBackgroundColor;
	}

	public function getActiveTextColor(): string {
		return $this->activeTextColor;
	}

	/**
	 * The colours the label takes on the active tier, or null when it keeps its own.
	 *
	 * Inverted: a solid label swaps background and text; an outline or dashed label fills with white and keeps
	 * its text colour, so it stays readable on the coloured row. Custom: the stored pair, each falling back to
	 * the inverted value when empty.
	 *
	 * @return array{background: string, color: string}|null
	 */
	public function getActiveColors(): ?array {

		if ( self::ACTIVE_KEEP === $this->activeStyle ) {
			return null;
		}

		$background = $this->getBackgroundColor() ?: '#2271b1';
		$text       = $this->getTextColor() ?: '#ffffff';
		$outlined   = in_array( $this->getBorderStyle(), array( 'outline', 'dashed' ), true );

		$inverted = $outlined
			? array( 'background' => '#ffffff', 'color' => $text )
			: array( 'background' => $text, 'color' => $background );

		if ( self::ACTIVE_CUSTOM === $this->activeStyle ) {
			return array(
				'background' => $this->activeBackgroundColor ?: $inverted['background'],
				'color'      => $this->activeTextColor ?: $inverted['color'],
			);
		}

		return $inverted;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public function getBackgroundColor() {
		return $this->backgroundColor;
	}
	
	public function getTextColor() {
		return $this->textColor;
	}
	
	public function getStyle() {
		return $this->style;
	}
	
	public function getBorderColor() {
		return $this->borderColor;
	}
	
	public function getFontSize() {
		return $this->fontSize;
	}
	
	public function getBorderWidth() {
		return $this->borderWidth;
	}
	
	public function getPaddingX() {
		return $this->paddingX;
	}
	
	public function getPaddingY() {
		return $this->paddingY;
	}
	
	public function getBorderRadius() {
		return $this->borderRadius;
	}
	
	public function getBorderStyle(): string {
		return $this->borderStyle;
	}
	
	public function getIcon(): string {
		return $this->icon;
	}
	
	public function getCSSClass(): ?string {
		return $this->CSSClass;
	}
	
	public function toArray(): array {
		return array(
			'id'               => $this->id,
			'title'            => $this->title,
			'background_color' => $this->backgroundColor,
			'text_color'       => $this->textColor,
			'border_color'     => $this->borderColor,
			'style'            => $this->style,
			'css_class'        => $this->CSSClass,
			'font_size'        => $this->fontSize,
			'border_width'     => $this->borderWidth,
			'padding_x'        => $this->paddingX,
			'padding_y'        => $this->paddingY,
			'border_radius'    => $this->borderRadius,
			'border_style'     => $this->borderStyle,
			'icon'             => $this->icon,
			'active_style'            => $this->activeStyle,
			'active_background_color' => $this->activeBackgroundColor,
			'active_text_color'       => $this->activeTextColor,
		);
	}
	
	public function isValid(): bool {
		return ! empty( $this->id ) && ! empty( $this->title );
	}
	
	public static function fromArray( array $data ): self {
		return new self( $data['id'], $data );
	}
	
	public function render(): string {
		$style = '';
		
		$bgColor     = $this->getBackgroundColor() ?: '#2271b1';
		$textColor   = $this->getTextColor() ?: '#ffffff';
		$borderColor = $this->getBorderColor();
		
		$fontSize     = $this->getFontSize() ?: 12;
		$borderWidth  = $this->getBorderWidth() !== null ? $this->getBorderWidth() : 0;
		$paddingX     = $this->getPaddingX() !== null ? $this->getPaddingX() : 8;
		$paddingY     = $this->getPaddingY() !== null ? $this->getPaddingY() : 2;
		$borderRadius = $this->getBorderRadius() !== null ? $this->getBorderRadius() : 4;
		$borderStyle  = $this->getBorderStyle() ?: 'solid';
		
		if ( $borderStyle === 'outline' || $borderStyle === 'dashed' ) {
			$style .= 'background-color: transparent;';
		} else {
			$style .= 'background-color: ' . esc_attr( $bgColor ) . ';';
		}
		
		$style .= 'color: ' . esc_attr( $textColor ) . ';';
		$style .= 'padding: ' . esc_attr( $paddingY ) . 'px ' . esc_attr( $paddingX ) . 'px;';
		$style .= 'font-size: ' . esc_attr( $fontSize ) . 'px;';
		$style .= 'font-weight: 600;';
		$style .= 'display: inline-block;';
		$style .= 'white-space: nowrap;';
		$style .= 'overflow: hidden;';
		$style .= 'width: max-content;';
		$style .= 'border-radius: ' . esc_attr( $borderRadius ) . 'px;';
		
		if ( $borderStyle === 'dashed' ) {
			$actualBorderColor = $borderColor ?: $textColor;
			$style             .= 'border: ' . esc_attr( $borderWidth ?: 2 ) . 'px dashed ' . esc_attr( $actualBorderColor ) . ';';
		} elseif ( $borderStyle === 'outline' ) {
			$actualBorderColor = $borderColor ?: $textColor;
			$style             .= 'border: ' . esc_attr( $borderWidth ?: 2 ) . 'px solid ' . esc_attr( $actualBorderColor ) . ';';
		} elseif ( $borderWidth > 0 ) {
			$style .= 'border: ' . esc_attr( $borderWidth ) . 'px solid ' . esc_attr( $borderColor ?: '#000000' ) . ';';
		}
		
		$cssClass = $this->getCSSClass() ? ' ' . esc_attr( $this->getCSSClass() ) : '';

		// the stylesheet applies these on the active tier of the table layouts
		$activeColors = $this->getActiveColors();

		if ( $activeColors ) {
			$cssClass .= ' tiered-pricing-tier-label--active-aware';
			$style    .= '--tpt-label-active-bg: ' . esc_attr( $activeColors['background'] ) . ';';
			$style    .= '--tpt-label-active-color: ' . esc_attr( $activeColors['color'] ) . ';';
		}

		$iconSvg = '';
		if ( $this->getIcon() ) {
			$iconSvg = self::getIconSvg( $this->getIcon() );
			// Adjust layout for icon + text
			$style .= 'display: inline-flex; align-items: center; justify-content: center; gap: 2px;';
		}
		
		$labelHTML = '<span id="' . esc_attr( $this->getId() ) . '" class="tiered-pricing-tier-label' . $cssClass . '" style="' . $style . '">' . $iconSvg . esc_html( $this->getTitle() ) . '</span>';
		
		return apply_filters( 'tiered_pricing_table/addons/tier_labels/label_html', $labelHTML, $this );
	}

	/**
	 * The icon set (Feather, MIT): name => inner SVG markup of a 24x24 stroked icon. One registry for the
	 * storefront and the editor.
	 */
	public static function getIcons(): array {
		return array(
			'star'              => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>',
			'fire'              => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path>',
			'lightning'         => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>',
			'tag'               => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line>',
			'crown'             => '<path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"></path>',
			'percent'           => '<line x1="19" y1="5" x2="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle>',
			'award'             => '<circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>',
			'truck'             => '<rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle>',
			'gift'              => '<polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>',
			'package'           => '<line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>',
			'clock'             => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
			'check-circle'      => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
			'heart'             => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>',
			'thumbs-up'         => '<path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>',
			'trending-up'       => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline>',
			'arrow-down-circle' => '<circle cx="12" cy="12" r="10"></circle><polyline points="8 12 12 16 16 12"></polyline><line x1="12" y1="8" x2="12" y2="16"></line>',
			'users'             => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
			'shield'            => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
		);
	}

	/**
	 * Icon names for the editor.
	 */
	public static function getIconNames(): array {
		return array(
			'star'              => __( 'Star', 'tier-pricing-table' ),
			'fire'              => __( 'Fire', 'tier-pricing-table' ),
			'lightning'         => __( 'Lightning', 'tier-pricing-table' ),
			'tag'               => __( 'Tag', 'tier-pricing-table' ),
			'crown'             => __( 'Crown', 'tier-pricing-table' ),
			'percent'           => __( 'Percent', 'tier-pricing-table' ),
			'award'             => __( 'Award', 'tier-pricing-table' ),
			'truck'             => __( 'Truck', 'tier-pricing-table' ),
			'gift'              => __( 'Gift', 'tier-pricing-table' ),
			'package'           => __( 'Package', 'tier-pricing-table' ),
			'clock'             => __( 'Clock', 'tier-pricing-table' ),
			'check-circle'      => __( 'Check', 'tier-pricing-table' ),
			'heart'             => __( 'Heart', 'tier-pricing-table' ),
			'thumbs-up'         => __( 'Thumbs up', 'tier-pricing-table' ),
			'trending-up'       => __( 'Trending up', 'tier-pricing-table' ),
			'arrow-down-circle' => __( 'Arrow down', 'tier-pricing-table' ),
			'users'             => __( 'Users', 'tier-pricing-table' ),
			'shield'            => __( 'Shield', 'tier-pricing-table' ),
		);
	}

	/**
	 * A complete, decorative SVG for an icon name; empty for an unknown name.
	 */
	public static function getIconSvg( string $iconName ): string {
		$icons = self::getIcons();

		if ( empty( $icons[ $iconName ] ) ) {
			return '';
		}

		return '<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icons[ $iconName ] . '</svg>';
	}
}