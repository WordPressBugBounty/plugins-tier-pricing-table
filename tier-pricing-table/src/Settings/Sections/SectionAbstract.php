<?php namespace TierPricingTable\Settings\Sections;

use TierPricingTable\Settings\Settings;

abstract class SectionAbstract {
	
	abstract public function getName();
	
	abstract public function getSlug();
	
	abstract public function getSettings();
	
	public function getSectionCSS(): string {
		return '';
	}
	
	public function isActive(): bool {
		
		if ( isset( $_GET['section'] ) ) {
			return $_GET['section'] === $this->getSlug();
		} else {
			return $this->getSlug() === Settings::DEFAULT_SECTION;
		}
	}
	
	public function getURL(): string {
		return add_query_arg( array( 'section' => $this->getSlug() ) );
	}
	
	public function isIntegration(): bool {
		return false;
	}
	
	/**
	 * "Unread" indicator on this section's navigation link. Return a version string to enable it;
	 * bump the version to show the badge again after it was seen. Store-wide: the first visit
	 * clears it for every user.
	 */
	public function getBadgeVersion(): ?string {
		return null;
	}
	
	public function isBadgeUnseen(): bool {
		$version = $this->getBadgeVersion();
		
		return $version && get_option( 'tpt_section_badge_seen_' . $this->getSlug() ) !== $version;
	}
	
	public function markBadgeSeen() {
		if ( $this->getBadgeVersion() ) {
			update_option( 'tpt_section_badge_seen_' . $this->getSlug(), $this->getBadgeVersion(), false );
		}
	}
}
