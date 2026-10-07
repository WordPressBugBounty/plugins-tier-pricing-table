<?php

namespace TierPricingTable\Addons\GlobalTieredPricing;

use TierPricingTable\Addons\GlobalTieredPricing\PricingRule\RuleSettings;
use TierPricingTable\Forms\Form;
use DateTime;
use DateTimeZone;
use Exception;
use WC_Product;
use WP_User;
class GlobalPricingRule {
    /**
     * Rule ID
     *
     * @var int
     */
    public $id;

    /**
     * Is suspended
     *
     * @var bool
     */
    public $isSuspended = false;

    /**
     * Regular pricing type
     *
     * @var string
     */
    public $pricingType;

    /**
     * Regular price
     *
     * @var ?float
     */
    public $regularPrice;

    /**
     * Sale price
     *
     * @var ?float
     */
    public $salePrice;

    /**
     * Percentage Discount
     *
     * @var ?float
     */
    public $discount;

    /**
     * Percentage Discount
     *
     * @var string
     */
    public $discountType = 'sale_price';

    /**
     * Applying type
     *
     * @var string
     */
    public $applyingType;

    /**
     * Tax status
     *
     * @var string
     */
    public $taxStatus = '';

    /**
     * Tax class
     *
     * @var string
     */
    public $taxClass = '';

    /**
     * Tiered Pricing type
     *
     * @var string
     */
    public $tieredPricingType;

    /**
     * Percentage Tiered Pricing Rules
     *
     * @var array
     */
    public $percentageTieredPricingRules = array();

    /**
     * Fixed Tiered Pricing Rules
     *
     * @var array
     */
    public $fixedTieredPricingRules = array();

    /**
     * Included categories
     *
     * @var array
     */
    public $includedProductCategories = array();

    /**
     * Excluded categories
     *
     * @var array
     */
    public $excludedProductCategories = array();

    /**
     * Whether the included and excluded categories cover their subcategories too.
     *
     * @var bool
     */
    public $includeSubcategories = false;

    /**
     * Included tags
     *
     * @var array
     */
    public $includedProductTags = array();

    /**
     * Excluded tags
     *
     * @var array
     */
    public $excludedProductTags = array();

    /**
     * Included brands
     *
     * @var array
     */
    public $includedProductBrands = array();

    /**
     * Excluded brands
     *
     * @var array
     */
    public $excludedProductBrands = array();

    /**
     * Included products
     *
     * @var array
     */
    public $includedProducts = array();

    /**
     * Excluded products
     *
     * @var array
     */
    public $excludedProducts = array();

    /**
     * Included product roles
     *
     * @var array
     */
    public $includedUsersRole = array();

    /**
     * Excluded product roles
     *
     * @var array
     */
    public $excludedUsersRole = array();

    /**
     * Included users
     *
     * @var array
     */
    public $includedUsers = array();

    /**
     * Excluded users
     *
     * @var array
     */
    public $excludedUsers = array();

    /**
     * Product minimum purchase quantity
     *
     * @var int|null
     */
    public $minimum;

    /**
     * Mix and match minimum quantity
     *
     * @var bool|null
     */
    public $mixAndMatchMinQuantity = null;

    public $priorityOptions;

    const DEFAULT_PRIORITY = 10;

    /**
     * The pseudo role of a visitor who is not logged in, selectable in the role lists. The same key the
     * wholesale registration add-on uses (NonLoggedInUsers\Roles::GUEST).
     */
    const GUEST_ROLE = 'guest';

    /**
     * The roles the matcher reads for a user: a visitor who is not logged in has the guest pseudo role.
     */
    public static function rolesOf( WP_User $user ) : array {
        return ( $user->ID ? (array) $user->roles : array(self::GUEST_ROLE) );
    }

    /**
     * Whether a role key may be stored in the rule's role lists: an existing WordPress role or the guest pseudo role.
     */
    public static function isSelectableRole( $role ) : bool {
        return self::GUEST_ROLE === $role || function_exists( 'wp_roles' ) && array_key_exists( $role, wp_roles()->roles );
    }

    /**
     * Order among global rules: the lowest number is applied first; equal numbers, the newest rule first.
     *
     * @var int
     */
    public $priority = self::DEFAULT_PRIORITY;

    /**
     * First day the rule applies (Y-m-d in the site's timezone, inclusive), null = no start.
     *
     * @var ?string
     */
    public $startDate = null;

    /**
     * Last day the rule applies (Y-m-d in the site's timezone, inclusive), null = no end.
     *
     * @var ?string
     */
    public $endDate = null;

    /**
     * post_date_gmt of the rule: orders rules with the same priority (newest first).
     *
     * @var string
     */
    public $createdAt = '';

    /**
     * Array with custom data from 3rd-party addons
     *
     * @var array
     */
    public $data = array();

    public function getId() : int {
        return $this->id;
    }

    public function setId( int $id ) {
        $this->id = $id;
    }

    public function getDiscount() : ?float {
        return $this->discount;
    }

    public function setDiscount( ?float $discount ) {
        $this->discount = $discount;
    }

    public function getDiscountType() : string {
        return $this->discountType;
    }

    public function setDiscountType( string $discountType ) {
        $this->discountType = ( in_array( $discountType, array('sale_price', 'regular_price') ) ? $discountType : 'sale_price' );
    }

    public function getTieredPricingType() : string {
        return 'fixed';
    }

    public function setTieredPricingType( string $tieredPricingType ) {
        $this->tieredPricingType = ( in_array( $tieredPricingType, array('percentage', 'fixed') ) ? $tieredPricingType : 'fixed' );
    }

    public function getPercentageTieredPricingRules() : array {
        return $this->percentageTieredPricingRules;
    }

    public function setPercentageTieredPricingRules( array $percentageTieredPricingRules ) {
        $this->percentageTieredPricingRules = $percentageTieredPricingRules;
    }

    public function getFixedTieredPricingRules() : array {
        return $this->fixedTieredPricingRules;
    }

    public function setFixedTieredPricingRules( array $fixedTieredPricingRules ) {
        $this->fixedTieredPricingRules = $fixedTieredPricingRules;
    }

    public function getApplyingType() : string {
        return $this->applyingType;
    }

    public function setApplyingType( string $applyingType ) {
        $this->applyingType = ( in_array( $applyingType, array('individual', 'cross') ) ? $applyingType : 'cross' );
    }

    public function getPricingType() : string {
        return $this->pricingType;
    }

    public function setPricingType( string $priceType ) {
        $this->pricingType = ( in_array( $priceType, array('percentage', 'flat') ) ? $priceType : 'flat' );
    }

    public function getTieredPricingRules() : array {
        return $this->getFixedTieredPricingRules();
    }

    public function getTaxStatus() : string {
        return $this->taxStatus;
    }

    public function setTaxStatus( string $taxStatus ) {
        $this->taxStatus = $taxStatus;
    }

    public function getTaxClass() : string {
        return $this->taxClass;
    }

    public function setTaxClass( string $taxClass ) {
        $this->taxClass = $taxClass;
    }

    public function getRegularPrice() : ?float {
        return $this->regularPrice;
    }

    public function setRegularPrice( ?float $regularPrice ) {
        $this->regularPrice = ( !Form::isEmpty( $regularPrice ) ? floatval( $regularPrice ) : null );
    }

    public function getSalePrice() : ?float {
        return $this->salePrice;
    }

    public function setSalePrice( ?float $salePrice ) {
        $this->salePrice = $salePrice;
    }

    /**
     * Create instance from array
     *
     * @param  array  $data
     *
     * @return self
     */
    public static function fromArray( array $data ) : self {
        $applyingType = $data['applying_type'] ?? 'individual';
        $applyingType = ( in_array( $applyingType, array('individual', 'cross') ) ? $applyingType : 'cross' );
        $tieredPricingType = $data['tiered_pricing_type'] ?? 'fixed';
        $tieredPricingType = ( in_array( $tieredPricingType, array('flat', 'percentage') ) ? $tieredPricingType : 'fixed' );
        $percentageRules = ( isset( $data['percentage_rules'] ) ? (array) $data['percentage_rules'] : array() );
        $fixedRules = ( isset( $data['fixed_rules'] ) ? (array) $data['fixed_rules'] : array() );
        $pricingType = $data['pricing_type'] ?? 'flat';
        $pricingType = ( in_array( $pricingType, array('flat', 'percentage') ) ? $pricingType : 'flat' );
        $regularPrice = $data['regular_price'] ?? null;
        $salePrice = $data['sale_price'] ?? null;
        $discount = $data['discount'] ?? null;
        $discountType = $data['discount_type'] ?? 'sale_price';
        $minimum = $data['minimum'] ?? null;
        $mixAndMatchValue = $data['mix_and_match_minimum'] ?? '';
        $mixAndMatch = ( $mixAndMatchValue === '' ? null : $mixAndMatchValue === 'yes' );
        $taxStatus = $data['tax_status'] ?? '';
        $taxClass = $data['tax_class'] ?? '';
        $self = new self();
        $self->setTaxStatus( $taxStatus );
        $self->setTaxClass( $taxClass );
        $self->setPricingType( (string) $pricingType );
        $self->setRegularPrice( ( Form::isEmpty( $regularPrice ) ? null : (float) $regularPrice ) );
        $self->setSalePrice( ( Form::isEmpty( $salePrice ) ? null : (float) $salePrice ) );
        $self->setDiscount( ( Form::isEmpty( $discount ) ? null : (float) $discount ) );
        $self->setDiscountType( (string) $discountType );
        $self->setApplyingType( $applyingType );
        $self->setTieredPricingType( $tieredPricingType );
        $self->setPercentageTieredPricingRules( $percentageRules );
        $self->setFixedTieredPricingRules( $fixedRules );
        $self->setMinimum( ( Form::isEmpty( $minimum ) ? null : (int) $minimum ) );
        $self->setMixAndMatchMinQuantity( $mixAndMatch );
        $self->setPriority( $data['priority'] ?? self::DEFAULT_PRIORITY );
        $self->setStartDate( $data['start_date'] ?? null );
        $self->setEndDate( $data['end_date'] ?? null );
        return $self;
    }

    /**
     * Validate
     *
     * @throws Exception
     */
    public function validatePricing() {
        // only the fields of the chosen pricing type reach the storefront: a discount left over from
        // "percentage" changes nothing in "flat" mode, and the other way round
        if ( 'percentage' === $this->getPricingType() ) {
            $valid = (float) $this->getDiscount() > 0;
        } else {
            $valid = !Form::isEmpty( $this->getRegularPrice() ) || !Form::isEmpty( $this->getSalePrice() );
        }
        $valid = $valid || !empty( $this->getTieredPricingRules() );
        $valid = $valid || !Form::isEmpty( $this->getMinimum() );
        // a mix & match choice alone changes how product-level minimums count across variations
        $valid = $valid || !is_null( $this->getMixAndMatchMinQuantity() );
        $valid = $valid || $this->getSettings()->getPriorityType() === 'flexible';
        $valid = apply_filters( 'tiered_pricing_table/global_pricing/validation', $valid, $this );
        if ( !$valid ) {
            throw new Exception(esc_html__( 'The pricing rule does not affect either prices or product quantity. The rule will be skipped.', 'tier-pricing-table' ));
        }
    }

    public function isValidPricing() : bool {
        try {
            $this->validatePricing();
        } catch ( Exception $e ) {
            return false;
        }
        return true;
    }

    public function getMinimum() : ?int {
        return $this->minimum;
    }

    public function setMinimum( ?int $minimum ) {
        $this->minimum = ( intval( $minimum ) > 1 ? $minimum : null );
    }

    public function getMixAndMatchMinQuantity() : ?bool {
        return $this->mixAndMatchMinQuantity;
    }

    public function setMixAndMatchMinQuantity( ?bool $mixAndMatch ) {
        $this->mixAndMatchMinQuantity = $mixAndMatch;
    }

    public function getIncludedProductCategories() : array {
        return $this->includedProductCategories;
    }

    public function getExcludedProductCategories() : array {
        return $this->excludedProductCategories;
    }

    public function setIncludedProductCategories( array $includedProductCategories ) {
        $this->includedProductCategories = $includedProductCategories;
    }

    public function setExcludedProductCategories( array $excludedProductCategories ) {
        $this->excludedProductCategories = $excludedProductCategories;
    }

    public function isIncludeSubcategories() : bool {
        return $this->includeSubcategories;
    }

    public function setIncludeSubcategories( bool $includeSubcategories ) {
        $this->includeSubcategories = $includeSubcategories;
    }

    /**
     * The included categories as the matcher reads them: with every subcategory when the switch is on.
     */
    public function getEffectiveIncludedCategories() : array {
        return ( $this->includeSubcategories ? self::withSubcategories( $this->includedProductCategories ) : $this->includedProductCategories );
    }

    /**
     * The excluded categories as the matcher reads them: with every subcategory when the switch is on.
     */
    public function getEffectiveExcludedCategories() : array {
        return ( $this->includeSubcategories ? self::withSubcategories( $this->excludedProductCategories ) : $this->excludedProductCategories );
    }

    /**
     * The ids plus all their descendant product categories.
     */
    public static function withSubcategories( array $ids ) : array {
        $all = array();
        foreach ( array_map( 'intval', $ids ) as $id ) {
            $all[] = $id;
            $children = get_term_children( $id, 'product_cat' );
            if ( is_array( $children ) ) {
                $all = array_merge( $all, array_map( 'intval', $children ) );
            }
        }
        return array_values( array_unique( $all ) );
    }

    public function getIncludedProductTags() : array {
        return $this->includedProductTags;
    }

    public function getExcludedProductTags() : array {
        return $this->excludedProductTags;
    }

    public function setIncludedProductTags( array $includedProductTags ) {
        $this->includedProductTags = $includedProductTags;
    }

    public function setExcludedProductTags( array $excludedProductTags ) {
        $this->excludedProductTags = $excludedProductTags;
    }

    public function getIncludedProductBrands() : array {
        return $this->includedProductBrands;
    }

    public function getExcludedProductBrands() : array {
        return $this->excludedProductBrands;
    }

    public function setIncludedProductBrands( array $includedProductBrands ) {
        $this->includedProductBrands = $includedProductBrands;
    }

    public function setExcludedProductBrands( array $excludedProductBrands ) {
        $this->excludedProductBrands = $excludedProductBrands;
    }

    public function getIncludedProducts() : array {
        return $this->includedProducts;
    }

    public function getExcludedProducts() : array {
        return $this->excludedProducts;
    }

    public function setIncludedProducts( array $includedProducts ) {
        $this->includedProducts = $includedProducts;
    }

    public function setExcludedProducts( array $excludedProducts ) {
        $this->excludedProducts = $excludedProducts;
    }

    public function getIncludedUserRoles() : array {
        return $this->includedUsersRole;
    }

    public function getExcludedUserRoles() : array {
        return $this->excludedUsersRole;
    }

    public function setIncludedUsersRole( array $includedUsersRole ) {
        $this->includedUsersRole = $includedUsersRole;
    }

    public function setExcludedUsersRole( array $excludedUsersRole ) {
        $this->excludedUsersRole = $excludedUsersRole;
    }

    public function getIncludedUsers() : array {
        return $this->includedUsers;
    }

    public function getExcludedUsers() : array {
        return $this->excludedUsers;
    }

    public function setIncludedUsers( array $includedUsers ) {
        $this->includedUsers = $includedUsers;
    }

    public function setExcludedUsers( array $excludedUsers ) {
        $this->excludedUsers = $excludedUsers;
    }

    public function getPriority() : int {
        return $this->priority;
    }

    /**
     * @param  mixed  $priority  Anything non-numeric (an empty meta row, for instance) falls back to the default.
     */
    public function setPriority( $priority ) {
        $this->priority = ( is_numeric( $priority ) ? max( 0, (int) $priority ) : self::DEFAULT_PRIORITY );
    }

    public function getStartDate() : ?string {
        return $this->startDate;
    }

    public function setStartDate( $date ) {
        $this->startDate = self::normalizeDate( $date );
    }

    public function getEndDate() : ?string {
        return $this->endDate;
    }

    public function setEndDate( $date ) {
        $this->endDate = self::normalizeDate( $date );
    }

    public function hasSchedule() : bool {
        return null !== $this->startDate || null !== $this->endDate;
    }

    public function getCreatedAt() : string {
        return $this->createdAt;
    }

    public function setCreatedAt( string $createdAt ) {
        $this->createdAt = $createdAt;
    }

    /**
     * A "Y-m-d" string or null; anything else (a partial date, "0000-00-00", a timestamp) is dropped.
     */
    protected static function normalizeDate( $date ) : ?string {
        $date = ( is_string( $date ) ? trim( $date ) : '' );
        if ( '' === $date ) {
            return null;
        }
        $parsed = DateTime::createFromFormat( '!Y-m-d', $date );
        return ( $parsed && $parsed->format( 'Y-m-d' ) === $date ? $date : null );
    }

    /**
     * 00:00:00 of the start date in the site's timezone, as a timestamp; null without a start date.
     */
    public function getStartTimestamp() : ?int {
        return ( null === $this->startDate ? null : self::dayBoundary( $this->startDate, false ) );
    }

    /**
     * 23:59:59 of the end date in the site's timezone, as a timestamp; null without an end date.
     */
    public function getEndTimestamp() : ?int {
        return ( null === $this->endDate ? null : self::dayBoundary( $this->endDate, true ) );
    }

    protected static function dayBoundary( string $date, bool $endOfDay ) : int {
        $timezone = ( function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone('UTC') );
        $moment = new DateTime($date . (( $endOfDay ? ' 23:59:59' : ' 00:00:00' )), $timezone);
        return $moment->getTimestamp();
    }

    /**
     * "active", "scheduled" (starts later) or "expired" (ended) at the given moment (default: now).
     */
    public function getScheduleStatus( ?int $now = null ) : string {
        $now = ( null === $now ? time() : $now );
        $start = $this->getStartTimestamp();
        $end = $this->getEndTimestamp();
        if ( null !== $start && $now < $start ) {
            return 'scheduled';
        }
        if ( null !== $end && $now > $end ) {
            return 'expired';
        }
        return 'active';
    }

    public function isWithinSchedule( ?int $now = null ) : bool {
        return 'active' === $this->getScheduleStatus( $now );
    }

    const EFFECTIVE_STATUSES = array(
        'active',
        'scheduled',
        'expired',
        'suspended',
        'skipped'
    );

    /**
     * What the storefront makes of a published rule, in order of precedence: "suspended", "expired",
     * "skipped" (changes neither prices nor quantity limits), "scheduled" (starts later) or "active".
     */
    public function getEffectiveStatus( ?int $now = null ) : string {
        if ( $this->isSuspended() ) {
            return 'suspended';
        }
        $schedule = $this->getScheduleStatus( $now );
        if ( 'expired' === $schedule ) {
            return 'expired';
        }
        // shown before "scheduled" so an empty campaign rule gets fixed before it starts
        if ( !$this->isValidPricing() ) {
            return 'skipped';
        }
        return $schedule;
    }

    /**
     * Whether a higher-priority rule can take products and customers away from this one: only a rule
     * that is (or will be) in force. A suspended, expired or skipped rule is not applied anyway.
     */
    public function canBeOverridden( ?int $now = null ) : bool {
        return in_array( $this->getEffectiveStatus( $now ), array('active', 'scheduled'), true );
    }

    /**
     * usort() callback: the order the rules are matched in. Lowest priority number first; among equal
     * numbers the newest rule first (today's behaviour for rules that never had a priority), then by ID.
     */
    public static function compareOrder( GlobalPricingRule $a, GlobalPricingRule $b ) : int {
        if ( $a->getPriority() !== $b->getPriority() ) {
            return $a->getPriority() <=> $b->getPriority();
        }
        if ( $a->getCreatedAt() !== $b->getCreatedAt() ) {
            return strcmp( $b->getCreatedAt(), $a->getCreatedAt() );
        }
        return (int) $b->id <=> (int) $a->id;
    }

    /**
     * Whether this rule has a higher priority than another one (and so is applied where both match).
     */
    public function outranks( GlobalPricingRule $other ) : bool {
        return self::compareOrder( $this, $other ) < 0;
    }

    /**
     * The rule's title, or "Rule #ID" for a rule saved without one.
     */
    public static function titleOf( int $id ) : string {
        $title = get_the_title( $id );
        /* translators: %d: rule ID */
        return ( '' !== trim( (string) $title ) ? $title : sprintf( __( 'Rule #%d', 'tier-pricing-table' ), $id ) );
    }

    public function getTitle() : string {
        return self::titleOf( (int) $this->id );
    }

    public function getSettings() : RuleSettings {
        if ( !$this->priorityOptions ) {
            $this->priorityOptions = new RuleSettings($this);
        }
        return $this->priorityOptions;
    }

    public function asArray() : array {
        return array(
            'pricing_type'          => $this->getPricingType(),
            'regular_price'         => $this->getRegularPrice(),
            'sale_price'            => $this->getSalePrice(),
            'discount'              => $this->getDiscount(),
            'discount_type'         => $this->getDiscountType(),
            'applying_type'         => $this->getApplyingType(),
            'tiered_pricing_type'   => $this->getTieredPricingType(),
            'percentage_rules'      => $this->getPercentageTieredPricingRules(),
            'fixed_rules'           => $this->getFixedTieredPricingRules(),
            'minimum'               => $this->getMinimum(),
            'mix_and_match_minimum' => $this->getMixAndMatchMinQuantity(),
            'tax_status'            => $this->getTaxStatus(),
            'tax_class'             => $this->getTaxClass(),
            'priority'              => $this->getPriority(),
            'start_date'            => $this->getStartDate(),
            'end_date'              => $this->getEndDate(),
            'included_categories'   => $this->getIncludedProductCategories(),
            'include_subcategories' => $this->isIncludeSubcategories(),
            'included_tags'         => $this->getIncludedProductTags(),
            'included_brands'       => $this->getIncludedProductBrands(),
            'included_products'     => $this->getIncludedProducts(),
            'included_users'        => $this->getIncludedUsers(),
            'included_users_role'   => $this->getIncludedUserRoles(),
            'excluded_categories'   => $this->getExcludedProductCategories(),
            'excluded_tags'         => $this->getExcludedProductTags(),
            'excluded_brands'       => $this->getExcludedProductBrands(),
            'excluded_products'     => $this->getExcludedProducts(),
            'excluded_users'        => $this->getExcludedUsers(),
            'excluded_users_role'   => $this->getExcludedUserRoles(),
            'rule_id'               => $this->getId(),
            'is_suspended'          => $this->isSuspended(),
        );
    }

    public function save() {
        $dataToUpdate = array(
            '_tpt_pricing_type'          => $this->getPricingType(),
            '_tpt_regular_price'         => $this->getRegularPrice(),
            '_tpt_sale_price'            => $this->getSalePrice(),
            '_tpt_discount'              => $this->getDiscount(),
            '_tpt_discount_type'         => $this->getDiscountType(),
            '_tpt_applying_type'         => $this->getApplyingType(),
            '_tpt_tiered_pricing_type'   => $this->getTieredPricingType(),
            '_tpt_percentage_rules'      => $this->getPercentageTieredPricingRules(),
            '_tpt_fixed_rules'           => $this->getFixedTieredPricingRules(),
            '_tpt_minimum'               => $this->getMinimum(),
            '_tpt_mix_and_match_minimum' => ( is_null( $this->getMixAndMatchMinQuantity() ) ? '' : wc_bool_to_string( $this->getMixAndMatchMinQuantity() ) ),
            '_tpt_tax_status'            => $this->getTaxStatus(),
            '_tpt_tax_class'             => $this->getTaxClass(),
            '_tpt_priority'              => $this->getPriority(),
            '_tpt_start_date'            => (string) $this->getStartDate(),
            '_tpt_end_date'              => (string) $this->getEndDate(),
            '_tpt_included_categories'   => $this->getIncludedProductCategories(),
            '_tpt_include_subcategories' => wc_bool_to_string( $this->isIncludeSubcategories() ),
            '_tpt_included_tags'         => $this->getIncludedProductTags(),
            '_tpt_included_brands'       => $this->getIncludedProductBrands(),
            '_tpt_included_products'     => $this->getIncludedProducts(),
            '_tpt_included_users'        => $this->getIncludedUsers(),
            '_tpt_included_user_roles'   => $this->getIncludedUserRoles(),
            '_tpt_excluded_categories'   => $this->getExcludedProductCategories(),
            '_tpt_excluded_tags'         => $this->getExcludedProductTags(),
            '_tpt_excluded_brands'       => $this->getExcludedProductBrands(),
            '_tpt_excluded_products'     => $this->getExcludedProducts(),
            '_tpt_excluded_users'        => $this->getExcludedUsers(),
            '_tpt_excluded_user_roles'   => $this->getExcludedUserRoles(),
            '_tpt_is_suspended'          => wc_bool_to_string( $this->isSuspended() ),
        );
        foreach ( $dataToUpdate as $key => $value ) {
            update_post_meta( $this->getId(), $key, $value );
        }
    }

    public static function build( $ruleId ) : self {
        // Simple data to read
        $dataToRead = array(
            '_tpt_pricing_type'          => 'pricing_type',
            '_tpt_sale_price'            => 'sale_price',
            '_tpt_regular_price'         => 'regular_price',
            '_tpt_discount'              => 'discount',
            '_tpt_discount_type'         => 'discount_type',
            '_tpt_applying_type'         => 'applying_type',
            '_tpt_tiered_pricing_type'   => 'tiered_pricing_type',
            '_tpt_minimum'               => 'minimum',
            '_tpt_mix_and_match_minimum' => 'mix_and_match_minimum',
            '_tpt_tax_status'            => 'tax_status',
            '_tpt_tax_class'             => 'tax_class',
            '_tpt_is_suspended'          => 'is_suspended',
            '_tpt_priority'              => 'priority',
            '_tpt_start_date'            => 'start_date',
            '_tpt_end_date'              => 'end_date',
        );
        $data = array();
        foreach ( $dataToRead as $key => $name ) {
            $data[$name] = get_post_meta( $ruleId, $key, true );
        }
        $priceRule = self::fromArray( $data );
        $priceRule->setCreatedAt( (string) get_post_field( 'post_date_gmt', $ruleId ) );
        $existingRoles = wp_roles()->roles;
        $includedCategoriesIds = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_tpt_included_categories', true ) ) );
        $includedTagsIds = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_tpt_included_tags', true ) ) );
        $includedBrandsIds = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_tpt_included_brands', true ) ) );
        $includedProductsIds = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_tpt_included_products', true ) ) );
        $includedUsersRole = array_filter( (array) get_post_meta( $ruleId, '_tpt_included_user_roles', true ), array(self::class, 'isSelectableRole') );
        $includedUsers = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_tpt_included_users', true ) ) );
        $excludedCategoriesIds = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_tpt_excluded_categories', true ) ) );
        $excludedTagsIds = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_tpt_excluded_tags', true ) ) );
        $excludedBrandsIds = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_tpt_excluded_brands', true ) ) );
        $excludedProductsIds = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_tpt_excluded_products', true ) ) );
        $excludedUsersRole = array_filter( (array) get_post_meta( $ruleId, '_tpt_excluded_user_roles', true ), array(self::class, 'isSelectableRole') );
        $excludedUsers = array_filter( array_map( 'intval', (array) get_post_meta( $ruleId, '_tpt_excluded_users', true ) ) );
        $isSuspended = get_post_meta( $ruleId, '_tpt_is_suspended', true ) === 'yes';
        $priceRule->setPercentageTieredPricingRules( self::readPricingRules( 'percentage', $ruleId ) );
        $priceRule->setFixedTieredPricingRules( self::readPricingRules( 'fixed', $ruleId ) );
        $priceRule->setIncludedProductCategories( $includedCategoriesIds );
        $priceRule->setIncludeSubcategories( 'yes' === get_post_meta( $ruleId, '_tpt_include_subcategories', true ) );
        $priceRule->setIncludedProductTags( $includedTagsIds );
        $priceRule->setIncludedProductBrands( $includedBrandsIds );
        $priceRule->setIncludedUsers( $includedUsers );
        $priceRule->setIncludedUsersRole( $includedUsersRole );
        $priceRule->setIncludedProducts( $includedProductsIds );
        $priceRule->setExcludedProductCategories( $excludedCategoriesIds );
        $priceRule->setExcludedProductTags( $excludedTagsIds );
        $priceRule->setExcludedProductBrands( $excludedBrandsIds );
        $priceRule->setExcludedUsers( $excludedUsers );
        $priceRule->setExcludedUsersRole( $excludedUsersRole );
        $priceRule->setExcludedProducts( $excludedProductsIds );
        $priceRule->setIsSuspended( $isSuspended );
        $priceRule->setId( $ruleId );
        return apply_filters( 'tiered_pricing_table/global_pricing/after_built_rule', $priceRule );
    }

    protected static function readPricingRules( $type, $id ) : array {
        $type = ( in_array( $type, array('percentage', 'fixed') ) ? $type : 'fixed' );
        $rules = get_post_meta( $id, "_tpt_{$type}_rules", true );
        $rules = ( !empty( $rules ) ? $rules : array() );
        $rules = ( is_array( $rules ) ? array_filter( $rules ) : array() );
        ksort( $rules );
        return $rules;
    }

    public function setIsSuspended( bool $isSuspended ) {
        $this->isSuspended = $isSuspended;
    }

    public function suspend() {
        $this->setIsSuspended( true );
    }

    public function reactivate() {
        $this->setIsSuspended( false );
    }

    public function isSuspended() : bool {
        return $this->isSuspended;
    }

    /**
     * Wrapper for the main "match" function to provide the hook for 3rd party devs
     *
     * @param  WP_User  $user
     * @param  WC_Product  $product
     *
     * @return bool
     */
    public function matchRequirements( WP_User $user, WC_Product $product ) : bool {
        $matched = $this->_matchRequirements( $user, $product );
        return apply_filters(
            'tiered_pricing_table/global_pricing/match_requirements',
            $matched,
            $this,
            $user,
            $product
        );
    }

    protected function _matchRequirements( WP_User $user, WC_Product $product ) : bool {
        $parentProduct = ( $product->is_type( array('variation', 'subscription-variation') ) ? wc_get_product( $product->get_parent_id() ) : $product );
        /**
         * 1. Check for product exclusion
         *
         * If the product in exclusion - pricing rule does not match immediately
         */
        $excludedProducts = $this->translateIds( $this->getExcludedProducts(), 'product' );
        if ( !empty( $excludedProducts ) ) {
            if ( in_array( $product->get_id(), $excludedProducts ) || in_array( $parentProduct->get_id(), $excludedProducts ) ) {
                return false;
            }
        }
        $excludedProductCategories = $this->translateIds( $this->getEffectiveExcludedCategories(), 'product_cat' );
        if ( !empty( $excludedProductCategories ) ) {
            if ( !empty( array_intersect( $parentProduct->get_category_ids(), $excludedProductCategories ) ) ) {
                return false;
            }
        }
        $excludedProductTags = $this->translateIds( $this->getExcludedProductTags(), 'product_tag' );
        if ( !empty( $excludedProductTags ) ) {
            if ( !empty( array_intersect( $parentProduct->get_tag_ids(), $excludedProductTags ) ) ) {
                return false;
            }
        }
        $excludedProductBrands = $this->translateIds( $this->getExcludedProductBrands(), 'product_brand' );
        if ( !empty( $excludedProductBrands ) ) {
            $productBrands = wp_get_post_terms( $parentProduct->get_id(), 'product_brand', array(
                'fields' => 'ids',
            ) );
            if ( is_wp_error( $productBrands ) ) {
                $productBrands = [];
            }
            if ( !empty( array_intersect( $productBrands, $excludedProductBrands ) ) ) {
                return false;
            }
        }
        /**
         * 2. Check for user exclusion
         *
         * If users in exclusion - pricing rule does not match immediately
         */
        if ( in_array( $user->ID, $this->getExcludedUsers() ) ) {
            return false;
        }
        $userRoles = self::rolesOf( $user );
        foreach ( $this->getExcludedUserRoles() as $role ) {
            if ( in_array( $role, $userRoles ) ) {
                return false;
            }
        }
        /**
         * 3. Check for rule limitation for specific products
         *
         * If yes - match rule only for selected product/product categories
         */
        $productMatched = false;
        $productLimitations = false;
        $includedProducts = $this->translateIds( $this->getIncludedProducts(), 'product' );
        if ( !empty( $includedProducts ) ) {
            $productLimitations = true;
            if ( in_array( $product->get_id(), $includedProducts ) || in_array( $parentProduct->get_id(), $includedProducts ) ) {
                $productMatched = true;
            }
        }
        $includedProductCategories = $this->translateIds( $this->getEffectiveIncludedCategories(), 'product_cat' );
        if ( !empty( $includedProductCategories ) ) {
            $productLimitations = true;
            if ( !empty( array_intersect( $parentProduct->get_category_ids(), $includedProductCategories ) ) ) {
                $productMatched = true;
            }
        }
        $includedProductTags = $this->translateIds( $this->getIncludedProductTags(), 'product_tag' );
        if ( !empty( $includedProductTags ) ) {
            $productLimitations = true;
            if ( !empty( array_intersect( $parentProduct->get_tag_ids(), $includedProductTags ) ) ) {
                $productMatched = true;
            }
        }
        $includedProductBrands = $this->translateIds( $this->getIncludedProductBrands(), 'product_brand' );
        if ( !empty( $includedProductBrands ) ) {
            $productLimitations = true;
            $productBrands = wp_get_post_terms( $parentProduct->get_id(), 'product_brand', array(
                'fields' => 'ids',
            ) );
            if ( is_wp_error( $productBrands ) ) {
                $productBrands = [];
            }
            if ( !empty( array_intersect( $productBrands, $includedProductBrands ) ) ) {
                $productMatched = true;
            }
        }
        // There is product limitation and the product/category does not match the rule
        if ( $productLimitations && !$productMatched ) {
            return false;
        }
        /**
         * 4. If there are no user limits - match the rule immediately
         */
        if ( empty( $this->getIncludedUserRoles() ) && empty( $this->getIncludedUsers() ) ) {
            return true;
        }
        /**
         * 5. If there are user limits - check for user ID and user role.
         */
        if ( in_array( $user->ID, $this->getIncludedUsers() ) ) {
            return true;
        }
        foreach ( $this->getIncludedUserRoles() as $role ) {
            if ( in_array( $role, $userRoles ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Map a rule's stored object IDs to the language currently being viewed.
     *
     * A rule is created in a single language and stores the product/term IDs of that language. The
     * product being priced (and the term IDs returned by WooCommerce for it) are in the current
     * language, so under WPML/Polylang a raw ID comparison would never match on a translation. This
     * translates the stored IDs into the current language via the shared `wpml_object_id` filter
     * (implemented by both WPML and Polylang) so the comparison works across every translation.
     *
     * When no multilingual plugin is active the filter has no listeners, so the IDs are returned
     * unchanged with no overhead.
     *
     * @param  int[]   $ids   Object IDs as stored on the rule.
     * @param  string  $type  Element type: a post type ('product') or taxonomy ('product_cat',
     *                        'product_tag', 'product_brand').
     *
     * @return int[]
     */
    protected function translateIds( array $ids, string $type ) : array {
        if ( empty( $ids ) || !has_filter( 'wpml_object_id' ) ) {
            return $ids;
        }
        $translated = array();
        foreach ( $ids as $id ) {
            // The `true` argument keeps the original ID when the object has no translation in the
            // current language, preserving the previous behaviour for untranslated objects.
            $translated[] = (int) apply_filters(
                'wpml_object_id',
                $id,
                $type,
                true
            );
        }
        return $translated;
    }

}
