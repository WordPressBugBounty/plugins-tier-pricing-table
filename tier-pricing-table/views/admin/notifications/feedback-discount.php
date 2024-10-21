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

<style>
	@media screen and (max-width: 900px) {
		.tiered-pricing-feedback-discount-offer__logo {
			display: none;
		}
	}
</style>

<div class="notice" style="border: 1px solid #632b58;
	padding: 0;
	background: #935687;
	color: #fff;
	border-radius: 5px;
	overflow: hidden;
	box-sizing: border-box;">

    <div style="display: flex; padding: 15px; gap: 20px; align-items: center; ">

        <div style="width: 100px; text-align:center;" class="tiered-pricing-feedback-discount-offer__logo">
            <img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/pricing-logo.png' ) ); ?>" alt=""
                 width="100%">
            <b>Tiered Pricing Table for</b>
            <br>
            <small>WooCommerce</small>
        </div>

        <div>

            <h2 style="color: #fff; line-height: 1.2em; margin: 0px 0 20px 0;">
                Collect your  <b style="color: #8cff00; background: #000; padding: 3px 8px; border-radius: 4px">20% discount</b>
            </h2>
            <p>
                You've been using the Tiered Pricing Table for WooCommerce for a while now. We hope you're enjoying it!
            </p>
            <p style="font-size: 1.1em">
                Leave us <a href="https://forms.gle/yWTt3aWuvZVQhbRZ7" target="_blank"
                              style="color: #fff !important; font-weight: bold;">feedback</a> and get a
                coupon for <b>20% discount</b> on the premium version!
            </p>

            <p style="font-size: 1.1em">
                <a href="https://tiered-pricing.com"
                   style="color: #fff !important;" target="_blank">Check our website</a>
                to get more information about the premium features.
            </p>
            <p>Have a question? <a
                        href="<?php echo esc_attr( TierPricingTablePlugin::getContactUsURL() ); ?>"
                        target="_blank"
                        style="color: #fff !important;">Feel free to contact us!</a></p>

        </div>

        <div class="tpt-two-months-using-notification__close" style="margin-left: auto;
	text-align: right;
	align-self: baseline;
	font-size: 1.5em;">
            <a href="<?php echo esc_attr( $notification->getCloseURL() ); ?>"
               style="color: #fff;">Close</a>
        </div>
    </div>
</div>