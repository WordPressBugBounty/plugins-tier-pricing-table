<?php namespace TierPricingTable\Addons\TierLabels;

class TierLabelsManager {
	
	const LABELS_KEY = 'tiered_pricing_table/features/tier-labels/labels';
	
	protected static $instance = null;
	protected $labels = array();
	
	public static function getInstance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	private function __construct() {}
	
	public function saveLabels( array $labels ) {
		$labelsAsArray = array_map( function ( $label ) {
			return $label->toArray();
		}, $labels );
		
		update_option( self::LABELS_KEY, $labelsAsArray );
	}
	
	public function getLabels( $force = false ): array {
		
		if ( empty( $this->labels ) || $force ) {
			$customLabels = get_option( self::LABELS_KEY, array() );
			$instances    = array();
			
			foreach ( $customLabels as $label ) {
				$instances[] = TierLabel::fromArray( $label );
			}
			
			$this->labels = $instances;
		}
		
		return $this->labels;
	}
	
	/**
	 * How many products, variations and rules assign each label (one count per entity, whatever the tier).
	 *
	 * @return array<string, int> label id => count
	 */
	public function getUsage(): array {
		global $wpdb;

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
			'%' . $wpdb->esc_like( 'tiered_price_tier_labels' )
		) );

		$usage = array();

		foreach ( (array) $rows as $row ) {
			$value = maybe_unserialize( $row->meta_value );

			if ( ! is_array( $value ) ) {
				continue;
			}

			$ids = array();

			foreach ( $value as $byType ) {
				foreach ( (array) $byType as $labelId ) {
					if ( '' !== (string) $labelId ) {
						$ids[ (string) $labelId ] = true;
					}
				}
			}

			foreach ( array_keys( $ids ) as $labelId ) {
				$usage[ $labelId ] = ( $usage[ $labelId ] ?? 0 ) + 1;
			}
		}

		return $usage;
	}

	public function getLabel( $id ): ?TierLabel {
		$labels = $this->getLabels();
		
		foreach ( $labels as $label ) {
			if ( $label->getId() === $id ) {
				return $label;
			}
		}
		
		return null;
	}
}