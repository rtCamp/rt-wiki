<?php

/**
 * rtWiki
 *
 * Helper functions for RtWiki Page not Found
 * send mail to subscribers of daily change in wiki
 *
 * @package    RtWikiDailyChanges
 * @subpackage Helper
 *
 * @author     Dipesh
 */
class RtWikiDailyChanges
{

	/**
	 * Send mail On post Update having body as diff of content for daily update
	 *
	 * @return type
	 */
	function get_users_subscribeposts_list()
	{
		$subscriberslist = array();
		$blogusers       = get_users( );
		$supported_posts = rtwiki_get_supported_attribute();
		if ( is_array( $supported_posts ) && ! empty( $supported_posts ) ){
			foreach ( $supported_posts as $post_types ) {
				$wp_query = new WP_Query( array( 'post_type' => $post_types, ) );
				if ( $wp_query->have_posts() ){
					while ( $wp_query->have_posts() ) {
						$wp_query->the_post();
						foreach ( $blogusers as $user ) {
							if ( is_post_subscribe_cur_user( $user->ID ) && get_permission( get_the_ID(), $user->ID, 1 ) ){
								$subscriberslist[ $user->ID ][ ] = get_the_ID();

							}
						}
					}
				}
			}
		}
		return $subscriberslist;
	}

	/**
	 * Send mail to subscribers as daily changes
	 */
	function send_daily_change_mail()
	{
		error_log( 'dips' );
		$subscriberslist = $this->get_users_subscribeposts_list();
		error_log( 'dips' );
		error_log( $subscriberslist );
		foreach ( $subscriberslist as $key => $value ) {
			$user_info = get_userdata( $key );
			$finalBody = '';
			foreach ( $value as $postid ) {
				$finalBody .= $this->get_post_content_diff( $postid ) . '<br/>';
			}
			if ( isset( $finalBody ) && $finalBody != '' ){
				add_filter( 'wp_mail_content_type', 'set_html_content_type' );
				$subject    = 'Daily Update : RtWiki';
				$headers[ ] = 'From: rtcamp.com <no-reply@' . sanitize_title_with_dashes( get_bloginfo( 'name' ) ) . '.com>';
				wp_mail( $user_info->user_email, $subject, $finalBody, $headers );
				remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
			}
		}
	}

	/**
	 * get diffrent for daily change
	 *
	 * @param type $postID
	 *
	 * @return string
	 */
	function get_post_content_diff( $postID )
	{
		$revision     = wp_get_post_revisions( $postID );
		$content   = array();
		$title     = array();
		$modifieddate = array();
		$finalBody    = '';

		foreach ( $revision as $revisions ) {
			$content[ ]     = $revisions->post_content;
			$title[ ]       = $revisions->post_title;
			$modifieddate[] = $revisions->post_modified;
		}
		if ( ! empty( $content ) && date( 'Y-m-d', strtotime( $modifieddate[ 0 ] ) ) == date( 'Y-m-d' ) ){
			$url       = 'Page Link:' . get_permalink( $postID ) . '<br>';
			$body      = rtwiki_text_diff( $title[ count( $title ) - 1 ], $title[ 0 ], $content[ count( $title ) - 1 ], $content[ 0 ] );
			$finalBody = $url . '<br>' . $body;
		}

		return $finalBody;
	}
}
