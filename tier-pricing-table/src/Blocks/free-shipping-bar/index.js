/**
 * The Free Shipping Bar block: rendered by PHP, previewed in the editor with the editor's own (empty) cart.
 */
( function ( blocks, element, blockEditor, ServerSideRender, i18n ) {
	var el = element.createElement;

	blocks.registerBlockType( 'tiered-pricing-table/free-shipping-bar', {
		edit: function ( props ) {
			var blockProps = blockEditor.useBlockProps();

			return el(
				'div',
				blockProps,
				el( ServerSideRender, {
					block: 'tiered-pricing-table/free-shipping-bar',
					attributes: props.attributes,
					EmptyResponsePlaceholder: function () {
						return el(
							'p',
							{ style: { margin: 0, padding: '12px', border: '1px dashed #949494', color: '#50575e', fontSize: '13px' } },
							i18n.__( 'Free shipping bar: shows here when a cart rule with the free shipping bar covers the visitor. Switch the bar on under WooCommerce → Pricing rules → the rule → Free shipping.', 'tier-pricing-table' )
						);
					}
				} )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.serverSideRender, window.wp.i18n );
