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
	
	$upgradeUrl = add_query_arg( array(
		'coupon' => 'BF25OFF',
	), tpt_fs_activation_url() );

?>

<style>
	.tpt__admin__black-friday-banner {
		border: 1px solid #c3c4c7;
		padding: 10px;
		border-radius: 5px;
		overflow: hidden;
		display: flex;
	}

	.tpt__admin__black-friday-banner__inner {
		display: flex;
		flex-grow: 1;
		gap: 15px;
	}

	.tpt__admin__black-friday-banner__close-button {
		font-size: 1.5em;
		margin-left: 10px;
	}

	.tpt__admin__black-friday-banner__close-button a {
		text-decoration: none;
	}

	.tpt__admin__black-friday-banner__premium-features {
		width: 60%;
		display: flex;
		flex-wrap: wrap;
		gap: 10px;
	}

	.tpt__admin__black-friday-banner__premium-feature {
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

	.tpt__admin__black-friday-banner__main-text {
		flex-grow: 1;
		padding: 10px;
	}

	.tpt__admin__black-friday-banner__main-text__title {
		font-size: 16px;
		margin-bottom: 20px;
	}

	.tpt__admin__black-friday-banner__coupon {
		color: #fff;
		background: #000;
		padding: 2px 6px;
		border-radius: 3px;
	}

	.tpt__admin__black-friday-banner__main-text__upgrade-buttons {
		display: flex;
		align-items: center;
		gap: 7px;
	}

	@media screen and (max-width: 768px) {

		.tpt__admin__black-friday-banner__inner {
			flex-direction: column;
		}

		.tpt__admin__black-friday-banner__premium-features {
			width: 100%;
		}

		.tpt__admin__black-friday-banner__main-text {
			width: 100%;
			text-align: center;
		}

		.tpt__admin__black-friday-banner__main-text__upgrade-buttons {
			justify-content: center;
		}

	}
</style>

<div class="notice tpt__admin__black-friday-banner">
    <div class="tpt__admin__black-friday-banner__inner">

        <div class="tpt__admin__black-friday-banner__main-text">
            <div class="tpt__admin__black-friday-banner__main-text__title">
                🎉 <b>Tiered Pricing Table</b> Black Friday Sale: <b style="color: #b51a00;">Save 25%!</b>
            </div>

            <div style="margin: 10px 0">

                <p style="font-size: 1.2em">
                    Use <b class="tpt__admin__black-friday-banner__coupon">BF25OFF</b>
                    coupon code and get the <b>25% discount</b>!
                </p>

                <div>Have a question? <a href="<?php echo esc_attr( TierPricingTablePlugin::getContactUsURL() ); ?>"
                                         target="_blank"
                    >Feel free to contact us!</a> or <a target="_blank" href="https://tiered-pricing.com">check our
                        website</a></div>
            </div>

            <div class="tpt__admin__black-friday-banner__main-text__upgrade-buttons">

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

            <div style="margin-top: 5px">
                <small>Limited-time offer. Valid only this week.</small>
            </div>

        </div>
        <div class="tpt__admin__black-friday-banner__premium-features">
			
			<?php foreach ( $premiumFeatures as $feature ) : ?>
                <div class="tpt__admin__black-friday-banner__premium-feature">
                    ✅ <b><?php echo esc_html( $feature ) ?></b>
                </div>
			<?php endforeach; ?>

        </div>
    </div>


    <div class="tpt__admin__black-friday-banner__close-button">
        <a href="<?php echo esc_attr( $notification->getCloseURL() ); ?>">&times;</a>
    </div>
</div>
