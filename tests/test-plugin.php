<?php

class Test_Plugin extends WP_UnitTestCase {
	private $post_ids;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->post_ids = $this->factory->post->create_many( 15 );
		wp_cache_flush();
	}

	public function test_basic_caching(): void {
		/** @var wpdb $wpdb */
		global $wpdb;

		$queries_before_all = $wpdb->num_queries;
		get_post( $this->post_ids[0] );
		$queries_after_first_getpost = $wpdb->num_queries;
		get_post( $this->post_ids[0] );
		$queries_after_second_getpost = $wpdb->num_queries;

		self::assertSame( $queries_after_first_getpost, $queries_after_second_getpost );
		self::assertGreaterThan( $queries_before_all, $queries_after_first_getpost );
	}

	private function run_test( array $query_params ): void {
		/** @var wpdb $wpdb */
		global $wpdb;

		$before  = $wpdb->num_queries;
		$query_1 = new WP_Query( $query_params );
		$after_1 = $wpdb->num_queries;
		$query_2 = new WP_Query( $query_params );
		$after_2 = $wpdb->num_queries;

		self::assertSame( $after_1, $after_2 );
		self::assertGreaterThan( $before, $after_1 );

		self::assertSame( $query_1->found_posts, $query_2->found_posts );
		self::assertSame( $query_1->post_count, $query_2->post_count );
		self::assertEquals( $query_1->posts, $query_2->posts );
	}

	/**
	 * @see https://github.com/Automattic/advanced-post-cache/pull/10/files#diff-c363746730d090d8f19ecb201e8cc5c68020fc5aba32510fa95a66857d8ed726
	 */
	public function test_gh10(): void {
		$params = [
			'status'         => 'publish',
			'posts_per_page' => 15,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		];

		$this->run_test( $params );
	}

	/**
	 * @dataProvider pagination_data_provider
	 */
	public function test_pagination( array $params ): void {
		$this->run_test( $params );
	}

	public function pagination_data_provider(): array {
		return [
			'normal query'                 => [
				[
					'status'         => 'publish',
					'posts_per_page' => 5,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				],
			],
			'no pagination'                => [
				[
					'status'   => 'publish',
					'nopaging' => true, // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.nopaging_nopaging
					'orderby'  => 'ID',
					'order'    => 'ASC',
				],
			],
			'no found rows'                => [
				[
					'status'         => 'publish',
					'no_found_rows'  => true,
					'posts_per_page' => 10,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				],
			],
			'no found rows, no pagination' => [
				[
					'status'        => 'publish',
					'no_found_rows' => true,
					'nopaging'      => true, // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.nopaging_nopaging
					'orderby'       => 'ID',
					'order'         => 'ASC',
				],
			],
		];
	}

	/**
	 * @see https://github.com/Automattic/advanced-post-cache/issues/14
	 */
	public function test_metadata(): void {
		$params = [
			'posts_per_page' => 5,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'     => 'custom_key_123',
					'compare' => 'EXISTS',
				],
			],
		];

		update_post_meta( $this->post_ids[0], 'custom_key_123', 123 );

		$query = new WP_Query( $params );
		self::assertCount( 1, $query->posts );

		update_post_meta( $this->post_ids[1], 'custom_key_123', 456 );
		$query = new WP_Query( $params );
		self::assertCount( 2, $query->posts );

		add_post_meta( $this->post_ids[2], 'custom_key_123', 789 );
		$query = new WP_Query( $params );
		self::assertCount( 3, $query->posts );

		delete_post_meta( $this->post_ids[0], 'custom_key_123' );
		delete_post_meta( $this->post_ids[1], 'custom_key_123' );
		delete_post_meta( $this->post_ids[2], 'custom_key_123' );
		$query = new WP_Query( $params );
		self::assertEmpty( $query->posts );
	}
}
