<?php
/*
* Core functions for the video gallery plugin, generally called from nev_AJAX.php
*/
if (!class_exists(nev_core)){
		class nev_core{		
        /**
         * Grabs the video that has yet to be submitted to the gallery
         */
			static function get_last_video() {
				global $wpdb;
				$id = $wpdb->insert_id;
            	$tableName = $wpdb->prefix . 'nev_videos';
            	$result = $wpdb->get_row("SELECT * FROM $tableName ORDER BY ID DESC LIMIT 0 , 1");
            	if (!empty($result)){
            		if ( $result->submitted){
            			$id = nev_core::create_new_row();
            			$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tableName WHERE id =  %d", $id));
            			return $result;
            		}
            		else{ return $result; }
            	}
            	else { 
            		$id = nev_core::create_new_row();
            		$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tableName WHERE id =  %d", $id));
            		return $result;
            	}
			}
        /**
         * Creates a new video for the add video form to be submitted
         */
			static function create_new_row() {
				global $wpdb;
				$tableName = $wpdb->prefix . 'nev_videos';
				$wpdb->insert($tableName,
                        array(
                            'title' => '',
                            'description' => 'Enter Description',
                            'thumbnail' => '',
                            'videofile' => '',
                            'submitted' => 0,
                            'converted' => 0,
                            'tags' => ''
                        ),array('%s', '%s', '%s', '%s', '%d', '%d', '%s'));
                 $lastid = $wpdb->insert_id; 
                 return $lastid;      
			}
		/**
         * updates a component in the nev_videos table 
    	 * @param $column - column for the value to be updated
         * @param $value - the value that is being inputted into the db
         * @param $id - the id of the component to be update
         */
			static function update_component($column, $value, $id) {
				global $wpdb;
            	$tableName = $wpdb->prefix . 'nev_videos';
            	$wpdb->update(
            		$tableName,
            		array ($column => $value),
            		array( 'id' => $id )
            	);
			}
		/**
         * saves the form to the db to be viewed for later 
    	 * @param $data - an array of information containing values from the form
         */
			static function save_form($data) {
            	$video = nev_core::get_last_video();
            	$id = $video->id;
            	nev_core::update_component('title', $data['title'], $id);
        		nev_core::update_component('description', $data['description'], $id);
        		nev_core::update_component('tags', $data['tags'], $id);
        		return $video;
			}
			
		/**
         * updates the video from the video gallery (back end)
    	 * @param $data - an array of information containing values from the form
         */
			static function update_video($data) {
            	$id = $data['id'];
            	nev_core::update_component('title', $data['title'], $id);
        		nev_core::update_component('description', $data['description'], $id);
        		nev_core::update_component('tags', $data['tags'], $id);
        		return true;
			}
			
		/**
         * deletes the video from the video gallery and all its attributes
    	 * @param $id - video id to delete row
         */	
         	static function delete_video($id) {
				global $wpdb;
            	$tableName = $wpdb->prefix . 'nev_videos';
            	$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tableName WHERE id =  %d", $id));
            	$video = explode(",",$result->videofile);
            	$image = explode(",",$result->thumbnail);
            	unlink($video[0]);
            	unlink($image[0]);
            	$wpdb->delete(
            		$tableName,
            		array( 'id' => $id )
            	);
			}
			
		/**
         * gets all submitted videos in db
         */
			static function get_submitted_videos() {
				global $wpdb;
				$tableName = $wpdb->prefix . 'nev_videos';
				$videos = $wpdb->get_results("SELECT * FROM $tableName WHERE submitted = 1");
				return $videos;
			}
			
		/**
         * gets video by search terms and checks title, description, and tags for match
         * @param terms - search terms entered by user
         */
         	static function videos_search($terms){
         		$terms = explode(' ', $terms);
				$videos = nev_core::get_submitted_videos();
				$matches = array();
				$total = sizeof($terms);
         		for ($i=0; $i < sizeof($videos); $i++){
         			$count = 0;
         			$string = strtolower(stripslashes($videos[$i]->title) . " " . stripslashes($videos[$i]->description) . " " . stripslashes($videos[$i]->tags));
         			for ($j=0; $j < $total; $j++){
         				if (strpos($string,trim(strtolower($terms[$j]))) !== false){$count++;}
         			}
         			if ($count == $total){array_push($matches,$videos[$i]->id);}
         		}
				return $matches;
         	}
		
		/**
         * gets video by tag match
         * @param tag - tag search term
         */
         	static function videos_by_tag($tag){
         		$videos = nev_core::get_submitted_videos();
				$matches = array();
				for ($i=0; $i < sizeof($videos); $i++){
					if (strpos($videos[$i]->tags,trim($tag)) !== false){
						array_push($matches,$videos[$i]->id);
					}
				}
				return $matches;
         	}
         /**	
         * uploads the video and thumbnail to the video gallery
         */
			static function upload_media($id,$info){
				$html5_encoding = (bool) get_site_option( 'nev_html5_encoding' );
				$video_info = nev_core::get_last_video();
            	$id = $video_info->id;
				if ($html5_encoding){
					$image = wp_upload_bits(basename($info->thumbnail,'.'. $ext), null, @file_get_contents($info->thumbnail));
					if (file_exists($image['file'])){
                		nev_core::update_component('thumbnail', $image['file'] . ',' . $image['url'], $id);
                	}
                	unlink($info->thumbnail);
				}
			    $video = wp_upload_bits(basename($info->videofile,'.'. $ext), null, @file_get_contents($info->videofile));
            	unlink($info->videofile);
                nev_core::update_component('videofile', $video['file'] . ',' . $video['url'], $id);

			}
	}      
}	