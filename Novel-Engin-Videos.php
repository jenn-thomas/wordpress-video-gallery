<?php
/**
 * Plugin Name: Novel Engineering Video Gallery
 * Description: Allows an administrator to add videos with search tags, a title, and a description that are then displayed on a page using a short code. On the client side, a user can search through the videos by search terms or tag type.
 * Version: 1.0.0
 * Author: Jenn Thomas
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

require_once( ABSPATH . 'wp-includes/pluggable.php' );

define("NEV_PLUGIN_NAME", "Novel-Engineering-Video-Gallery");
define("NEV_IMAGE_PATH", plugins_url('/images', __FILE__));
define("NEV_PLUGIN_PATH", plugins_url('/', __FILE__));
define("NEV_DEBUG", false); // Turns on useful errors that are dumped into the php error log for debugging

if ( !class_exists("NEVideoGallery") ){
	
	class NEVideoGallery{
	
	    /**
 		* Class NEVideoGallery
 		* Initializes the plugin and includes all the necessary components
		*/
		function __construct(){	

			require_once( dirname( __FILE__ ) . '/core/nev_core.php' );	
			require_once( dirname( __FILE__ ) . '/front-end/nev_shortcode.php' );	
			$this->nev_init();
			
			self::hook_AJAX();
			
			if ( is_admin() ) 
				self::find_nev_classes( dirname(__FILE__) . "/admin-menu/" );
				add_action('admin_menu', array('NEVideoGallery', 'admin_menu'));
					
    	}
    	
    	private static function nev_init(){
    		nev_shortcode::init();
    		self::enqueue_nev_js();
    	}
    	
    	/**
         * Given $dir, recursively iterates over all directories and
         * calls init_nev_classes() on each directory. Used for extending SmartPost with
         * future SmartPost components.
         *
         * @param string $dir The directory path
         */
        static function find_nev_classes($dir){
            if ( is_dir($dir) ) {
                if ( $dh = opendir($dir) ) {
                    while ( ($file = readdir($dh)) !== false ) {
                        if( is_dir($dir . $file) && ($file != "." && $file != "..") ){
                            self::init_nev_classes( $dir . $file );
                        }
                    }
                    closedir($dh);
                }
            }
        }
        
        static function init_nev_classes($folder){
            foreach ( glob( $folder . "/nev_*.php" ) as $filename ){
                $class = basename($filename, ".php");
                if( !class_exists($class) && file_exists( $filename )){
                    require_once( $filename );
                }
                //Initialize the class if possible
                if( class_exists( $class )){
                    if( method_exists( $class, 'init' ) ){
                        call_user_func( array($class, 'init') );
                    }
                }
            }
        }
        
        static function hook_AJAX(){
        	require_once( dirname( __FILE__ ) . '/nev_AJAX.php' );
            nev_AJAX::init();
        }
    	
    	/**
 		* Create admin menu and submenus
		*/
    	function admin_menu(){
      		add_menu_page( 'Video Gallery', 'Video Gallery', 'edit_dashboard', 'video-gallery', array('NEVideoGallery', 'video_gallery'), 'dashicons-images-alt', null ); 
			add_submenu_page( 'video-gallery', 'Add Video', 'Add Video', 'edit_dashboard', 'add-video', array('NEVideoGallery', 'video_add') );
			add_submenu_page( 'video-gallery', 'Settings', 'Settings', 'edit_dashboard', 'video-settings', array('NEVideoGallery', 'video_settings') );

    	}
    	
    	/**
 		* displays video gallery, main page and listed in submenu
		*/
    	function video_gallery(){
    		nev_videogallery::render_settings();
    	}
    	
    	/**
 		* add video page, located in submenu
		*/
    	function video_add(){
    		nev_addvideo::render_settings();
    	}
    	
    	/**
 		* settings page, check to see if ffmpeg is located on server
 		* other options added?
		*/
    	function video_settings(){
    		nev_settings::render_settings();
    	}
    	
    	function novelEngin_install(){
 			
 			$nev_ffmpeg_path = get_site_option( 'nev_ffmpeg_path' );

            if( empty($nev_ffmpeg_path) || is_wp_error( $nev_ffmpeg_path ) ){

                // See if ffmpeg exists...
                exec( 'command -v ffmpeg', $ffmpeg_output, $ffmpeg_status );

                // If command exited successfully, then update the nev_ffmpeg_path site option with the path, otherwise update it to false.
                if( $ffmpeg_status === '0' ){
                    update_site_option( 'nev_ffmpeg_path', basename($ffmpeg_output) );
                }else{
                    update_site_option( 'nev_ffmpeg_path', new WP_Error( 'broke', __( 'ffmpeg path not found' ) ) );
                }

                if( DEBUG_NEV_VIDEO ){
                    $nev_ffmpeg_path = get_site_option( 'nev_ffmpeg_path' );
                    if( is_wp_error($nev_ffmpeg_path) ){
                        error_log( 'ffmpeg path:' . print_r($nev_ffmpeg_path, true) );
                    }else{
                        error_log( 'ffmpeg path:' . $nev_ffmpeg_path );
                    }
                    error_log( 'ffmpeg_status: ' . $ffmpeg_status );
                    error_log( 'ffmpeg output: ' . print_r($ffmpeg_output, true) );
                }
            }
            
            // Set video player options to default of they're not already set
            $nev_html5_encoding = get_site_option( 'nev_html5_encoding' );
            if( empty( $nev_html5_encoding ) ){
                update_site_option( 'nev_html5_encoding', true );
            }

            $nev_player_width = get_site_option( 'nev_player_width' );
            if( empty( $nev_player_width ) ){
                update_site_option( 'nev_player_width', 560 );
            }

            $nev_player_height = get_site_option( 'nev_player_height' );
            if( empty( $nev_player_height ) ){
                update_site_option( 'nev_player_height', 320);
            }    	
    	
    		global $wpdb;
    		$video_table = 'nev_videos';
    		$tag_table = 'nev_tags';
	        
	        $this->table_videos  = $wpdb->prefix . $video_table;
	        $this->table_tags = $wpdb->prefix . $tag_table;
    	
    		if($wpdb->get_var("SHOW TABLES LIKE '$this->table_videos'") != $this->table_videos) {
				$sql = "CREATE TABLE " . $this->table_videos . " (
						  id bigint(20) NOT NULL AUTO_INCREMENT,
						  title mediumtext NOT NULL,
						  description mediumtext NULL,
						  thumbnail varchar(255) NOT NULL,
						  videofile varchar(255) NOT NULL,
						  submitted smallint NOT NULL,
						  converted smallint NOT NULL,
						  tags text NULL,
						  UNIQUE KEY id (id)
      						) CHARSET=utf8 COLLATE=utf8_unicode_ci ;";
      			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			} 
	
    		if($wpdb->get_var("SHOW TABLES LIKE '$this->table_tags'") != $this->table_tags) {
				$sql_tag = "CREATE TABLE " . $this->table_tags . " (
						tag varchar(100) NOT NULL,
						UNIQUE KEY id (tag)
						  ) CHARSET=utf8 COLLATE=utf8_unicode_ci ;";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql_tag);
		
			}	
    	}
    	
    	function novelEngin_uninstall(){
    	
    	}
    	
    	static function enqueue_nev_js(){ 
    		 wp_enqueue_script( 'jquery' );
    		 wp_enqueue_script( 'nev_globals' , plugins_url('js/nev_globals.js', __FILE__), array( 'jquery' ) );
		     wp_enqueue_script( 'jquery-ui-core' );
         	 wp_localize_script( 'nev_globals', 'nev_globals', array(
                'NEV_ADMIN_URL'           => admin_url( 'admin.php' ),
                'NEV_AJAX_URL'            => admin_url( 'admin-ajax.php' ),
                'NEV_NONCE'	              => wp_create_nonce('nev_nonce'),
                'NEV_PLUGIN_PATH'         => NEV_PLUGIN_PATH,
                'NEV_IMAGE_PATH'          => NEV_IMAGE_PATH,
                'MAX_UPLOAD_SIZE'         => WP_MEMORY_LIMIT,
                'UPLOAD_SWF_URL'          => includes_url( 'js/plupload/plupload.flash.swf' ),
                'UPLOAD_SILVERLIGHT_URL'  => includes_url( 'js/plupload/plupload.silverlight.xap' )
                )
  			);
        }
	
	}

}

$NEVideoGallery = new NEVideoGallery();

if(isset($NEVideoGallery)){
	register_activation_hook(__FILE__,  array(&$NEVideoGallery,'novelEngin_install' ));
	register_deactivation_hook(__FILE__, array(&$NEVideoGallery,'novelEngin_uninstall' ));
}
?>