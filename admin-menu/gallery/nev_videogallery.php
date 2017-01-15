<?php
/**
*	Generates a table that displays all the submitted videos with their description
*   title, thumbnail, and tags. Upon clicking on a row, a user can edit these categories
*   and also search to find specific videos 	
*/

if ( !class_exists("nev_videogallery") ){
	
	class nev_videogallery{
		
		static function init(){
            wp_register_script( 'nev_videogalleryJS', plugins_url('/js/nev_videogallery.js', __FILE__), array('jquery', 'wp-mediaelement') );
            wp_enqueue_script( 'nev_videogalleryJS' );
            wp_enqueue_script( 'wp-mediaelement' );
            wp_enqueue_style( 'wp-mediaelement' );
		}

	// renders the pages settings
		static function render_settings(){
			$videos = nev_core::get_submitted_videos();
?>

<div class="wrap">
	<h2>Novel Engineering Video Gallery</h2>
	<p></p>
	<input type="text" id="nev_search_values" name="nev_search_values" value="">
	<button class="button" type="button" id="nev_search_button" >Search</button>
	<p><i>Click on the row to edit, view, or delete the video</i></p>
	<p></p>
	<div id="nev-update-video" style="display:none;"></div>
		<table class="widefat" cellspacing="0" align="center">
			<thead>
			<tr>
				<th scope="col" style="text-align: center;"><?php _e('ID'); ?></th>
				<th scope="col" style="text-align: center;"><?php _e('Thumbnail'); ?></th>
				<th scope="col" style="text-align: center; width:75px;"><?php _e('Title'); ?></th>
				<th scope="col" style="text-align: center;"><?php _e('Description'); ?></th>
				<th scope="col" style="text-align: center; width:50px;"><?php _e('Tags'); ?></th>
			</tr>
			</thead>           
			<tbody>
				<?php if ($videos >= 1) {
						$width  = get_site_option( 'nev_player_width' );
            			$height = get_site_option( 'nev_player_height' );
					for ($i = 0; $i < sizeof($videos); $i++){?>
						<tr class="nev-active-row" id="<?php echo 'nev-view-row' . $videos[$i]->id; ?>">
							<td scope="col" style="vertical-align: middle; text-align: center;"><?php echo $videos[$i]->id ?></td>
							<td scope="col" style="vertical-align: middle; text-align: center;">
							<?php $image = explode(',',$videos[$i]->thumbnail);
							if (file_exists($image[0])) {  ?>                                                   
								<img src=" <?php echo $image[1]; ?> " width=100px;>
							<?php  } else { ?> No Image</td> <?php  } ?>	
							<td id="<?php echo 'nevTitle' . $videos[$i]->id; ?>" scope="col" style="vertical-align: middle; text-align: center; width:75px;"><?php echo stripslashes($videos[$i]->title); ?></td>
							<td id="<?php echo 'nevDescrptn' . $videos[$i]->id; ?>" scope="col" style="vertical-align: middle; text-align: center;"><?php echo stripslashes($videos[$i]->description); ?></td>
							<td id="<?php echo 'nevTags' . $videos[$i]->id; ?>" scope="col" style="vertical-align: middle; text-align: center; width:50px;"><?php $tags = array_filter(explode(',',$videos[$i]->tags));
								$tagsString = "";
								for ($j = 0; $j < sizeof($tags); $j++){
									if($tags[$j] != ""){ 
										$tags[$j] = trim($tags[$j]);
										if ($j == (sizeof($tags)-1)){$tagsString .= $tags[$j];}
										else{$tagsString .= $tags[$j] . ", ";}
									}
								}
								echo $tagsString;
							 ?></td>
						</tr>
						<tr class="nev-edit-video-row" id="<?php echo 'nev-edit-row' . $videos[$i]->id; ?>" style="display:none;">
							<td scope="col" ><?php echo $videos[$i]->id ?></td>
							<td scope="col" colspan="4">
								<div>
									<div style="float:left;">
									<div id="<?php echo 'nevPlayerContainer-' . $videos[$i]->id; ?>" style="<?php echo 'width:' . $width . 'px; height: ' . $height . 'px; float:left; padding-right:20px;'; ?>" class="wp-video">
										<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->
										<?php $video =  explode(',',$videos[$i]->videofile);
											if (file_exists($video[0])) { ?>
											<video width="<?php echo $width; ?>" height="<?php echo $height; ?>" preload controls>
												<source type="video/mp4" src="<?php echo $video[1]; ?>">
											</video>
									<?php } else { ?> <div style="text-align:center;">Sorry! No video file exists</div> <?php } ?>
									</div>
										<div style="clear:both; padding-top:20px; float:left;">
											<p class="buttons">
												<button class="button-primary nev-update-video" type="button" id="<?php echo 'cancel_edit-' . $videos[$i]->id; ?>">Cancel</button>
												<button class="button-primary nev-update-video" type="button" id="<?php echo 'delete_video-' . $videos[$i]->id; ?>">Delete Video</button>
												<button class="button-primary nev-update-video" type="button" id="<?php echo 'update_video-' . $videos[$i]->id; ?>">Update Video</button>
											</p>
										</div>		
									</div>
									<div style="float:left;">
										<p class="video-title">
											<h4>Title: 
												<input type="text" id="<?php echo 'nev-video-title' . $videos[$i]->id; ?>" name="title" value="<?php echo stripslashes($videos[$i]->title); ?>"> 
											</h4>
										</p>
										<p>
											<textarea id ="<?php echo 'nevDescription' . $videos[$i]->id; ?>" style="margin: 0px; height: 150px; width: 350px;" ><?php echo stripslashes($videos[$i]->description);?></textarea>
										</p>
										<p>
											<div style="margin-top:-10px; padding-bottom:10px;">
											<div>
												<div id="<?php echo nevTagsList . $videos[$i]->id;?>" style="width:375px;"></div>
												<div style="padding-top:5px;"><a href="#" id="<?php echo $videos[$i]->id; ?>" class="nevViewTags">Click to view all tags</a></div>
												<div id="<?php echo nevDBtags . $videos[$i]->id; ?>" style="padding-top:5px; display: none; width:375px;"></div>
											</div>
										</p>
									</div>	
								</div>
							</td>						
						</tr>
				<?php	} ?>
				<?php } else {?>
					 <tr><td colspan="5" align="center"><strong> No entries found </strong></td></tr>
				<?php }	?>	
			</tbody>
				<tfoot>
					<tr>
						<th scope="col" style="text-align: center;"><?php _e('ID'); ?></th>
						<th scope="col" style="text-align: center;"><?php _e('Thumbnail'); ?></th>
						<th scope="col" style="text-align: center;"><?php _e('Title'); ?></th>
						<th scope="col" style="text-align: center;"><?php _e('Description'); ?></th>
						<th scope="col" style="text-align: center;"><?php _e('Tags'); ?></th>
					</tr>
				</tfoot> 
			</table>
	</div>

<?php
		}
	}
}