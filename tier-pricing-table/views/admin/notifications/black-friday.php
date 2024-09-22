<?php defined( 'WPINC' ) || die;
	
	use TierPricingTable\Admin\Notifications\Notifications\TwoMonthsUsingDiscount;
	use TierPricingTable\Core\ServiceContainer;
	use TierPricingTable\TierPricingTablePlugin;
	
	$fileManager = ServiceContainer::getInstance()->getFileManager();
	
	/**
	 * Available variables
	 *
	 * @var TwoMonthsUsingDiscount $notification
	 */
?>
<div class="notice" style="border: 1px solid #c3c4c7;
	padding: 0;
	background: #6d419c;
	color: #fff;
	border-radius: 5px;
	overflow: hidden;">
	
	<div style="display: flex; gap: 20px">
		
		<div style="max-width: 200px;
	display: flex;
	padding: -3px 30px;
	background: #845EC2;" class="tpt-two-months-using-notification__image">
			<div class="tpt-two-months-using-notification__image-inner">
				<img width="100%" src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/pricing-logo.png' ) ); ?>"
					 alt="">
				<h4 style="
	color: #fff;
	text-align: center;
	margin: 0;
">Tiered Pricing Table for WooCommerce</h4>
			</div>
		</div>
		
		<div style="padding: 15px">
			<h2 style="font-size:2.2em; color: #fff; line-height: 1.2em; margin: 10px 0 20px 0;">
				🔥 Black Friday Deal 🔥
			</h2>
			
			<div style="margin: 10px 0">
				
				<p style="font-size: 1.2em">
					Get a
					<b style="color: #8cff00; font-size: 1.15em; background: #000; padding: 3px 8px; border-radius: 4px">25%
						discount</b>
					for the premium version by using the <code
						style="color: #8cff00; font-size: 1.15em; background: #000; padding: 3px 8px; border-radius: 4px">BF25%OFF</code>
					coupon.
				</p>
				
				<p>
					<a href="https://tiered-pricing.com"
					   style="color: #fff !important;" target="_blank">Check our website</a>
					to get more information about the premium features.
				</p>
				<p>Have a question? <a href="<?php echo esc_attr( TierPricingTablePlugin::getContactUsURL() ); ?>"
									   target="_blank"
									   style="color: #fff !important;">Feel free to contact us!</a></p>
			</div>
			<div>
				<a href="<?php echo esc_attr( tpt_fs_activation_url() ); ?>"
				   class="tpt-button tpt-button--green" style="margin: 0; font-size: 1.2em">Upgrade 🚀</a>
				
				<a href="<?php echo esc_attr( $notification->getCloseURL() ); ?>" style="color: #fff; margin-left: 10px"
				   style=" font-size: 1.2em"><?php esc_html_e( "I'm not interested", 'tier-pricing-table' ); ?></a>
			</div>
			<div style="margin-top: 5px">
				<small>Limited-time offer. Valid only this week.</small>
			</div>

		</div>
		
		<div class="tpt-two-months-using-notification__close" style="margin-left: auto;
	text-align: right;padding: 15px;
	font-size: 1.5em;">
			<a href="<?php echo esc_attr( $notification->getCloseURL() ); ?>"
			   style="color: #fff;">Close</a>
		</div>
	</div>
</div>

<style>
	@media screen and (max-width: 600px) {
		.tpt-two-months-using-notification__image,
		.tpt-two-months-using-notification__close {
			display: none !important;
		}
	}
</style>