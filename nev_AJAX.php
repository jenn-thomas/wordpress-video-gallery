<?php
/*
* All AJAX functions for video gallery
*/
if ( !class_exists("nev_AJAX") ){
	
	class nev_AJAX{
		
        /**
         * adds all ajax functions
         */
		static function init(){
			add_action('wp_ajax_nev_enableHTML5VideoAJAX', array('nev_AJAX', 'nev_enableHTML5VideoAJAX'));
            add_action('wp_ajax_nev_check_for_ffmpeg', array('nev_AJAX', 'nev_check_for_ffmpeg'));
            add_action('wp_ajax_nev_delete_tags', array('nev_AJAX', 'nev_delete_tags'));
            add_action('wp_ajax_nev_add_tags', array('nev_AJAX', 'nev_add_tags'));
            add_action('wp_ajax_nev_load_tags', array('nev_AJAX', 'nev_load_tags'));
            add_action('wp_ajax_nev_videoUploadAJAX', array('nev_AJAX', 'nev_videoUploadAJAX'));
            add_action('wp_ajax_nev_checkVideoStatusAJAX', array('nev_AJAX', 'nev_checkVideoStatusAJAX'));
            add_action('wp_ajax_nev_get_form_data', array('nev_AJAX', 'nev_get_form_data'));
            add_action('wp_ajax_nev_delete_video', array('nev_AJAX', 'nev_delete_video'));
            add_action('wp_ajax_nev_save_form', array('nev_AJAX', 'nev_save_form'));
            add_action('wp_ajax_nev_submit_form', array('nev_AJAX', 'nev_submit_form'));
            add_action('wp_ajax_nev_get_videos', array('nev_AJAX', 'nev_get_videos'));
            add_action('wp_ajax_nev_delete_video_row', array('nev_AJAX', 'nev_delete_video_row'));
            add_action('wp_ajax_nev_update_video', array('nev_AJAX', 'nev_update_video'));
            add_action('wp_ajax_nev_search_videos', array('nev_AJAX', 'nev_search_videos'));
            add_action('wp_ajax_nopriv_nev_search_videos', array('nev_AJAX', 'nev_search_videos') );
		}	
		
        /**
         * gets all submitted videos from database
         */
		static function nev_get_videos(){
			if (!is_user_logged_in()){
                header("HTTP/1.0 403 Security Check.");
                exit;
			}
			$videos = nev_core::get_submitted_videos();
			echo json_encode( array('videos' => $videos));
			exit;
		}
	
        /**
         * Checks for and save ffmpeg path
         */
     	static function nev_check_for_ffmpeg(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                exit;
            }

            if( empty( $_POST[ 'ffmpeg_path' ] ) ){
                header("HTTP/1.0 403 Provided path is empty.");
                exit;
            }
            
            if (!is_user_logged_in()){
                header("HTTP/1.0 403 Security Check.");
                exit;
			}

            $nev_ffmpeg_path = (string) stripslashes_deep( $_POST[ 'ffmpeg_path' ] );

            exec('command -v ' . $nev_ffmpeg_path . 'ffmpeg', $cmd_output, $cmd_status);

            if( $cmd_status === 0 ){
                update_site_option( 'nev_ffmpeg_path', $nev_ffmpeg_path );
                echo json_encode( array( 'success' => true, 'status_code' => $cmd_status, 'output' => $cmd_output[0] ) );
            }else{
                echo json_encode( array( 'status_code' => $cmd_status, 'output' => $cmd_output[0] ) );
            }

            exit;
        }

        /**
         * If ffmpeg is detected, handles checkbox AJAX to enable/disable HTML5
         * video encoding.
         */
        static function nev_enableHTML5VideoAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                exit;
            }
            
            if (!is_user_logged_in()){
                header("HTTP/1.0 403 Security Check.");
                exit;
			}

            if(is_wp_error($mediaComp->errors)){
                header( 'HTTP/1.0 403 ' . $mediaCom->errors->get_error_message() );
                exit;
            }

            $convertToHTML5 = (bool) $_POST['convertToHTML5'];

            $success = update_site_option( 'nev_html5_encoding', $convertToHTML5);

            echo json_encode( array('success' => $success) );

            exit();
        }	
        
        /**
		 * Deletes selected tags from user from the database
         */
        static function nev_delete_tags(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                exit;
            }
            
            if (!is_user_logged_in()){
                header("HTTP/1.0 403 Security Check.");
                exit;
			}
			
            $tags = $_POST['tags'];
            
            if( empty( $_POST[ 'tags' ] ) ){
                echo json_encode( array( 'success' => true, 'tags' => 'none' ) );
                exit;
            }
            else{
            	global $wpdb;
            	$tableName = $wpdb->prefix . 'nev_tags';
            	$tags = explode(",", $tags);
            	$tagsDeleted = array(); $tagsDontExist = array();
            	for ($x = 0; $x < sizeof($tags); $x++){
            		$tags[$x] = trim($tags[$x]);
            		$result = $wpdb->get_row($wpdb->prepare("SELECT tag FROM $tableName WHERE tag ='". $tags[$x] . "'"));
            		if (isset($result)){
            			$wpdb->delete($tableName, array('tag' => $tags[$x]));
            			array_push($tagsDeleted, $tags[$x]);
            			$wpdb->query("UPDATE wp_nev_videos SET tags = REPLACE(tags, ' " . $tags[$x] . " ,', '');");
            		}
            		else{
            			array_push($tagsDontExist, $tags[$x]);
            		}
            	}
            	echo json_encode( array( 'tags' => $tagsDeleted, 'notags' => $tagsDontExist) );
            }
           exit();
        }
        
        /**
		 * Adds selected tags from user input to the database
         */
        static function nev_add_tags(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                exit;
            }
            
            if (!is_user_logged_in()){
                header("HTTP/1.0 403 Security Check.");
                exit;
			}
            
            $tags = $_POST['tags'];
            
            if( empty( $_POST[ 'tags' ] ) ){
                echo json_encode( array( 'tags' => 'none' ) );
                exit;
            }
            else{
            	global $wpdb;
            	$tableName = $wpdb->prefix . 'nev_tags';
            	$tags = explode(",", $tags);
            	$tagsAdded = array(); $tagsExist = array();
            	for ($x = 0; $x < sizeof($tags); $x++){
            		$tags[$x] = trim($tags[$x]);
            		$result = $wpdb->get_row($wpdb->prepare("SELECT tag FROM $tableName WHERE tag ='". $tags[$x] . "'"));
            		if(isset($result)){
						array_push($tagsExist,$tags[$x]);
            		}
            		else{
            			$wpdb->replace($tableName, array('tag' => $tags[$x]));
            			array_push($tagsAdded,$tags[$x]);
            		}
            	}    	
            	echo json_encode( array( 'tags' => $tagsAdded, 'tagsExist' => $tagsExist) );
            }
           exit();
        }

        /**
		 * Gets all tags from database and returns as array of objects
         */
        static function nev_load_tags(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                exit;
            }
            
            if (!is_user_logged_in()){
                header("HTTP/1.0 403 Security Check.");
                exit;
			}
			
            global $wpdb;
            $tableName = $wpdb->prefix . 'nev_tags';
            $tags = $wpdb->get_results("SELECT * FROM $tableName");
            echo json_encode( array('tags' => $tags));
            exit();
        }
        
     	 /**
         * Handles video uploads using chunking.
         */
        static function nev_videoUploadAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(empty($_FILES)){
                header("HTTP/1.0 409 Files uploaded are empty!");
                exit;
            }

            $uploaded_video = nev_addvideo::chunked_plupload( "nev_videoUpload" );
            if( file_exists( $uploaded_video ) ){

                // Attempt to encode the video
               nev_addvideo::encode_via_ffmpeg($uploaded_video);

            } elseif ( $uploaded_video !== false && !file_exists( $uploaded_video ) ) {
                header( "HTTP/1.0 409 There was an error with uploading the video. The video file could not be found." );
            }
            exit;
        }
        
        /**
         * Checks on the status of the video.
         */
        static function nev_checkVideoStatusAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }
            $html5_encoding = (bool) get_site_option( 'nev_html5_encoding' );
			$videoComponent = nev_core::get_last_video();
            // Check if video conversion is over
            if($html5_encoding == false){
            	if (file_exists($videoComponent->videofile)) {
            		nev_core::upload_media($videoComponent->id,$videoComponent);
            		echo json_encode( array( 'converted' => true) );
            		exit;
            	}
            } else {
            	if( $videoComponent->converted ){
            		nev_core::upload_media($videoComponent->id,$videoComponent);
            		$video = nev_core::get_last_video();
            		$videofile = explode(',', $video->videofile);
                	if( file_exists($videofile[0]) ) {
                    	echo json_encode( array( 'converted' => true ) );
                    	exit;
                	}
            	} else {
                	echo json_encode( array( 'converted' => false ) );
                	exit;
            	}
            }
            exit;
        }
        
        /**
         * upon load, gets any inputted information that was saved
         */
        static function nev_get_form_data(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }
            
        	if (!is_user_logged_in()){
                header("HTTP/1.0 403 Security Check.");
                exit;
			}
        	
        	$result = nev_core::get_last_video();
        	$html5_encoding = (bool) get_site_option( 'nev_html5_encoding' );
        	echo json_encode( array( 'data' => $result, 'bool' => $html5_encoding));
        	exit;
        }
        
        /**
         * deletes video from form and from db specified by the videos id
         */
        static function nev_delete_video(){
        	$nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }
            
        	if (!is_user_logged_in()){
                header("HTTP/1.0 403 Security Check.");
                exit;
			}
        	$info = nev_core::get_last_video();
        	$id = $info->id;
        	$video = explode(',',$video->videofile);
        	$image = explode(',',$video->thumbnail);
        	unlink($video[0]);
        	unlink($image[0]);
        	nev_core::update_component('videofile', "", $id);
        	nev_core::update_component('thumbnail', "", $id);
        	nev_core::update_component('converted', 0, $id);
        	echo json_encode( array( 'success' => true ) );
        	exit;
        }
        
        /**
         * saves the add video form
         */
        static function nev_save_form(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }
            
            if (!is_user_logged_in()){
                header("HTTP/1.0 403 Security Check.");
                exit;
			}
			
            $data = $_POST['info'];
			nev_core::save_form($data);
        	echo json_encode( array( 'action' => 'save_form') );
        	exit;
        }
        
        /**
         * submits the add video form
         */
        static function nev_submit_form(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }
            
            if (!is_user_logged_in()){
                header("HTTP/1.0 403 Security Check.");
                exit;
			}
			
            $data = $_POST['info'];
            $video = nev_core::save_form($data);
            $html5_encoding = (bool) get_site_option( 'nev_html5_encoding' );
            if ($html5_encoding) {
            	if ($video->converted != 1 && $video->videofile != ""){
            		$message = 'Please wait while the video is being converted';
            		echo json_encode ( array ('success' => false, 'message' => $message) );
            		exit;
            	}
            	elseif ($video->videofile == ""){
            		$message = 'Please upload a video before submitting the form';
            		echo json_encode ( array ('success' => false, 'message' => $message ) );
            		exit;
            	}
             } else {
            	if ($video->videofile == ""){
            		$message = 'Please upload a video before submitting the form';
            		echo json_encode ( array ('success' => false, 'message' => $message ) );
            		exit;
            	}
             }
            nev_core::update_component('submitted', 1, $video->id);
        	echo json_encode( array ('action' => 'submit_form' , 'success' => true) );
        	exit;
        }
        
        /**
         * deletes video and all its data in database
         */
        static function nev_delete_video_row(){
        	$nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }
            
            if (!is_user_logged_in()){
                header("HTTP/1.0 403 Security Check.");
                exit;
			}
			
            $id = $_POST['id'];
           	nev_core::delete_video($id);
            echo json_encode(array('success' => true, 'id' => $id));
            exit;
        }
        
        /**
         * updates video if changes are submitted in back end video gallery
         */
        static function nev_update_video(){
        	$nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'nev_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }
            
            if (!is_user_logged_in()){
                header("HTTP/1.0 403 Security Check.");
                exit;
			}
			
            $data = $_POST['info'];
			$return = nev_core::update_video($data);
			if ($return){
            	echo json_encode(array('success' => true, 'id' => $data['id'], 'data' => $data));
            	exit;
            }
            exit;
        }
        
        /**
         * updates video if changes are submitted in back end video gallery
         */
         static function nev_search_videos(){
            $terms = trim($_GET['terms']);
            if ($terms == ""){$videos = nev_core::get_submitted_videos();}
            else{$videos = nev_core::videos_search($terms);}
            echo json_encode(array('videos' => $videos));
            exit;
         }
	} // end of nev_AJAX class
} // end of !class_exists