<?php
/*
* Front end video gallery generated by a shortcode [nevgallery]
*/
 if ( !class_exists("nev_shortcode") ){
	
	class nev_shortcode{
	 	function init(){
 			add_shortcode('nevgallery', array('nev_shortcode' , 'nevgallery_func') );
 			wp_register_script( 'nev_shortcodeJS', plugins_url('/js/nev_shortcode.js', __FILE__), array('jquery') );
            wp_enqueue_script( 'nev_shortcodeJS' );
			wp_register_style( 'nev_shortcodeCSS', plugins_url('css/nev_shortcode.css', __FILE__) );
            wp_enqueue_style( 'nev_shortcodeCSS' );
 		}
 		
 		// [nevgallery] shortcode
 		// generates front end video gallery on page where the shortcode exists
 		static function nevgallery_func(){
 		    $width  = get_site_option( 'nev_player_width' );
            $height = get_site_option( 'nev_player_height' );
 			$videos = nev_core::get_submitted_videos();	
 				if (isset($_GET["t"]) && $_GET["t"] != ""){
 					$videosTags = nev_core::videos_by_tag($_GET["t"]);
 				} else {
 					$videosTags = false;}
				$url = get_permalink();
				if (strpos($url, '?') !== false){$char = '&';}
				else {$char = '?';}
 		?>
 			<div style="float:right;">
 				<form method='post' id="nev-FE-form">
 					<input type="text" id="search-nev-FE">
					<input class="button" type="submit" value="Search Videos" id="search-nevbuttonFE">
				</form>
			</div>	
			<div class="nev-no-match" style="display:none; text-align:center; padding-top:20px;">There are no videos that match your query. Try searching something else!</div>
 			<div id="nev-container" style="padding-top:20px; clear:both;">
 				<?php for ($i = 0; $i < sizeof($videos); $i++){
 					if($videosTags){
 						if (array_search($videos[$i]->id,$videosTags) === false){ $display = "none"; }
 						else { $display = "initial"; }
 					}
 					else { $display = "initial"; }
 					?> <div id="<?php echo 'div-' . $videos[$i]->id; ?>" class="nevImageDiv" style="<?php echo 'display:' . $display ?>">
						<?php $image = explode(',',$videos[$i]->thumbnail);
							if (file_exists($image[0])) {  ?>                                                   
							<a href="#" class="nev-video-link" id="<?php echo $videos[$i]->id; ?>"><img src="<?php echo $image[1]; ?> " width=275px;></a>
						<?php  } else {?>
							<a href="#" class="nev-video-link" id="<?php echo $videos[$i]->id; ?>">
							<img src="<?php echo NEV_IMAGE_PATH . '/noImage.png' ?>" width=275px>
							</a>
						 <?php  } ?>
						<div class="nevTitle" id="<?php echo 'nevTitle' . $videos[$i]->id; ?>"><?php echo stripslashes($videos[$i]->title); ?></div>
 						</div>
 			 	<?php } ?>
 				</div>
 					<div class="nevbackdrop"></div>
 					<?php for ($i = 0; $i < sizeof($videos); $i++){ ?>
 						<div class="nevbox" id="<?php echo 'popup' . $videos[$i]->id; ?>">									
 							<div class="nev-popup" style="float:left; margin: 0 auto; background-color:black; width: <?php echo $width . 'px;'?>; padding:10px;">
										<div style="<?php echo 'width:' . $width . 'px;' . 'height:' . $height . 'px;' ?>" id="nev-video-player-container" class="wp-video">
										<?php $video = explode(',',$videos[$i]->videofile);
											if (file_exists($video[0])) { 
												$v = "[video mp4= " . $video[1] . " width=" . $width . " height=" . $height . " preload='auto'][/video]";
												echo do_shortcode($v); ?>
<!-- 
											<video width="<?php echo $width . 'px';?>" height="<?php echo $height . 'px';?>" id="<?php echo 'video' . $videos[$i]->id ?>" preload controls>
												<source type="video/mp4" src="<?php echo $video[1]; ?>">
											</video>
 -->
										<?php } else { ?> <div style="text-align:center">No video file exists</div> <?php } ?>
										</div>	
										<div style="padding-top:10px;">
											<strong><?php echo stripslashes($videos[$i]->title); ?></strong>
										</div>
										<div style="font-size:12px; max-height:63px; overflow:auto;">
											<?php echo stripslashes($videos[$i]->description); ?>
										</div>
											<div class="nev-tag-string" style="font-size:12px;">		
											<?php echo "Tags: ";						
												$tags = array_filter(explode(',',$videos[$i]->tags));
												$tagsString = "";
												if (sizeof($tags) > 0){	
													for ($j = 0; $j < sizeof($tags); $j++){
														if($tags[$j] != ""){ 
															$link = $url . $char . "t=" . $tags[$j];
															$tags[$j] = trim($tags[$j]);
															if ($j == (sizeof($tags)-1)){$tagsString .= "<a class='tags-FE' href='" . $link . "' target='_blank'>" . $tags[$j] . "</a>";}
															else{$tagsString .= "<a class='tags-FE' href='" . $link . "' target='_blank'>" . $tags[$j] . "</a>, ";}
														}
													}
												} else { $tagsString = "<i> No tags found</i>"; }
													echo $tagsString; ?>
											</div>
									</div>
								 <div class="nevclose"><strong>x</strong></div>
						</div>
 		<?php }
 		}
	} // end of nev_shortcode class
} // end of if class exists