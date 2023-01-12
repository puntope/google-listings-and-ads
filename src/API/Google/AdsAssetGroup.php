<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsAssetGroupQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsListingGroupFilterQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\Util\V11\ResourceNames;
use Google\Ads\GoogleAds\V11\Enums\AssetGroupStatusEnum\AssetGroupStatus;
use Google\Ads\GoogleAds\V11\Enums\ListingGroupFilterTypeEnum\ListingGroupFilterType;
use Google\Ads\GoogleAds\V11\Enums\ListingGroupFilterVerticalEnum\ListingGroupFilterVertical;
use Google\Ads\GoogleAds\V11\Resources\AssetGroup;
use Google\Ads\GoogleAds\V11\Resources\AssetGroupListingGroupFilter;
use Google\Ads\GoogleAds\V11\Services\AssetGroupListingGroupFilterOperation;
use Google\Ads\GoogleAds\V11\Services\AssetGroupOperation;
use Google\Ads\GoogleAds\V11\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V11\Services\MutateOperation;
use Google\Ads\GoogleAds\V11\Services\AssetGroupServiceClient;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Exception;
use DateTime;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;

/**
 * Class AdsAssetGroup
 *
 * Used for the Performance Max Campaigns
 * https://developers.google.com/google-ads/api/docs/performance-max/asset-groups
 *
 * @since 1.12.2
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsAssetGroup implements OptionsAwareInterface {

	use OptionsAwareTrait;
	use ApiExceptionTrait;

	/**
	 * Temporary ID to use within a batch job.
	 * A negative number which is unique for all the created resources.
	 *
	 * @var int
	 */
	protected const TEMPORARY_ID = -3;

	/**
	 *  The page size for the AdsAssetGroupQuery.
	 */
	protected const PAGE_SIZE = 1;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * The AdsAssetGroupAsset class.
	 *
	 * @var AdsAssetGroupAsset
	 */
	protected $asset_group_asset;

	/**
	 * List of asset group resource names.
	 *
	 * @var string[]
	 */
	protected $asset_groups;

	/**
	 * AdsAssetGroup constructor.
	 *
	 * @param GoogleAdsClient    $client
	 * @param AdsAssetGroupAsset $asset_group_asset
	 */
	public function __construct( GoogleAdsClient $client, AdsAssetGroupAsset $asset_group_asset ) {
		$this->client            = $client;
		$this->asset_group_asset = $asset_group_asset;
	}

	/**
	 * Create an asset group.
	 *
	 * @since x.x.x
	 *
	 * @param int $campaign_id
	 *
	 * @return int The asset group ID.
	 * @throws ExceptionWithResponseData ExceptionWithResponseData When an ApiException is caught.
	 */
	public function create_asset_group( $campaign_id ): int {
		try {
			$campaign_resource_name = ResourceNames::forCampaign( $this->options->get_ads_id(), $campaign_id );
			$current_date_time      = ( new DateTime( 'now', wp_timezone() ) )->format( 'Y-m-d H:i:s' );
			$name                   = sprintf(
			/* translators: %s: current date time. */
				__( 'Asset Group %s', 'google-listings-and-ads' ),
				$current_date_time
			);

			$operations     = $this->create_operations( $campaign_resource_name, $name );
			$asset_group_id = $this->mutate( $operations );

			return [
				'id'   => $asset_group_id,
				'name' => $name,
			];

		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_api_exception_errors( $e );
			throw new ExceptionWithResponseData(
			/* translators: %s Error message */
				sprintf( __( 'Error creating asset group: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[
					'errors' => $errors,
					'id'     => $campaign_id,
				]
			);
		}
	}

	/**
	 * Returns a set of operations to create an asset group.
	 *
	 * @param string $campaign_resource_name
	 * @param string $name The asset group name.
	 * @return array
	 */
	public function create_operations( string $campaign_resource_name, string $name ): array {
		// Asset must be created before listing group.
		return [
			$this->asset_group_create_operation( $campaign_resource_name, $name ),
			$this->listing_group_create_operation(),
		];
	}

	/**
	 * Returns a set of operations to delete an asset group.
	 *
	 * @param string $campaign_resource_name
	 * @return array
	 */
	public function delete_operations( string $campaign_resource_name ): array {
		$asset_group_operations   = $this->asset_group_delete_operations( $campaign_resource_name );
		$listing_group_operations = $this->listing_group_delete_operations();

		// All assets must be deleted from the group before deleting the asset group.
		return array_merge(
			$listing_group_operations,
			$asset_group_operations
		);
	}

	/**
	 * Returns an asset group create operation.
	 *
	 * @param string $campaign_resource_name
	 * @param string $campaign_name
	 *
	 * @return MutateOperation
	 */
	protected function asset_group_create_operation( string $campaign_resource_name, string $campaign_name ): MutateOperation {
		$asset_group = new AssetGroup(
			[
				'resource_name' => $this->temporary_resource_name(),
				'name'          => $campaign_name . ' Asset Group',
				'campaign'      => $campaign_resource_name,
				'status'        => AssetGroupStatus::ENABLED,
			]
		);

		$operation = ( new AssetGroupOperation() )->setCreate( $asset_group );
		return ( new MutateOperation() )->setAssetGroupOperation( $operation );
	}

	/**
	 * Returns an asset group listing group filter create operation.
	 *
	 * @return MutateOperation
	 */
	protected function listing_group_create_operation(): MutateOperation {
		$listing_group = new AssetGroupListingGroupFilter(
			[
				'asset_group' => $this->temporary_resource_name(),
				'type'        => ListingGroupFilterType::UNIT_INCLUDED,
				'vertical'    => ListingGroupFilterVertical::SHOPPING,
			]
		);

		$operation = ( new AssetGroupListingGroupFilterOperation() )->setCreate( $listing_group );
		return ( new MutateOperation() )->setAssetGroupListingGroupFilterOperation( $operation );
	}

	/**
	 * Returns an asset group delete operation.
	 *
	 * @param string $campaign_resource_name
	 *
	 * @return MutateOperation[]
	 */
	protected function asset_group_delete_operations( string $campaign_resource_name ): array {
		$operations         = [];
		$this->asset_groups = [];

		$results = ( new AdsAssetGroupQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'asset_group.campaign', $campaign_resource_name )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $results->iterateAllElements() as $row ) {
			$resource_name        = $row->getAssetGroup()->getResourceName();
			$this->asset_groups[] = $resource_name;
			$operation            = ( new AssetGroupOperation() )->setRemove( $resource_name );
			$operations[]         = ( new MutateOperation() )->setAssetGroupOperation( $operation );
		}

		return $operations;
	}

	/**
	 * Returns an asset group listing group filter delete operation.
	 *
	 * @return MutateOperation[]
	 */
	protected function listing_group_delete_operations(): array {
		if ( empty( $this->asset_groups ) ) {
			return [];
		}

		$operations = [];
		$results    = ( new AdsListingGroupFilterQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'asset_group_listing_group_filter.asset_group', $this->asset_groups, 'IN' )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $results->iterateAllElements() as $row ) {
			$resource_name = $row->getAssetGroupListingGroupFilter()->getResourceName();
			$operation     = ( new AssetGroupListingGroupFilterOperation() )->setRemove( $resource_name );
			$operations[]  = ( new MutateOperation() )->setAssetGroupListingGroupFilterOperation( $operation );
		}

		return $operations;
	}

	/**
	 * Return a temporary resource name for the asset group.
	 *
	 * @return string
	 */
	protected function temporary_resource_name() {
		return ResourceNames::forAssetGroup( $this->options->get_ads_id(), self::TEMPORARY_ID );
	}

	/**
	 * Get Asset Groups for a specific campaign.
	 *
	 * @since x.x.x
	 *
	 * @param int  $campaign_id The campaign ID.
	 * @param bool $include_assets Whether to include the assets in the response.
	 *
	 * @return array The asset groups for the campaign.
	 * @throws ExceptionWithResponseData When an ApiException is caught.
	 */
	public function get_asset_groups_by_campaign_id( int $campaign_id, bool $include_assets = true ): array {
		try {
			$asset_groups_converted = [];

			$asset_group_results = ( new AdsAssetGroupQuery( [ 'pageSize' => self::PAGE_SIZE ] ) )
				->set_client( $this->client, $this->options->get_ads_id() )
				->add_columns( [ 'asset_group.path1', 'asset_group.path2', 'asset_group.id', 'asset_group.final_urls' ] )
				->where( 'campaign.id', $campaign_id )
				->where( 'asset_group.status', 'REMOVED', '!=' )
				->get_results();

			/** @var GoogleAdsRow $row */
			foreach ( $asset_group_results->getPage()->getIterator() as $row ) {
				$asset_groups_converted[ $row->getAssetGroup()->getId() ] = $this->convert_asset_group( $row );
			}

			if ( $include_assets ) {
				return array_values( $this->get_assets( $asset_groups_converted ) );
			}

			return array_values( $asset_groups_converted );
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_api_exception_errors( $e );
			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Error retrieving asset groups: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[ 'errors' => $errors ]
			);
		}

	}

	/**
	 * Get assets for asset groups.
	 *
	 * @since x.x.x
	 *
	 * @param array $asset_groups The asset groups converted.
	 *
	 * @return array The asset groups with assets.
	 */
	protected function get_assets( array $asset_groups ): array {
		$asset_group_ids = array_keys( $asset_groups );
		$assets          = $this->asset_group_asset->get_assets_by_asset_group_ids( $asset_group_ids );

		foreach ( $asset_group_ids as $asset_group_id ) {
			$asset_groups[ $asset_group_id ]['assets'] = $assets[ $asset_group_id ] ?? [];
		}

		return $asset_groups;
	}

	/**
	 * Edit an asset group.
	 *
	 * @param int   $asset_group_id The asset group ID.
	 * @param array $data The asset group data.
	 * @param array $assets A list of assets data.
	 *
	 * @return int The asset group ID.
	 * @throws ExceptionWithResponseData When an ApiException is caught.
	 */
	public function edit_asset_group( int $asset_group_id, array $data, array $assets = [] ): int {
		try {
			$operations = $this->asset_group_asset->edit_operations( $asset_group_id, $assets );

			// PMax only supports one final URL but it is required to be an array.
			if ( ! empty( $data['final_url'] ) ) {
				$data['final_urls'] = [ $data['final_url'] ];
				unset( $data['final_url'] );
			}

			if ( ! empty( $data ) ) {
				$operations[] = $this->edit_operation( $asset_group_id, $data );
			}

			if ( ! empty( $operations ) ) {
				$this->client->getGoogleAdsServiceClient()->mutate( $this->options->get_ads_id(), $operations );
			}

			return $asset_group_id;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_api_exception_errors( $e );
			throw new ExceptionWithResponseData(
			/* translators: %s Error message */
				sprintf( __( 'Error editing asset group: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[
					'errors' => $errors,
					'id'     => $asset_group_id,
				]
			);
		}
	}

	/**
	 * Returns an asset group edit operation.
	 *
	 * @param integer $asset_group_id The Asset Group ID
	 * @param array   $fields The fields to update.
	 *
	 * @return MutateOperation
	 */
	protected function edit_operation( int $asset_group_id, array $fields ): MutateOperation {
		$fields['resource_name'] = ResourceNames::forAssetGroup( $this->options->get_ads_id(), $asset_group_id );
		$asset_group             = new AssetGroup( $fields );
		$operation               = new AssetGroupOperation();
		$operation->setUpdate( $asset_group );
		$operation->setUpdateMask( FieldMasks::allSetFieldsOf( $asset_group ) );
		return ( new MutateOperation() )->setAssetGroupOperation( $operation );
	}

	/**
	 * Convert Asset Group data to an array.
	 *
	 * @since x.x.x
	 *
	 * @param GoogleAdsRow $row Data row returned from a query request.
	 *
	 * @return array
	 */
	protected function convert_asset_group( GoogleAdsRow $row ): array {
		return [
			'id'               => $row->getAssetGroup()->getId(),
			'final_url'        => iterator_to_array( $row->getAssetGroup()->getFinalUrls() )[0] ?? '',
			'display_url_path' => [ $row->getAssetGroup()->getPath1(), $row->getAssetGroup()->getPath2() ],
		];
	}

	/**
	 * Send a batch of operations to mutate an asset group.
	 *
	 * @since x.x.x
	 *
	 * @param MutateOperation[] $operations
	 *
	 * @return int Asset Group ID from the MutateOperationResponse.
	 * @throws ApiException If any of the operations fail.
	 */
	protected function mutate( array $operations ): int {
		$responses = $this->client->getGoogleAdsServiceClient()->mutate(
			$this->options->get_ads_id(),
			$operations
		);

		foreach ( $responses->getMutateOperationResponses() as $response ) {
			if ( 'asset_group_result' === $response->getResponse() ) {
				$campaign_result = $response->getAssetGroupResult();
				return $this->parse_asset_group_id( $campaign_result->getResourceName() );
			}
		}

		return 0;
	}

	/**
	 * Convert ID from a resource name to an int.
	 *
	 * @since x.x.x
	 *
	 * @param string $name Resource name containing ID number.
	 *
	 * @return int
	 * @throws Exception When unable to parse resource ID.
	 */
	protected function parse_asset_group_id( string $name ): int {
		try {
			$parts = AssetGroupServiceClient::parseName( $name );
			return absint( $parts['asset_group_id'] );
		} catch ( ValidationException $e ) {
			throw new Exception( __( 'Invalid asset group ID', 'google-listings-and-ads' ) );
		}
	}

}
