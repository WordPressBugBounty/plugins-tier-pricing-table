/**
 * The Quick Order Form block: rendered by PHP on the store. The editor shows a still preview of the form
 * (the live form needs the customer's cart and prices) with a link to its settings.
 */
( function ( blocks, element, blockEditor, components, i18n ) {
	var el = element.createElement;
	var __ = i18n.__;

	blocks.registerBlockType( 'tiered-pricing-table/quick-order', {
		edit: function () {
			var blockProps = blockEditor.useBlockProps();
			var settingsUrl = ( window.ajaxurl || '/wp-admin/admin-ajax.php' ).replace( 'admin-ajax.php', 'admin.php?page=wc-settings&tab=tiered_pricing_table_settings&section=quick-order' );

			return el(
				'div',
				blockProps,
				el(
					components.Placeholder,
					{
						icon: 'list-view',
						label: __( 'Quick Order Form', 'tier-pricing-table' ),
						instructions: __( 'Customers search by name or SKU, paste a list or upload a CSV, see the tier price of every line and add everything to the cart at once. The form shows on the store; who can use it, the products and the columns are set in the Quick Order settings.', 'tier-pricing-table' )
					},
					el(
						components.ExternalLink,
						{ href: settingsUrl },
						__( 'Quick Order settings', 'tier-pricing-table' )
					)
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
