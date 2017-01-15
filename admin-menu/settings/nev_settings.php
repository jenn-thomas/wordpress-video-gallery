<?php
/**
* Allows the user to check for ffmpeg on server (and disable it) and add and delete tags
* from the db
*/
if ( !class_exists("nev_settings") ){

	define( 'DEBUG_NEV_VIDEO', false); // Set to true to debug the video component
   	define( 'NEV_DEFAULT_VIDEO_PATH', '/usr/local/bin/' ); // Default path to begin our search for ffmpeg
    define( 'NEV_DEFAULT_VIDEO_ENCODING', true); // Encode by default
    define( 'NEV_DEFAULT_PLAYER_WIDTH', 560) ; // Default player width
    define( 'NEV_DEFAULT_PLAYER_HEIGHT', 320 ); // Default player height
    define( 'NEV_VIDEO_SCRIPT_PATH', dirname( __FILE__ ) . '/html5video.php' ); // Script path
	
	class nev_settings{
	
		static function init(){
            	wp_register_script( 'nev_settingsJS', plugins_url('/js/nev_settings.js', __FILE__), array('jquery') );
            	wp_enqueue_script( 'nev_settingsJS' );
		}

		static function render_settings(){
?>
			<div class="wrap">
				<h2>Settings</h2>
				</br>
				<span id="nev_errors" style="color: red;"></span>
				<div style="border:2px white solid; padding-left:15px; padding-bottom:15px; padding-right:15px margin:15px;">
					<div>
						<h3>Find ffmpeg</h3>
 						<p>
            				The Video component uses <a href="http://www.ffmpeg.org/">ffmpeg</a> to format uploaded videos to HTML5 supported *.webm and *.mp4 formats.
            				It will try to detect if ffmpeg is available on the server. If it cannot find ffmpeg, it will not attempt at
            				formatting uploaded videos.
        				</p>
    		<?php
 			$nev_ffmpeg_path = get_site_option( 'nev_ffmpeg_path' );
    		if( DEBUG_NEV_VIDEO  ){
        	if( is_wp_error( $nev_ffmpeg_path ) ){
            	echo '<p>' . $nev_ffmpeg_path->get_error_message() . '</p>';
        	}
        	else {
            	exec('command -v ' . $nev_ffmpeg_path . 'ffmpeg', $cmd_output, $cmd_status);
    		?>
    					<div class="nev_error">
        					<p>Command: <?php echo 'command -v ' . $nev_ffmpeg_path . 'ffmpeg' ?></p>
        					<p>Status code: <?php echo $cmd_status ?></p>
        					<p>Command ouput: <?php echo print_r( $cmd_output, true) ?></p>
    					</div>
    		<?php 
        	} 
    		}
    		if( !is_wp_error( $nev_ffmpeg_path ) ){
        		$html5_encoding = get_site_option( 'nev_html5_encoding' );
    			$checked = $html5_encoding ? 'checked' : '';
    		?>     
    			<input type="checkbox" class="nev_enableHTML5Video" id="nev_html5video" <?php echo $checked ?> />
    			<label for="nev_html5video">
        			Encode uploaded video to HTML5 compatible formats. It is recommended to check the box
        			to enable this option.
    			</label>
  		  <?php
    		}
    		else {
    		?>
    			<div id="nev_ffmpeg_not_found" class="nev_error">
    			<p>
    				Doh! ffmpeg was not detected. If you are unsure what to do next, contact your server administrator or post to the WordPress forums for further help.
    			</p>
    			</div>
   		 <?php
    		}
		 ?>
    			<p>
    				Full path to the ffmpeg executable: 
        			<input type="text" id="nev_ffmpeg_path" name="nev_ffmpeg_path" value="<?php echo is_wp_error( $nev_ffmpeg_path ) ? '' : $nev_ffmpeg_path; ?>" />
        			<button class="button" type="button" id="nev_check_ffmpeg_path" name="nev_check_ffmpeg_path">Test</button>
    			</p>
    			<span id="nev_check_ffmpeg_results" style="color: green;"></span>
			</div>
		</div>
		</br>
		<div id="nev_tagContainer" style="border:2px white solid; padding-left:15px; padding-bottom:15px; padding-right:15px margin:15px;">
			<h3>Tags</h3>
				<i>Separate tags with commas if input contains multiple tags. It is NOT case sensitive (test and TEST are the same tag). Avoid using special characters e.g. ',?$%</br>
				These are all appropriate tags: <strong>test, test tag, tag</strong></i>
				<h4>Add Tags: 
					<input type="text" id="nev_added_tags" name="nev_added_tags" value="">
					<button class="button" type="button" id="nev_add_tags" name="nev_add_tags">Submit</button>
				</h4>
				<div id="nev_returnaddtags" style="color:green;"></div>
				<h4>Delete Tags: 
					<input type="text" id="nev_deleted_tags" name="nev_deleted_tags" value="">
					<button class="button" type="button" id="nev_delete_tags" name="nev_delete_tags">Submit</button>
				</h4>
				<div id="nev_returndeletetags" style="color:green; padding-bottom:5px;"></div>
				<a href="#" id="nev_viewTags">Click to view all tags</a>
				<div id="nev_currentTagsDelete"></div>
				<div id="nev_showTags" style="visibility: hidden; padding-top:10px;"></div>
		</div>
	</div>
<?php
		}
	}
}
?>