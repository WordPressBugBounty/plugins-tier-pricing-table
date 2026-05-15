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
		) );
		
		$this->id              = $id;
		$this->title           = $args['title'];
		$this->backgroundColor = $args['background_color'];
		$this->textColor       = $args['text_color'];
		$this->borderColor     = $args['border_color'];
		$this->style           = $args['style'];
		$this->CSSClass        = $args['css_class'];
		$this->fontSize        = intval( $args['font_size'] );
		$this->borderWidth     = intval( $args['border_width'] );
		$this->paddingX        = intval( $args['padding_x'] );
		$this->paddingY        = intval( $args['padding_y'] );
		$this->borderRadius    = intval( $args['border_radius'] );
		$this->borderStyle     = $args['border_style'];
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
	
	public function getBorderStyle() {
		return $this->borderStyle;
	}
	
	public function getCSSClass(): ?string {
		return $this->CSSClass;
	}
	
	public function toArray(): array {
		return array(
			'id'               => $this->getId(),
			'title'            => $this->getTitle(),
			'background_color' => $this->getBackgroundColor(),
			'text_color'       => $this->getTextColor(),
			'border_color'     => $this->getBorderColor(),
			'style'            => $this->getStyle(),
			'css_class'        => $this->getCSSClass(),
			'font_size'        => $this->getFontSize(),
			'border_width'     => $this->getBorderWidth(),
			'padding_x'        => $this->getPaddingX(),
			'padding_y'        => $this->getPaddingY(),
			'border_radius'    => $this->getBorderRadius(),
			'border_style'     => $this->getBorderStyle(),
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
		
		$labelHTML = '<span id="' . esc_attr( $this->getId() ) . '" class="tiered-pricing-tier-label' . $cssClass . '" style="' . $style . '">' . esc_html( $this->getTitle() ) . '</span>';
		
		return apply_filters( 'tiered_pricing_table/addons/tier_labels/label_html', $labelHTML, $this );
	}
}