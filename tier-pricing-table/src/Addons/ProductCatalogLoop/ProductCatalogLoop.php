<?php

namespace TierPricingTable\Addons\ProductCatalogLoop;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\LayoutConfigurator\LayoutConfiguratorAddon;
use TierPricingTable\Addons\ProductCatalogLoop\Settings\ProductCatalogLoopSettingsSection;
use TierPricingTable\PriceManager;
use TierPricingTable\Settings\Settings;
use WC_Product;
use WP_Block;
class ProductCatalogLoop extends AbstractAddon {
    public function getName() : string {
        return __( 'Shop & Categories', 'tier-pricing-table' );
    }

    public function getDescription() : string {
        return __( 'Display tiered pricing tables on the shop and category pages.', 'tier-pricing-table' );
    }

    public function getIcon() : string {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 10h12v2H4zm0-4h16v2H4zm0 8h8v2H4zm10 0v6l5-3z"/></svg>';
    }

    public function getSlug() : string {
        return 'shop-loop-display';
    }

    public function getRenderAttributes() : array {
        if ( !ProductCatalogLoopSettingsSection::isCustomLayoutSettings() ) {
            return array(
                'display'     => true,
                'tiers_limit' => ProductCatalogLoopSettingsSection::getTiersLimit(),
            );
        }
        $args = array(
            'display_context'                     => 'shop-loop',
            'display'                             => true,
            'display_type'                        => ProductCatalogLoopSettingsSection::getLayoutType(),
            'title'                               => ProductCatalogLoopSettingsSection::getTitle(),
            'quantity_type'                       => ProductCatalogLoopSettingsSection::getQuantityType(),
            'getSelectedQuantityColor'            => ProductCatalogLoopSettingsSection::getSelectedQuantityColor(),
            'quantity_column_title'               => ProductCatalogLoopSettingsSection::getTableColumnsTitles()['head_quantity_text'],
            'price_column_title'                  => ProductCatalogLoopSettingsSection::getTableColumnsTitles()['head_price_text'],
            'discount_column_title'               => ProductCatalogLoopSettingsSection::getTableColumnsTitles()['head_discount_text'],
            'show_discount_column'                => ProductCatalogLoopSettingsSection::blocksShowDiscount(),
            'discount_format'                     => ProductCatalogLoopSettingsSection::getDiscountFormat(),
            'tiers_order'                         => ProductCatalogLoopSettingsSection::getTiersOrder(),
            'layout_spacing'                      => ProductCatalogLoopSettingsSection::getLayoutSpacing(),
            'cell_padding'                        => ProductCatalogLoopSettingsSection::getCellPadding(),
            'discount_badge_color'                => ProductCatalogLoopSettingsSection::getDiscountBadgeColor(),
            'active_tier_border'                  => ProductCatalogLoopSettingsSection::getActiveTierBorder(),
            'tiers_limit'                         => ProductCatalogLoopSettingsSection::getTiersLimit(),
            'clickable_rows'                      => ProductCatalogLoopSettingsSection::isClickableTableRows(),
            'active_tier_color'                   => ProductCatalogLoopSettingsSection::getSelectedQuantityColor(),
            'table_style'                         => ProductCatalogLoopSettingsSection::getTableStyle(),
            'blocks_style'                        => ProductCatalogLoopSettingsSection::getBlocksStyle(),
            'options_style'                       => ProductCatalogLoopSettingsSection::getOptionsStyle(),
            'dropdown_style'                      => ProductCatalogLoopSettingsSection::getDropdownStyle(),
            'plain_text_style'                    => ProductCatalogLoopSettingsSection::getPlainTextStyle(),
            'options_show_original_product_price' => ProductCatalogLoopSettingsSection::isShowOriginalProductPriceInOptions(),
            'options_show_default_option'         => ProductCatalogLoopSettingsSection::isShowDefaultOption(),
            'options_option_text'                 => ProductCatalogLoopSettingsSection::getOptionText(),
            'options_default_option_text'         => ProductCatalogLoopSettingsSection::getDefaultOptionText(),
            'plain_text_show_default_option'      => ProductCatalogLoopSettingsSection::isShowFirstPlainTextTier(),
            'plain_text_option_text'              => ProductCatalogLoopSettingsSection::getPlainTextTemplate(),
            'plain_text_default_option_text'      => ProductCatalogLoopSettingsSection::getFirstTierPlainTextTemplate(),
        );
        $default_quantity_measurement = array(
            'singular' => '',
            'plural'   => '',
        );
        $quantity_measurement = $default_quantity_measurement;
        if ( in_array( $args['display_type'], array('table', 'horizontal-table', 'tooltip') ) ) {
            $quantity_measurement = ProductCatalogLoopSettingsSection::getTableQuantityMeasurement();
        }
        if ( 'blocks' === $args['display_type'] ) {
            $quantity_measurement = ProductCatalogLoopSettingsSection::getBlocksQuantityMeasurement();
        }
        $args['quantity_measurement_singular'] = $quantity_measurement['singular'];
        $args['quantity_measurement_plural'] = $quantity_measurement['plural'];
        return $args;
    }

    /**
     * Block names whose items WooCommerce renders with the classic loop hooks fired as well (its archive
     * template compatibility layer), on top of the block output.
     */
    const PRODUCT_TEMPLATE_BLOCKS = array('woocommerce/product-template', 'core/post-template');

    /** How many product template blocks are rendering right now (nested templates count up). */
    protected static int $productBlocksDepth = 0;

    /**
     * True while a product template block renders its items. WooCommerce fires the classic loop hooks
     * there too, and renderInProductBlocks() already puts the pricing, the badge and the button into the
     * blocks, so the classic callbacks stand down to avoid a second copy outside the card's layout.
     */
    public static function isRenderingProductBlocks() : bool {
        return self::$productBlocksDepth > 0;
    }

    public function enterProductBlocks( $pre, $block ) {
        if ( $this->isProductTemplateBlock( (array) $block ) ) {
            self::$productBlocksDepth++;
        }
        return $pre;
    }

    public function leaveProductBlocks( $content, $block ) {
        if ( $this->isProductTemplateBlock( (array) $block ) && self::$productBlocksDepth > 0 ) {
            self::$productBlocksDepth--;
        }
        return $content;
    }

    protected function isProductTemplateBlock( array $block ) : bool {
        $name = (string) ($block['blockName'] ?? '');
        if ( 'woocommerce/product-template' === $name ) {
            return true;
        }
        // a Query Loop of products: the one WooCommerce marks as its own, or one inheriting the archive query
        $attrs = (array) ($block['attrs'] ?? array());
        return 'core/post-template' === $name && (!empty( $attrs['isInherited'] ) || 'woocommerce/product-query/product-template' === ($attrs['__woocommerceNamespace'] ?? ''));
    }

    public function run() {
        add_filter( 'tiered_pricing_table/settings/sections', array($this, 'addSettingSection'), 5 );
        add_filter(
            'pre_render_block',
            array($this, 'enterProductBlocks'),
            10,
            2
        );
        add_filter(
            'render_block',
            array($this, 'leaveProductBlocks'),
            1,
            2
        );
        add_filter( 'tiered_pricing_table/text_template_variables', array($this, 'registerTemplateVariables') );
        // the badge is its own feature: it shows whether or not the pricing does
        if ( ProductCatalogLoopSettingsSection::isBadgeEnabled() ) {
            add_action( 'woocommerce_before_shop_loop_item_title', array($this, 'renderBadge'), 11 );
        }
        // block themes and the Product Collection block render products without the classic loop hooks
        add_filter(
            'render_block',
            array($this, 'renderInProductBlocks'),
            10,
            3
        );
        if ( !ProductCatalogLoopSettingsSection::isEnabled() ) {
            return;
        }
        add_filter(
            'tiered_pricing_table/frontend/variation_render_settings',
            function ( $settings, $productId, $context ) {
                if ( 'shop-loop' !== $context ) {
                    return $settings;
                }
                return $this->getRenderAttributes();
            },
            10,
            3
        );
        add_filter(
            'tiered_pricing_table/frontend/default_price_behaviour_type',
            function (
                $type,
                $product,
                $priceHTML,
                $priceDisplayContext
            ) {
                if ( 'shop-loop' === $priceDisplayContext ) {
                    return ( ProductCatalogLoopSettingsSection::isDynamicPrice() ? 'dynamic' : 'static' );
                }
                return $type;
            },
            10,
            4
        );
        add_action( 'init', function () {
            $position = ProductCatalogLoopSettingsSection::getPosition();
            $closeLink = false;
            if ( 'woocommerce_shop_loop_item_title' === $position['hook'] ) {
                $closeLink = true;
                remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
            }
            add_action( $position['hook'], function () use($closeLink) {
                global $product, $post;
                // inside a product template block the pricing is already in the blocks (renderInProductBlocks)
                if ( self::isRenderingProductBlocks() ) {
                    return;
                }
                if ( !$product instanceof WC_Product ) {
                    $productID = ( isset( $post ) ? $post->ID : null );
                    $product = wc_get_product( $productID );
                }
                if ( $closeLink ) {
                    echo '</a>';
                }
                if ( !$product || !$product->is_purchasable() || !$this->shouldRender( $product ) ) {
                    return;
                }
                $this->render( $product, ProductCatalogLoopSettingsSection::useReducedStyles() );
            }, $position['priority'] );
        } );
    }

    /**
     * Whether this product list and this product are among the ones the settings allow.
     */
    public function shouldRender( WC_Product $product, ?string $context = null ) : bool {
        return $this->matchesContext( $context ) && $this->matchesCategories( $product );
    }

    protected function matchesContext( ?string $context = null ) : bool {
        $context = ( $context ?: $this->getLoopContext() );
        return in_array( $context, ProductCatalogLoopSettingsSection::getContexts(), true );
    }

    protected function matchesCategories( WC_Product $product ) : bool {
        $categories = ProductCatalogLoopSettingsSection::getCategories();
        if ( !$categories ) {
            return true;
        }
        $productId = ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() );
        // a chosen category covers its child categories
        foreach ( $categories as $categoryId ) {
            $children = get_term_children( $categoryId, 'product_cat' );
            if ( is_array( $children ) ) {
                $categories = array_merge( $categories, array_map( 'intval', $children ) );
            }
        }
        return has_term( array_unique( $categories ), 'product_cat', $productId );
    }

    /**
     * The kind of product list the classic loop is rendering, as one of the context keys.
     */
    protected function getLoopContext() : string {
        $name = (string) wc_get_loop_prop( 'name', '' );
        if ( 'related' === $name ) {
            return 'related';
        }
        if ( 'up-sells' === $name ) {
            return 'upsells';
        }
        if ( 'cross-sells' === $name ) {
            return 'cross_sells';
        }
        if ( wc_get_loop_prop( 'is_shortcode', false ) ) {
            return 'shortcode';
        }
        if ( is_search() ) {
            return 'search';
        }
        if ( is_product_category() ) {
            return 'category';
        }
        if ( is_product_tag() || is_product_taxonomy() ) {
            return 'tag';
        }
        if ( is_shop() ) {
            return 'shop';
        }
        return 'shortcode';
    }

    /**
     * Product Collection block: the pricing goes around the product button or the title block, per the
     * position option, and the badge into the product image block.
     */
    public function renderInProductBlocks( $content, $block, $instance ) {
        $name = $block['blockName'] ?? '';
        if ( !in_array( $name, array('woocommerce/product-button', 'core/post-title', 'woocommerce/product-image'), true ) ) {
            return $content;
        }
        // only the blocks of a product list: the single product template uses the same blocks (inside the
        // add-to-cart form, as the image), and there the product page has its own pricing
        if ( !self::isRenderingProductBlocks() ) {
            return $content;
        }
        if ( !$instance instanceof WP_Block || empty( $instance->context['postId'] ) ) {
            return $content;
        }
        $postType = $instance->context['postType'] ?? get_post_type( (int) $instance->context['postId'] );
        if ( 'product' !== $postType || !$this->matchesContext( 'blocks' ) ) {
            return $content;
        }
        $product = wc_get_product( (int) $instance->context['postId'] );
        if ( !$product || !$this->matchesCategories( $product ) ) {
            return $content;
        }
        if ( 'woocommerce/product-image' === $name ) {
            $badge = ( ProductCatalogLoopSettingsSection::isBadgeEnabled() ? $this->getBadgeHtml( $product ) : '' );
            return ( $badge ? preg_replace(
                '/(<img\\b[^>]*>)/i',
                '$1' . $badge,
                $content,
                1
            ) : $content );
        }
        if ( !ProductCatalogLoopSettingsSection::isEnabled() || !$product->is_purchasable() ) {
            return $content;
        }
        $position = ProductCatalogLoopSettingsSection::getPosition();
        $target = ( 'woocommerce_shop_loop_item_title' === $position['hook'] ? 'core/post-title' : 'woocommerce/product-button' );
        if ( $name !== $target ) {
            return $content;
        }
        $before = (int) $position['priority'] <= (( 'core/post-title' === $target ? 5 : 6 ));
        ob_start();
        $this->render( $product, ProductCatalogLoopSettingsSection::useReducedStyles() );
        $pricing = ob_get_clean();
        return ( $before ? $pricing . $content : $content . $pricing );
    }

    /**
     * The bulk savings badge on a classic loop item, right after the thumbnail.
     */
    public function renderBadge() {
        global $product, $post;
        // inside a product template block the badge is already in the product image block
        if ( self::isRenderingProductBlocks() ) {
            return;
        }
        $item = ( $product instanceof WC_Product ? $product : (( isset( $post ) ? wc_get_product( $post->ID ) : null )) );
        if ( !$item || !$this->shouldRender( $item ) ) {
            return;
        }
        echo $this->getBadgeHtml( $item );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper
    }

    /**
     * The badge markup, or an empty string when the product's tiers give no saving.
     */
    public function getBadgeHtml( WC_Product $product ) : string {
        list( $lowest, $regular, $lowestQuantity ) = $this->getLowestTierPrice( $product );
        if ( null === $lowest || $regular <= 0 || $lowest >= $regular ) {
            return '';
        }
        $discount = (int) round( ($regular - (float) $lowest) / $regular * 100 );
        $text = strtr( ProductCatalogLoopSettingsSection::getBadgeTemplate(), array(
            '{tp_max_discount}'    => $discount . '%',
            '{tp_lowest_price}'    => wc_price( (float) $lowest ),
            '{tp_lowest_quantity}' => $lowestQuantity,
            '{tp_max_saving}'      => wc_price( $regular - (float) $lowest ),
        ) );
        $color = ProductCatalogLoopSettingsSection::getBadgeColor();
        if ( !$color ) {
            $color = ( ProductCatalogLoopSettingsSection::isCustomLayoutSettings() ? ProductCatalogLoopSettingsSection::getSelectedQuantityColor() : (string) $this->getContainer()->getSettings()->get( 'selected_quantity_color', '#3858e9' ) );
        }
        return '<span class="tpt-bulk-badge tpt-bulk-badge--' . esc_attr( ProductCatalogLoopSettingsSection::getBadgePosition() ) . '" style="background-color: ' . esc_attr( $color ) . '">' . wp_kses_post( $text ) . '</span>';
    }

    /**
     * The lowest tier price a product (or any of its variations) reaches, the price it is compared with and
     * the quantity that price starts at, all as displayed.
     *
     * @return array{0: float|null, 1: float, 2: int|string}
     */
    protected function getLowestTierPrice( WC_Product $product ) : array {
        $lowest = null;
        $lowestQuantity = '';
        if ( $product->is_type( 'variable' ) ) {
            $regular = (float) $product->get_variation_price( 'max', true );
            foreach ( $product->get_children() as $childId ) {
                $rules = PriceManager::getPricingRule( (int) $childId )->getRules();
                if ( !$rules ) {
                    continue;
                }
                $child = wc_get_product( $childId );
                $price = ( $child ? (float) wc_get_price_to_display( $child, array(
                    'price' => PriceManager::getPricingRule( (int) $childId )->getTierPrice( PHP_INT_MAX, false ),
                ) ) : null );
                if ( null !== $price && (null === $lowest || $price < $lowest) ) {
                    $lowest = $price;
                    $lowestQuantity = (int) max( array_keys( $rules ) );
                }
            }
        } else {
            $regular = (float) wc_get_price_to_display( $product );
            $rules = PriceManager::getPricingRule( $product->get_id() )->getRules();
            if ( $rules ) {
                $lowest = (float) wc_get_price_to_display( $product, array(
                    'price' => PriceManager::getPricingRule( $product->get_id() )->getTierPrice( PHP_INT_MAX, false ),
                ) );
                $lowestQuantity = (int) max( array_keys( $rules ) );
            }
        }
        return array($lowest, $regular, $lowestQuantity);
    }

    public function registerTemplateVariables( $variables ) {
        $variables['tp_max_discount'] = array(
            'name'        => __( 'Biggest discount', 'tier-pricing-table' ),
            'description' => __( '{tp_max_discount} - the biggest percentage discount the product\'s tiers give.', 'tier-pricing-table' ),
            'variableKey' => '{tp_max_discount}',
        );
        $variables['tp_lowest_price'] = array(
            'name'        => __( 'Lowest price', 'tier-pricing-table' ),
            'description' => __( '{tp_lowest_price} - the lowest tier price.', 'tier-pricing-table' ),
            'variableKey' => '{tp_lowest_price}',
        );
        $variables['tp_lowest_quantity'] = array(
            'name'        => __( 'Lowest price quantity', 'tier-pricing-table' ),
            'description' => __( '{tp_lowest_quantity} - the quantity the lowest tier price starts at.', 'tier-pricing-table' ),
            'variableKey' => '{tp_lowest_quantity}',
        );
        $variables['tp_max_saving'] = array(
            'name'        => __( 'Biggest saving', 'tier-pricing-table' ),
            'description' => __( '{tp_max_saving} - the saving per item at the lowest tier price.', 'tier-pricing-table' ),
            'variableKey' => '{tp_max_saving}',
        );
        return $variables;
    }

    public function render( WC_product $product, $useReducedStyles = false ) {
        $classes = 'tiered-pricing-shop-loop';
        if ( $useReducedStyles ) {
            $classes .= ' tiered-pricing-shop-loop--reduced';
        }
        $this->getContainer()->getFileManager()->includeTemplate( 'frontend/shop-loop.php', array(
            'classes'  => $classes,
            'product'  => $product,
            'settings' => $this->getRenderAttributes(),
        ) );
    }

    public function addSettingSection( $sections ) : array {
        // The layout configurator carries the catalog settings while it is on.
        if ( LayoutConfiguratorAddon::isActive() ) {
            return $sections;
        }
        $_sections = array();
        foreach ( $sections as $section ) {
            $_sections[] = $section;
            if ( $section->getSlug() === 'general' ) {
                $_sections[] = new ProductCatalogLoopSettingsSection();
            }
        }
        return $_sections;
    }

}
