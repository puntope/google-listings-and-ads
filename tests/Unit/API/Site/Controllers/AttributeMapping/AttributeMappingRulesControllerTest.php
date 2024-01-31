<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\AttributeMapping;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping\AttributeMappingRulesController;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\AttributeMappingHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;

/**
 * Test suite for AttributeMappingRulesController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\AttributeMapping
 * @group AttributeMapping
 */
class AttributeMappingRulesControllerTest extends RESTControllerUnitTest {


	protected const ROUTE_RULES = '/wc/gla/mc/mapping/rules';
	protected const ROUTE_RULE  = '/wc/gla/mc/mapping/rules/' . self::TEST_RULE_ID;

	protected const TEST_RULE_ID    = 1;
	protected const TEST_ATTRIBUTES = [
		[
			'id'    => 'adult',
			'label' => 'Adult',
			'enum'  => true,
		],
		[
			'id'    => 'brand',
			'label' => 'Brand',
			'enum'  => false,
		],
	];
	protected const TEST_RULE       = [
		'attribute'               => 'adult',
		'source'                  => 'yes',
		'category_condition_type' => 'ALL',
	];

	/**
	 * @var AttributeMappingHelper
	 */
	private AttributeMappingHelper $attribute_mapping_helper;


	/**
	 * @var AttributeMappingRulesQuery
	 */
	private AttributeMappingRulesQuery $attribute_mapping_rules_query;

	public function setUp(): void {
		parent::setUp();

		$this->attribute_mapping_helper = $this->createMock( AttributeMappingHelper::class );
		$this->attribute_mapping_helper->method( 'get_attributes' )->willReturn( self::TEST_ATTRIBUTES );
		$this->attribute_mapping_helper->method( 'get_category_condition_types' )->willReturn( [ 'ALL', 'ONLY', 'EXCEPT' ] );

		$this->attribute_mapping_rules_query = $this->createMock( AttributeMappingRulesQuery::class );
		$this->controller                    = new AttributeMappingRulesController( $this->server, $this->attribute_mapping_helper, $this->attribute_mapping_rules_query );
		$this->controller->register();
	}


	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_RULES, $this->server->get_routes() );
	}

	public function test_get_rules_route() {
		$data = [
			[
				'id'                      => 1,
				'attribute'               => 'adult',
				'source'                  => 'yes',
				'category_condition_type' => 'ALL',
				'categories'              => '',
			],
			[
				'id'                      => 2,
				'attribute'               => 'brand',
				'source'                  => 'taxonomy:product_brand',
				'category_condition_type' => 'ONLY',
				'categories'              => '1,2,3',
			],
		];

		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'get_results' )->willReturn( $data );

		$response = $this->do_request( self::ROUTE_RULES );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $data, $response->get_data() );
	}

	public function test_get_rules_pagination() {
		$this->attribute_mapping_rules_query->expects( $this->exactly( 3 ) )
			->method( 'get_results' )->willReturn( [] );

		$this->attribute_mapping_rules_query->expects( $this->exactly( 3 ) )
			->method( 'get_count' )->willReturn( 2 );

		$this->attribute_mapping_rules_query->expects( $this->exactly( 3 ) )
			->method( 'set_limit' )->withConsecutive( [ 1 ], [ 2 ], [ 10 ] );

		$this->attribute_mapping_rules_query->expects( $this->exactly( 3 ) )
			->method( 'set_offset' )->withConsecutive( [ 1 ], [ 0 ], [ 10 ] );

		$response = $this->do_request(
			self::ROUTE_RULES,
			'GET',
			[
				'page'     => 2,
				'per_page' => 1,
			]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'X-WP-Total'      => 2,
				'X-WP-TotalPages' => 2,
			],
			$response->get_headers()
		);

		$response = $this->do_request(
			self::ROUTE_RULES,
			'GET',
			[
				'page'     => 1,
				'per_page' => 2,
			]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'X-WP-Total'      => 2,
				'X-WP-TotalPages' => 1,
			],
			$response->get_headers()
		);

		$response = $this->do_request(
			self::ROUTE_RULES,
			'GET',
			[
				'page'     => 2,
				'per_page' => 10,
			]
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'X-WP-Total'      => 2,
				'X-WP-TotalPages' => 1,
			],
			$response->get_headers()
		);
	}

	public function test_create_rule_route() {
		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'last_insert_id' )->willReturn( self::TEST_RULE_ID );

		$this->validate_post_route( self::ROUTE_RULES, 'insert' );
	}

	public function test_update_rule_route() {
		$this->validate_post_route( self::ROUTE_RULE, 'update' );
	}

	public function test_create_rule_route_not_creating() {
		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'insert' )->willReturn( 0 );

		$response = $this->do_request( self::ROUTE_RULES, 'post', self::TEST_RULE );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_update_rule_route_not_updating() {
		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'update' )->willReturn( 0 );

		$response = $this->do_request( self::ROUTE_RULE, 'post', self::TEST_RULE );
		$this->assertEquals( 400, $response->get_status() );
	}

	private function validate_post_route( $route, $method ) {
		$rule_with_id = array_merge( [ 'id' => self::TEST_RULE_ID ], self::TEST_RULE, [ 'categories' => null ] );

		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( $method )->willReturn( 1 );

		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'get_rule' )->willReturn( $rule_with_id );

		// insert works
		$response = $this->do_request( $route, 'post', self::TEST_RULE );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $rule_with_id, $response->get_data() );

		// not working without rule.attribute param
		$response = $this->do_request(
			$route,
			'post',
			[
				'source'                  => 'x',
				'category_condition_type' => 'ALL',
			]
		);
		$this->assertEquals( 400, $response->get_status() );

		// not working without rule.source param
		$response = $this->do_request(
			$route,
			'post',
			[
				'attribute'               => 'adult',
				'category_condition_type' => 'ALL',
			]
		);
		$this->assertEquals( 400, $response->get_status() );

		// not working without rule.category_condition_type param
		$response = $this->do_request(
			$route,
			'post',
			[
				'attribute' => 'adult',
				'source'    => 'test',
			]
		);
		$this->assertEquals( 400, $response->get_status() );

		// not working with wrong rule.categories
		$response = $this->do_request(
			$route,
			'post',
			[
				'attribute'               => 'adult',
				'source'                  => 'test',
				'category_condition_type' => 'ONLY',
				'categories'              => 'test,test',
			]
		);
		$this->assertEquals( 400, $response->get_status() );

		// not working with wrong rule.category_condition_type param
		$response = $this->do_request(
			$route,
			'post',
			[
				'attribute'               => 'adult',
				'source'                  => 'test',
				'category_condition_type' => 'BAD',
			]
		);
		$this->assertEquals( 400, $response->get_status() );

		// not working with wrong rule.attribute param
		$response = $this->do_request(
			$route,
			'post',
			[
				'attribute'               => 'wrong',
				'source'                  => 'test',
				'category_condition_type' => 'ALL',
			]
		);
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_delete_rule_route() {
		$data = [ 'id' => self::TEST_RULE_ID ];
		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'delete' )->willReturn( 1 );

		// delete works
		$response = $this->do_request( self::ROUTE_RULE, 'delete' );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $data, $response->get_data() );
	}

	public function test_delete_rule_route_not_deleting() {
		$this->attribute_mapping_rules_query->expects( $this->once() )
			->method( 'delete' )->willReturn( 0 );

		// delete fails
		$response = $this->do_request( self::ROUTE_RULE, 'delete' );
		$this->assertEquals( 400, $response->get_status() );
	}
}
