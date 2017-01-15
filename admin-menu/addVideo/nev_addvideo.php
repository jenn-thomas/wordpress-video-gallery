<?php
if ( !class_exists("nev_addvideo") ){
	
	class nev_addvideo{
		
		static function init(){
			wp_register_script( 'nev_addVideoJS', plugins_url('/js/nev_addvideo.js', __FILE__), array('jquery', 'plupload-all', 'wp-mediaelement') );
            wp_enqueue_script( 'nev_addVideoJS' );
            wp_localize_script( 'nev_addVideoJS', 'nev_addvideoJS', array('NEV_VIDEO_HTML5' => (bool) get_site_option( 'nev_html5_encoding' )));
            wp_register_style( 'nev_addVideoCSS', plugins_url('css/nev_addvideo.css', __FILE__) );
            wp_enqueue_script( 'wp-mediaelement' );
            wp_enqueue_style( 'nev_addVideoCSS' );
            wp_enqueue_style( 'wp-mediaelement' );
		}

// renders the page
		static function render_settings(){
			$data = nev_core::get_last_video();
?>

<div class="wrap">
	<div id="nev_component_errors" style="display: block; color:red;"></div>
	<h2>Add Video</h2>
		<div id="nevAJAXalerts" style="display: block; color:green;"></div>
		<form id="target">
			<p>
				<h4>Title: 
				<input type="text" id="nev-video-title" name="title" value="<?php echo stripslashes($data->title); ?>"> <p id="nevNoTitle" style="display:none; color:red;">* Please input a title for your video</p>
				</h4>
			</p>
			<p>
				<h4> Upload Video:</h4>
				<div id="nev_video" class="nev_video">
                	<div style="padding:10px; text-align:center; display: none;" id="nevBeingConverted">
                	<img src="<?php echo NEV_IMAGE_PATH . '/loading.gif' ?>"> Your video is being converted! Feel free to come back later.
                	</div>
                	<div style="display: none;" id="nevUploadVideo">
                		<div id="nevvideoUploader" class="nevVideoUploader" >
                			<p id="nevVideoProgressMsg" class="nevVideoProgressMsg">
                			</p>
                			<div id="nevVideoProgBarContainer" class="neVideoProgBarContainer">
                				<div id="neVideoProgressBar" class="neVideoProgressBar"></div>
                			</div>
                			<div class="clear"></div>
            			</div>
						<div id="nev_videoDropZone" class="nev_videoDropZone" style="text-align: center;">
                			<button type="button" data-compid="" id="nev-video-browse" class="nev-video-browse nev-browse-button button">
                			Upload a Video</button>
                			<p>You can also drag and drop a video file here</p>
                    		<input id="nev_videoBrowse" data-compid="" type="file" style="display:none;">
                		</div>
                	</div>	
                	<div style="padding:10px; text-align:center; display: none;" id="nevVideoConverted">
                		<div style="padding-bottom:10px;">
                			Success! View your video below or delete it to upload a new one.
                		</div>
                		<div>
                			<button class="button" type="button" id="nev_view_video">View Video</button>
                			<button class="button" type="button" id="nev_delete_video">Delete Video</button>
                		</div>
                	</div>
            	</div>
			</p>					
			<p>
				<h4>Description:</h4>
				<textarea id ="nevDescription" style="margin: 0px; height: 150px; width: 350px;" ><?php echo stripslashes($data->description)?></textarea>
			</p>
			<p>
				<h4>Add Tags:</h4>
				<div style="margin-top:-10px; padding-bottom:10px;"><i>If you submit the form with a tag that does not exist, it will be added automatically to the list of tags</i></div>
				<div>
					<input type="text" id="nevAddedTags">
					<button class="button" type="button" id="nevAdd">Add</button>
					<div style="padding-top:5px; padding-bottom:5px;"><i>Separate tags with commas</i></div>				
					<div id="nevTagsList">
					</div>
					<div style="padding-top:5px;"><a href="#" id="nevClickTags">Click to view all tags</a></div>
					<div id="nevCurrentTags" style="padding-top:5px; display: none;"></div>
				</div>
			</p>
			<p class="buttons">
				<button class="button-primary nev-send-data" type="button" id="submit_form" name="submit">Submit</button>
				<button class="button-primary nev-send-data" type="button" id="save_form" name="save_form">Save Form</button>
			</p>
		</form>
</div>

<?php
		}

        /**
         * Handles chunked AJAX uploads (using plupload plugin).
         * @param $file_data_id
         * @return bool|mixed - The file path of the whole file (when chunks = 0), otherwise false (when chunks > 0).
         */
        static function chunked_plupload($file_data_id){

            // Get a file name
            if ( isset( $_REQUEST["name"] ) ) {
                $fileName = $_REQUEST["name"];
            } elseif ( !empty( $_FILES ) ) {
                $fileName = $_FILES[$file_data_id]["name"];
            } else {
                $fileName = uniqid("file_");
            }

            $uploadsPath = wp_upload_dir();
            $filePath =  $uploadsPath['path'] . DIRECTORY_SEPARATOR . $fileName;

            // Chunking might be enabled
            $chunk  = isset($_REQUEST["chunk"])  ? intval($_REQUEST["chunk"])  : 0;
            $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

            // Open temp file
            if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
                header( "HTTP/1.0 409 Error Code 102: Failed to open output stream." );
                exit;
            }

            if ( !empty($_FILES) ) {
                if ($_FILES[$file_data_id]["error"] || !is_uploaded_file($_FILES[$file_data_id]["tmp_name"])) {
                    header( "HTTP/1.0 409 Error Code 103: Failed to move uploaded file." );
                    exit;
                }

                // Read binary input stream and append it to temp file
                if (!$in = @fopen($_FILES[$file_data_id]["tmp_name"], "rb")) {
                    header( "HTTP/1.0 409 Error Code 101: Failed to open input stream." );
                    exit;
                }
            } else {
                if (!$in = @fopen("php://input", "rb")) {
                    header( "HTTP/1.0 409 Error Code 101: Failed to open input stream." );
                    exit;
                }
            }

            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }

            fclose($out);
            fclose($in);

            // Check if file has been uploaded
            if (!$chunks || $chunk == $chunks - 1) {
                // Format the file name
                $newFilePath = preg_replace( '/\s+/', '', $filePath ); // Remove all whitespace
                $path_parts  = pathinfo($newFilePath);

                /**
                 * - Adds a unique ID to keep file names unique
                 * - Makes all file extensions lowercase (cases like .JPG or .MOV => .jpg/.mov)
                 * - sanitize_title slugifies the filename, so names like "John's Movie.mov" => "johnsmovie.mov"
                 */
                $newFilePath = $path_parts['dirname'] . '/' . sanitize_title( $path_parts['filename'] ) . '_' . uniqid() . '.' . strtolower( $path_parts['extension'] );
                rename( "{$filePath}.part", $newFilePath ); // Strip the temp .part suffix off
                return $newFilePath;
            }else{
                return false;
            }
        }
		/**
         * Compresses to 560px x 320px, orients (if necessary), and encodes a video file into .mp4 (if necessary).
         * @param $uploaded_video
         */
        static function encode_via_ffmpeg($uploaded_video){
            $nev_ffmpeg_path = get_site_option( 'nev_ffmpeg_path' );
            $html5_encoding = (bool) get_site_option( 'nev_html5_encoding' );
            $data = nev_core::get_last_video();
            nev_core::update_component('videofile', $uploaded_video, $data->id);
            if( $html5_encoding && !is_wp_error( $nev_ffmpeg_path ) ){
                global $wpdb;

                $script_args = array(
                    'DB_NAME' => DB_NAME,
                    'DB_USER' => DB_USER,
                    'DB_HOST' => DB_HOST,
                    'DB_PASS' => DB_PASSWORD,
                    'WP_DB_PREFIX' => $wpdb->prefix,
                    'VID_FILE' => $uploaded_video,
                    'WIDTH'  => get_site_option('nev_player_width'),
                    'HEIGHT' => get_site_option('nev_player_height'),
                    'FFMPEG_PATH' => $nev_ffmpeg_path
                );

                if( DEBUG_NEV_VIDEO ){
                    error_log( 'SCRIPT ARGS: ' . print_r($script_args, true) );
                    exec('php ' . NEV_VIDEO_SCRIPT_PATH . ' ' . implode(' ', $script_args) . ' 2>&1', $output, $status);
                    error_log( print_r($output, true) );
                    error_log( print_r($status, true) );
                }else{
                    shell_exec('php ' . NEV_VIDEO_SCRIPT_PATH . ' ' . implode(' ', $script_args) . ' &> /dev/null &');
                }
            }else{

                // Check to see that it's mp4 format
                $ext = pathinfo( $uploaded_video, PATHINFO_EXTENSION );
                if( $ext !== 'mp4' ){
                    unlink( $uploaded_video );
                    header( "HTTP/1.0 409 Error: only mp4 files are allowed to be uploaded when HTML5 encoding is not enabled!" );
                    exit;
                }else{
                    // Create the attachment
                    exit;
                }
            }
        }        
	}	
}
