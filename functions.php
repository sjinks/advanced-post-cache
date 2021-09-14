<?php

if ( defined( 'ABSPATH' ) ) {
	$GLOBALS['advanced_post_cache_object'] = new Advanced_Post_Cache();

	function clear_advanced_post_cache(): void {
		global $advanced_post_cache_object;
		$advanced_post_cache_object->flush_cache();
	}

	function do_clear_advanced_post_cache(): void {
		$GLOBALS['advanced_post_cache_object']->do_flush_cache = true;
	}

	function dont_clear_advanced_post_cache(): void {
		$GLOBALS['advanced_post_cache_object']->do_flush_cache = false;
	}

	add_action( 'clean_term_cache', 'clear_advanced_post_cache' );
	add_action( 'clean_post_cache', 'clear_advanced_post_cache' );

	add_action( 'added_post_meta', 'clear_advanced_post_cache' );
	add_action( 'updated_post_meta', 'clear_advanced_post_cache' );
	add_action( 'delete_post_meta', 'clear_advanced_post_cache' );

	// Don't clear Advanced Post Cache for a new comment - temp core hack
	// http://core.trac.wordpress.org/ticket/15565
	add_action( 'wp_updating_comment_count', 'dont_clear_advanced_post_cache' );
	add_action( 'wp_update_comment_count', 'do_clear_advanced_post_cache' );
}
