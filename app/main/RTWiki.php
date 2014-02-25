<?php

/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ){

	exit;
}

/**
 * rtWiki
 *
 * The main rtWiki Class. This is where everything starts.
 *
 * @package    rtWiki
 * @subpackage Main
 *
 * @author     Dipesh
 */
if ( ! class_exists( 'RTWiki' ) ){

	class RTWiki
	{

		/**
		 * Constructs the class
		 * Defines constants and excerpt lengths, initiates admin notices,
		 * loads and initiates the plugin, loads translations.
		 *
		 * @global int $bp_media_counter Media counter
		 */
		public function __construct()
		{
			$this->rtwiki_require_once();
			$this->update_db();

			//Rtwiki enqueue scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'rtwiki_admin_enqueue_styles_and_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'rtwiki_enqueue_styles_and_scripts' ) );

			//Rtwiki widget area register
			add_action( 'widgets_init', array( $this, 'rt_wiki_widget_area' ) );
			add_action( 'widgets_init', 'rt_wiki_register_widgets' );
			add_action( 'wp_dashboard_setup', 'wiki_add_dashboard_widgets' );

			add_action( 'init', array( $this, 'admin_init' ) );

			//Post filtering
			add_action( 'the_posts', 'rtwiki_search_filter' );
			add_filter( 'the_content', 'rtwiki_content_filter' );
			add_filter( 'edit_post_link', 'rtwiki_edit_post_link_filter' );
			add_filter( 'comments_array', 'rtwiki_comment_filter', 10, 2 );
			add_filter( 'comments_open', 'rtwiki_comment_form_filter', 10, 2 );

			/* Function to disable feeds for wiki CPT */
			remove_action( 'do_feed_rdf', 'do_feed_rdf', 10, 1 );
			remove_action( 'do_feed_rss', 'do_feed_rss', 10, 1 );
			remove_action( 'do_feed_rss2', 'do_feed_rss2', 10, 1 );
			remove_action( 'do_feed_atom', 'do_feed_atom', 10, 1 );

			// Now we add our own actions, which point to our own feed function
			add_action( 'do_feed_rdf', 'my_do_feed', 10, 1 );
			add_action( 'do_feed_rss', 'my_do_feed', 10, 1 );
			add_action( 'do_feed_rss2', 'my_do_feed', 10, 1 );
			add_action( 'do_feed_atom', 'my_do_feed', 10, 1 );

			//Template include for rtwiki
			add_filter( 'template_include', 'rc_tc_template_chooser', 1 );

			//update subscribe entry
			add_action( 'wp', 'update_subscribe' );

			//Send Content diff through email on wikipostupdate
			add_action( 'save_post', 'send_mail_postupdate_wiki', 99, 1 );


		}

		function rtwiki_require_once()
		{
			require_once RT_WIKI_PATH_ADMIN . 'user-groups.php';
			require_once RT_WIKI_PATH_HELPER . 'rtwiki-functions.php';
			require_once RT_WIKI_PATH_HELPER . 'wiki-settings.php';
			require_once RT_WIKI_PATH_HELPER . 'wiki-post-filtering.php';
			require_once RT_WIKI_PATH_HELPER . 'wiki-single-custom-template.php';
			require_once RT_WIKI_PATH_HELPER . 'wiki-404-redirect.php';
			require_once RT_WIKI_PATH_HELPER . 'wiki-post-subscribe.php';
			require_once RT_WIKI_PATH_HELPER . 'wiki-singlepost-content.php';
			require_once RT_WIKI_PATH_HELPER . 'RtWikiDailyChanges.php';
			require_once RT_WIKI_PATH_ADMIN . 'wiki-widgets.php';
			require_once RT_WIKI_PATH_HELPER . 'RtCRMEmailDiff.php';
		}

		function update_db()
		{
			$update = new RTDBUpdate( false, RT_WIKI_PATH . 'index.php', RT_WIKI_PATH . 'app/schema/' );
			/* Current Version. */
			if ( ! defined( 'RTMEDIA_VERSION' ) ){
				define( 'RTMEDIA_VERSION', $update->db_version );
			}
			if ( $update->check_upgrade() ){
				$update->do_upgrade();
			}
		}

		/**
		 * Load admin screens
		 *
		 * @global RtWikiAdmin $rtwiki_admin Class for loading admin screen
		 */
		function admin_init()
		{
			global $rtwiki_admin,$rtwiki_cpt,$rtwiki_roles;
			$rtwiki_cpt   = new RtWikiCPT();
			$rtwiki_admin = new RtWikiAdmin();
			$rtwiki_roles = new RtWikiRoles();
		}

		/**
		 * Register rtWiki custom sidebar in the widget area.
		 */
		function rt_wiki_widget_area()
		{
			$arg = array(
				'name' => __( 'rtWiki Widget Area', 'rtCamp' ),
				'id' => 'rt-wiki-sidebar',
				'description' => __( 'An optional sidebar for the rtWiki Widget', 'rtCamp' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s sidebar-widget rtp-subscribe-widget-container">',
				'after_widget' => '</div>',
				'before_title' => '<h3 class="widgettitle">',
				'after_title' => '</h3>', ) ;

			register_sidebar( $arg );
		}

		function rtwiki_admin_enqueue_styles_and_scripts() {
			global $hook_suffix;
			wp_register_script( 'rtwiki-admin-script', plugins_url( '../assets/js/rtwiki-admin-script.js',  __FILE__ ), array( 'jquery' ) );
			wp_enqueue_script( 'rtwiki-admin-script' );

			wp_register_script( 'rtwiki-new-post-script', plugins_url( '../assets/js/rtwiki-new-post-script.js',  __FILE__ ), array( 'jquery' ) );


			if ( is_admin() && $hook_suffix == 'post-new.php' ) {
				wp_enqueue_script( 'rtwiki-new-post-script' );
			}

			wp_register_style( 'rtwiki-admin-styles', plugins_url( '../assets/css/rtwiki-admin-styles.css', __FILE__ ) );

			if (is_admin())
				wp_enqueue_style( 'rtwiki-admin-styles' );
		}

		function rtwiki_enqueue_styles_and_scripts() {
			wp_register_script( 'rtwiki-custom-script', plugins_url( '../assets/js/rtwiki-custom-script.js', __FILE__ ), array( 'jquery' ) );
			wp_enqueue_script( 'rtwiki-custom-script' );
			if ( is_404() ) {
				wp_register_script( 'rtwiki-404-script', plugins_url( '../assets/js/rtwiki-404-script.js', __FILE__ ), array( 'jquery' ) );
				wp_localize_script( 'rtwiki-404-script', 'redirectURL', "<a href='" . redirect_404() . "'>" . __( 'Click here. ', 'rtCamp' ) . '</a>' . __( 'If you want to add this post', 'rtCamp' ) );
				wp_enqueue_script( 'rtwiki-404-script' );
			}

			wp_register_style( 'rtwiki-client-styles', plugins_url( '../assets/css/rtwiki-client-styles.css', __FILE__ ) );
			wp_enqueue_style( 'rtwiki-client-styles' );
		}

	}

}