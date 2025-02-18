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
	
	$premiumFeatures = array(
		'Percentage-based discounts',
		'Role-based pricing',
		'Upsells in the cart',
		'Additional columns for pricing table',
		'Clickable pricing table',
		'Minimum and maximum order quantity',
		
		'Instant totals on the product page',
		'Hide prices for non-logged users',
		'Show product\'s lowest price in the product list',
	);
	
	$upgradeUrl = tpt_fs_activation_url()

?>

<style>
	.tpt__admin__feedback-discount-banner {
		border: 1px solid #c3c4c7;
		padding: 20px 10px !important;
		border-radius: 5px;
		overflow: hidden;
		display: flex;
	}

	.tpt__admin__feedback-discount-banner__inner {
		display: flex;
		flex-grow: 1;
		gap: 15px;
	}

	.tpt__admin__feedback-discount-banner__close-button {
		font-size: 1.5em;
		margin-left: 10px;
	}

	.tpt__admin__feedback-discount-banner__close-button a {
		text-decoration: none;
	}

	.tpt__admin__feedback-discount-banner__premium-features {
		width: 60%;
		display: flex;
		flex-wrap: wrap;
		gap: 10px;
	}

	.tpt__admin__feedback-discount-banner__premium-feature {
		padding: 5px 10px;
		background: #f5f5f5;
		border-radius: 5px;
		flex-grow: 1;
		align-items: center;
		justify-content: center;
		display: flex;
		gap: 5px;
		border: 1px solid #f1f1f1;
	}

	.tpt__admin__feedback-discount-banner__main-text {
		flex-grow: 1;
		padding: 10px;
	}

	.tpt__admin__feedback-discount-banner__main-text__title {
		font-size: 16px;
		margin-bottom: 20px;
	}

	.tpt__admin__feedback-discount-banner__coupon {
		color: #fff;
		background: #000;
		padding: 2px 6px;
		border-radius: 3px;
	}

	.tpt__admin__feedback-discount-banner__main-text__upgrade-buttons {
		display: flex;
		align-items: center;
		gap: 7px;
	}

	@media screen and (max-width: 768px) {

		.tpt__admin__feedback-discount-banner__inner {
			flex-direction: column;
		}

		.tpt__admin__feedback-discount-banner__premium-features {
			width: 100%;
		}

		.tpt__admin__feedback-discount-banner__main-text {
			width: 100%;
			text-align: center;
		}

		.tpt__admin__feedback-discount-banner__main-text__upgrade-buttons {
			justify-content: center;
		}

	}
</style>

<div class="notice tpt__admin__feedback-discount-banner">
    <div class="tpt__admin__feedback-discount-banner__inner">

        <div class="tpt__admin__feedback-discount-banner__main-text">
            <div class="tpt__admin__feedback-discount-banner__main-text__title">
                <span>🎁</span> <b>Tiered Pricing Table</b>: leave us feedback and <b
                        class="tpt__admin__feedback-discount-banner__coupon">Save 20%</b>
            </div>

            <div style="margin: 10px 0">

                <div style="margin: 10px 0">
                    You've been using the plugin for a while now. We'd love to hear your feedback!
                </div>

                <div style="margin-bottom: 10px">
                    Answer 4 little questions in our <a href="https://forms.gle/yWTt3aWuvZVQhbRZ7" target="_blank">feedback
                        form</a> and get
                    a coupon for <b>20% discount</b> on the premium version!
                </div>

                <div>Have a question? <a href="<?php echo esc_attr( TierPricingTablePlugin::getContactUsURL() ); ?>"
                                         target="_blank"
                    >Feel free to contact us!</a> or <a target="_blank" href="https://tiered-pricing.com">check our
                        website</a></div>
            </div>

            <div class="tpt__admin__feedback-discount-banner__main-text__upgrade-buttons">

                <a href="<?php echo esc_attr( $upgradeUrl ); ?>"
                   class="button button-primary button-large">
                    Upgrade 🚀
                </a>

                <span>
                    •
                </span>

                <a href="<?php echo esc_attr( $notification->getCloseURL() ); ?>">
                    I'm not interested, close the banner
                </a>
            </div>

        </div>
        <div class="tpt__admin__feedback-discount-banner__premium-features">
			
			<?php foreach ( $premiumFeatures as $feature ) : ?>
                <div class="tpt__admin__feedback-discount-banner__premium-feature">
                    ✅ <b><?php echo esc_html( $feature ) ?></b>
                </div>
			<?php endforeach; ?>

        </div>
    </div>

    <div class="tpt__admin__feedback-discount-banner__close-button">
        <a href="<?php echo esc_attr( $notification->getCloseURL() ); ?>">&times;</a>
    </div>
</div>
