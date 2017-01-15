// video gallery (back end) javascript

(function($){
    novel_engin_galleryBE = {  
    
 		/**
        * updates the row with the newly submitted data
        * @param data - object containing the data from the updated video
        */
    	update_row: function(data){
    		var id = data['id'];
    		var description = data['description'];
    		var tags = data['tags'].split(',').filter(function(e){return e});
    		var tagsString = "";
    		for (var i=0; i < tags.length; i++){
    			if (tags[i] != ""){
    				if (i == tags.length-1) {tagsString += tags[i].trim();}
    				else {tagsString += tags[i].trim() + ', ';}
    			}
    		}
    		var title = data['title'];
    		$('#nevTitle' + id).text(title);
    		$('#nevDescrptn' + id).text(description);
    		$('#nevTags' + id).text(tagsString);
    	},
    	
 		/**
        * adds tags to tag list
        * @param id - id of the video (listed in table column id)
        * @param tag - name of the tag
        * @param tagid - id of the tag span
        */
        addToList: function(id, tag, tagid){
        	var idArray = [];
        	$('#nevTagsList' + id).children().each(function(){
            	idArray.push($(this).attr('id'));  
     		})
     		// check if tag already exists
          	if (idArray.indexOf(tagid + '_' + id) == -1 && tag != ""){
      			var html = "<span id='" + tagid + '_' + id + "'><a id='" + tagid + '_' + id + "' href='#' class='nev-close-btn'>X</a>    "
      			+ tag + "   </span>";
				$('#nevTagsList' + id).append(html);
				novel_engin_galleryBE.tagClick();
  			}
        },
        
 		/**
        * Loads currently stored tags in database and initializes them in the DOM
        * @param id - id of the video (listed in table column id)
        */
    	load_tags: function(id){
    		$.ajax({
                url : NEV_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'nev_load_tags',
                    nonce: NEV_NONCE
                },
                dataType : 'json',
                success : function(response){
                	 if (response.tags.length >= 1){
                		var html = "Select a tag to add to the list:</br>"
                		$('#nevDBtags' + id).html(html);
						for (var i = 0; i < response.tags.length; i++){
							if (response.tags[i]['tag'] != ""){
								var link = $('<a>',{
   									text: response.tags[i]['tag'],
 									id: response.tags[i]['tag'].split(' ').join('_'),
    								href: '#',
    								class: 'tag'
								}).appendTo('#nevDBtags' + id);
								if (i != response.tags.length-1) $('#nevDBtags' + id).append(', ');
							}
						}
					}
					else { $('#nevDBtags' + id).html("No tags exist! Why don't you add some?");}
					$('a.tag').click(function(event){
    					event.preventDefault();
    					var tag = $(this).text();
    					var tagid = $(this).attr("id");
    					novel_engin_galleryBE.addToList(id, tag, tagid)
        			})
                },
                error : function(jqXHR, statusText, errorThrown){
                	errorElem = '#nev_errors';
            		$(errorElem).html('<p>Error: ' + statusText + ': ' + errorThrown + '</p>');
            		$(errorElem).parent().show();
                }
            })
        },
        
        // if tag from taglist (generated from db) is clicked or hovered over
        tagClick: function(){
			$('.nev-close-btn').hover(
  				function(){
  					$(this).css("background-color", "red");
  					$(this).css("color", "white");
  				},
  				function(){
  					$(this).css("background-color", "#605F61");
  				}
  			);
  			$('.nev-close-btn').click(function(event){
  				event.preventDefault();
  				var name = $(this).attr("id");
  				var id = '#' + name;
  				$('span').remove(id);
  			})   
        },
        
        
 		/**
        * grabs the data from the form columns for the specific video
        * @param id - id of the video (listed in table column id)
        */
        get_form_data: function(id){
        	var data = {};
        	// title
        	data['title'] = $('#nev-video-title' + id).val();
        	// description
        	data['description'] = $('#nevDescription' + id).val();
        	// tags
        	data['id'] = id;
        	var tagString = "";
        	$('#nevTagsList' + id).children().each(function(){
                 tagString += " " + $(this).text().replace('X',"").trim() + " ,"; 
          	})
        	data['tags'] = tagString;
        	// video is checked on server side upon submission
        	return data;
        },
    
    // initializes components
    	init: function(){
    		// gets all the submitted videos from the db
			$.ajax({
        		url: NEV_AJAX_URL,
        		type: 'POST',
        		data : {
        			action: 'nev_get_videos',    
        			nonce: NEV_NONCE				
        		},
        		dataType: 'json',  
        		success: function(response){
        			var videos = response.videos;
        			for (var i = 0; i < videos.length; i++){
        				if (videos[i].tags != ""){
							var tags = videos[i].tags.split(',');
							for (var j = 0; j < tags.length; j++){
								novel_engin_galleryBE.addToList(videos[i].id, tags[j].trim(), tags[j].trim().split(' ').join('_'));
							}
						}
        			}
				},
        		error: function(jqXHR, statusText, errorThrown){
        			console.log(statusText);
        		}
    		}) 
    		
    		//if search button is pressed
    		$("#nev_search_button").click(function(){
    			$('.nev-edit-video-row').css('display','none');	
    			var values = $("#nev_search_values").val();
     			$('tbody').find('.nev-deactivated-row').each(function(){
     				var id = $(this).attr('id');
     				$('#' + id).removeClass('nev-deactivated-row').addClass('nev-active-row');		
     			});
    			if (values == ""){$('.nev-active-row').css('display','');}
    			else {
    				values = values.toLowerCase().split(' ');
    				var rows = [];
    				var i = 0;
    				$('tbody').find('.nev-active-row').each(function(){
    					rows[i] = "";
    					$(this).find('td').each(function(){
    						rows[i] += $(this).text().toLowerCase().trim() + ' ';
    					})	
    					i++
     				})
     				var total = values.length;
     				var count, id;
     				for (var t = 0; t < rows.length; t++) {
     					count = 0;
     					id = rows[t].split(' ')[0];
     					for (var j = 0; j < total; j++) {
     						if (rows[t].match(values[j])){ count++ }
     					}
     					if (count == total){ 
     						$('#nev-view-row' + id).css('display',''); 
     						$('#nev-view-row' + id).removeClass('nev-deactivated-row').addClass('nev-active-row');
     					} else { 
     						$('#nev-view-row' + id).css('display','none'); 
     						$('#nev-view-row' + id).removeClass('nev-active-row').addClass('nev-deactivated-row');
     					}
     				}
     			}
    		});
    		
    		// if hovering over row
    		$("tr.nev-active-row").hover(
 				function () {
   			 		$(this).css("background","#E0F5FF");
   			 		$(this).css('cursor', 'pointer');
  				}, 
 			 	function () {
    				$(this).css("background","");
 			 	}
			)
			// if row is clicked
				.click(function(){
					var id = $(this).children(":first").text();
					novel_engin_galleryBE.load_tags(id);
          			$('.nev-edit-video-row').css('display','none');	
          			$('.nev-active-row').css('display','');	
            		$('video').each(function(){$(this).trigger('pause')})
          			$('#nev-view-row' + id).hide();	
          			$('#nev-edit-row' + id).show("slow");	
				})
				
			// show hidden div
  			$('.nevViewTags').click(function(event){
            	event.preventDefault();
            	var status = ('Hide Tags' == $(this).text());
            	var id = $(this).attr('id');
            	$(this).html(status ? 'Click to view all tags': 'Hide Tags' );
            	$('#nevDBtags' + id).css("display", status ? 'none': 'block');
            });
			
			// if button is pressed on viewing video
			$('.button-primary.nev-update-video').click(function(){
				var command = $(this).attr('id').split('-')[0];
				var id = $(this).attr('id').split('-')[1];
				switch(command){
				// if viewing video is cancelled
				 case 'cancel_edit':
				 	$('#nev-edit-row' + id).hide();	
          			$('#nev-view-row' + id).show("slow");	
				 	break;
				// if user decides to delete video
				 case 'delete_video':
				 	if (confirm('Are you sure you want to permanently delete the video?')) {
				 		$.ajax({
        					url: NEV_AJAX_URL,
        					type: 'POST',
        					data : {
        						action: 'nev_delete_video_row',    
        						nonce: NEV_NONCE,
        						id: id				
        					},
        					dataType: 'json',  
        					success: function(response){
								if (response.success){
									$('#nev-update-video').css('display','');
									$('#nev-update-video').css('color','green');
									$('#nev-view-row' + response.id).remove();	
          							$('#nev-edit-row' + response.id).remove();
									$('#nev-update-video').html("<strong>Your video has been deleted!</strong>");
									$( window ).scrollTop( 0 );
									setTimeout( function(){ 
										$('#nev-update-video').html("");
										$('#nev-update-video').css('display','none');
									 }, 6000 );
								}
								else{
									$('#nev-update-video').css('color','red');
									$('#nev-update-video').html("Your video failed to delete, why don't you try again? If this continues to occur please contact an administrator.");
									setTimeout(function(){ $('#nev-update-video').html(""); }, 6000);
								}
							},
        					error: function(jqXHR, statusText, errorThrown){
        						console.log(statusText);
        					}
        				})
        			   }	 
				 	break;
				 // if user updates the video with new data
				 case 'update_video':
				 	var data = novel_engin_galleryBE.get_form_data(id);
				 	$.ajax({
        				url: NEV_AJAX_URL,
        				type: 'POST',
        				data : {
        					action: 'nev_update_video',    
        					nonce: NEV_NONCE,
        					info: data				
        				},
        				dataType: 'json',  
        				success: function(response){
							if (response.success){
								$('#nev-update-video').css('display','');
								$('#nev-update-video').css('color','green');
								$('#nev-update-video').html("<strong>Your video has been updated!</strong>");
								$( window ).scrollTop( 0 );
								$('#nev-view-row' + response.id).css('display','');	
          						$('#nev-edit-row' + response.id).css('display','none');	
								novel_engin_galleryBE.update_row(response.data);
								setTimeout( function(){ 
									$('#nev-update-video').html("");
									$('#nev-update-video').css('display','none');
								 }, 6000 );
							}
							else{
								$('#nev-update-video').css('color','red');
								$('#nev-update-video').html("Your video failed to upload, why don't you try again? If this continues to occur please contact an administrator.");
								setTimeout(function(){ $('#nev-update-video').html(""); }, 6000);
							}
						},
        				error: function(jqXHR, statusText, errorThrown){
        					console.log(statusText);
        				}
    				}) 
				 	break;
				 default:
				 	break;	
			    }
			});				
		}
	}	
	
    $(document).ready(function(){
        novel_engin_galleryBE.init();
    });
    
})(jQuery);