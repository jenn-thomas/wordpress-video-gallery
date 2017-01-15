/*
* javascript for add video page on dashboard
*/

(function($){
    nev_addvideo = {    
 		/**
        * Loads information about the current form and if any and uploads to form
        */
        set_form_data: function(){
        	$.ajax({
        		url: NEV_AJAX_URL,
        		type: 'POST',
        		data : {
        			action: 'nev_get_form_data',
        			nonce: NEV_NONCE,
        		},
        		dataType: 'json',  
        		success: function(response){
        			var data = response.data;
					if (data['converted'] == false){
						if(data['videofile'] != ""){
							if (response.bool){
								$('#nevUploadVideo').show();
							}
							else {
								$('#nevVideoConverted').show();
							}
						}
						else{
							$('#nevUploadVideo').show();
						}
					}
					else {
						$('#nevVideoConverted').show();
					}
					if (data['tags'] != ""){
						var tags = data['tags'].split(',');
						for (var i = 0; i < tags.length; i++){
							nev_addvideo.addToList(tags[i].trim(),tags[i].trim().split(' ').join('_'));
						}
					}	
        		},
        		error: function(jqXHR, statusText, errorThrown){
        		
        		}
        	})
        },
        
 		/**
        * Loads currently stored tags in database and initializes them in the DOM
        */
    	load_tags: function(){
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
                		$('#nevCurrentTags').html(html);
						for (var i = 0; i < response.tags.length; i++){
							if (response.tags[i]['tag'] != ""){
								var link = $('<a>',{
   									text: response.tags[i]['tag'],
 									id: response.tags[i]['tag'].split(' ').join('_'),
    								href: '#',
    								class: 'tag'
								}).appendTo('#nevCurrentTags');
								if (i != response.tags.length-1) $('#nevCurrentTags').append(', ');
							}
						}
					}
					else { $('#nevCurrentTags').html("No tags exist! Why don't you add some?");}
					$('a.tag').click(function(event){
    					event.preventDefault();
    					var tag = $(this).text();
    					var id = $(this).attr("id");
    					nev_addvideo.addToList(tag,id)
        			})
                },
                error : function(jqXHR, statusText, errorThrown){
                	errorElem = '#nev_errors';
            		$(errorElem).html('<p>Error: ' + statusText + ': ' + errorThrown + '</p>');
            		$(errorElem).parent().show();
                }
            })
        },
        
 		/**
        * grabs all the data from the form
        */          
        get_form_data: function(){
        	var data = {};
        	// title
        	data['title'] = $('#nev-video-title').val();
        	// description
        	data['description'] = $('#nevDescription').val();
        	// tags
        	var tagString = "";
        	$('#nevTagsList').children().each(function(){
                 tagString += " " + $(this).text().replace('X','').trim() + " ,";  
          	})
        	data['tags'] = tagString;
        	// video is checked on server side upon submission
        	return data;
        },
        
 		/**
        * adds a selected tag to the tag list
        * @param tag - the selected tag name to be added
        * @param id - the selected tag id to be added
        */        
        addToList: function(tag,id){
        		var idArray = [];
        		$('#nevTagsList').children().each(function(){
                 	idArray.push($(this).attr('id'));  
          		})
          		// check if tag already exists
          		if (idArray.indexOf(tag) == -1 && tag != ""){
      				var html = "<span id='" + id + "'><a id='" + id + "' href='#' class='nev-close-btn'>X</a>    " 
      				+ tag + "   </span>";
					$('#nevTagsList').append(html);
					nev_addvideo.tagClick();
  				}
        },
        
 		/**
        * initializes the added tag class and lets user delete tags
        */
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
  				console.log($(this).attr("id"));
  				var name = $(this).attr("id");
  				var id = '#' + name;
  				console.log(typeof id)
  				$('span').remove(id);
  			})   
        },

        /**
         * Initializes HTML5 filedrop for the media component
         * @param component - The video component
         * @see $plupload_init array in /wp-admin/includes/media.php
         */
        initFileDrop: function( component ){
            var self = this;
            var browse_id = 'nev-video-browse';
            var prev_onbeforeunload = window.onbeforeunload;

            var videoFilters;
            if( nev_addvideoJS.NEV_VIDEO_HTML5 ){
                videoFilters = [
                    {title : "MOV files", extensions : "mov, MOV"},
                    {title : "AVI files", extensions : "avi, AVI"},
                    {title : "MP4 files", extensions : "mp4, MP4"},
                    {title : "M4V files", extensions : "m4v, M4V"}
                ]
            }else{
                videoFilters = [
                    {title : "MP4 files", extensions : "mp4, MP4"}
                ]
            }

            var uploader = new plupload.Uploader({
                runtimes      : 'html5,silverlight,flash,html4',
                chunk_size    : '1mb',
                browse_button : browse_id, // you can pass in id...
                url           : NEV_AJAX_URL,
                flash_swf_url : nev_globals.UPLOAD_SWF_URL,
                silverlight_xap_url : nev_globals.UPLOAD_SILVERLIGHT_URL,
                dragdrop        : true,
                drop_element    : component.attr('id'),
                file_data_name  : 'nev_videoUpload',
                multi_selection : false,
                multipart       : true,
                urlstream_upload: true,
                multipart_params: {
                    action  : 'nev_videoUploadAJAX',
                    nonce   : NEV_NONCE
                },
                max_file_size : '2gb',
                filters : videoFilters,
                init: {

                    FilesAdded: function(up, files) {
                        if(files.length > 1){
                            alert('Please upload one file at a time.')
                            while (up.files.length > 0) {
                                up.removeFile(up.files[0]);
                            }
                        }else{
                            up.start();
                            $('.nev_video').removeClass('hover');
                            $('#nev_videoDropZone').hide();
                            $('#neVideoProgBarContainer').show();

                            //Add a dialogue in case window is closed
                            window.onbeforeunload = function (e) {
                                e = e || window.event;
                                if (e) {
                                    e.returnValue = 'Warning: A file is being uploaded. If you interrupt file upload you will have to restart the upload.';
                                }
                                return 'Warning: A file is being uploaded. If you interrupt file upload you will have to restart the upload.';
                            };
                        }
                    },

                    UploadProgress: function(up, file) {
                        $('#neVideoProgressBar').css('width', file.percent + '%');
                        $('#nevVideoProgressMsg').html('<p><img src="' + NEV_IMAGE_PATH + '/loading.gif" /> Uploading Video… ' + file.percent + '%, ' + parseInt(up.total.bytesPerSec/1024) +'Kb/s</p>');
                    },

                    Error: function(up, err) {
                        var filetext = '';
                        if( err.file.name ){
                            filetext =  'File name: "' + err.file.name + '"';
                        }
                        var errorText = err.message + ' ' + filetext;
                        $( '#nev_component_errors' ).show().append('<p>Error: ' + errorText + '</p>');
            			$( 'html, body' ).animate({ scrollTop: 0 }, 0);
                    },

                    FileUploaded: function(up, files, response) {
                        if(response){
                        	$('#nevBeingConverted').css('display','block');
                            $('#neVideoProgBarContainer').hide();
							$('#nevUploadVideo').css('display','none');
							$('#nevBeingConverted').css('display','block');
                            self.checkVideoStatus();
                        }
                        window.onbeforeunload = prev_onbeforeunload;
                    }
                }
            });
            uploader.init();
        }, //end initFileDrop

        /**
         * Makes an AJAX call every 5 seconds to check on the encoding status of an uploaded video.
         */
        checkVideoStatus: function(){
            var self = this;
            var messageElem = $('#nevVideoProgressMsg');
            $.ajax({
                url	 : NEV_AJAX_URL,
                type : 'POST',
                data: {
                    action : 'nev_checkVideoStatusAJAX',
                    nonce  : NEV_NONCE,
                },
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                    if( response.converted ){
                    	$('#nevBeingConverted').css('display','none');
                    	$('#nevVideoConverted').css('display','block');
                    }else{
						$('#nevBeingConverted').css('display','block');
                        setTimeout( function(){ self.checkVideoStatus() }, 5000 );
                    }
                },
                error : function(jqXHR, statusText, errorThrown){
                    messageElem.html('<p class="nev-comp-errors" style="display: inherit;">There was an error with uploading your video! <a href="#" onclick="window.location.reload( true );">Refresh</a> the page and try to re-upload your video again.</p>')
                    	var errorText = errorThrown;
                        $( '#nev_component_errors' ).show().append('<p>Error: ' + errorText + '</p>');
            			$( 'html, body' ).animate({ scrollTop: 0 }, 0);
                }
            });
        },
        
        // refreshes the page after the video has been uploaded
        refreshPage: function(){
        	location.reload()
        },

        /**
         * Statically initializes all components on document.ready
         */
        init: function(){
            var self = this;
			var click = 0;
			// when user drags video file over div
  			$('.nev_video').on('dragover', function() {
   				$(this).addClass('hover');
  			});
  			// when user leaves dragover div
  			$('.nev_video').on('dragleave', function() {
    			$(this).removeClass('hover');
  			});

			// add tags from input #add
  			$('#nevAdd').click(function(){
  				var tag = $('#nevAddedTags').val();
  				$('#nevAddedTags').val("");
  				if (tag != ""){
  					tag = tag.split(',');
  					for (var i=0; i < tag.length; i++) nev_addvideo.addToList(tag[i].trim(),tag[i].trim().split(' ').join('_'));
  				}
  			});
  			
  			/**
        	* Deletes the uploaded video and allows user to upload a new one
        	*/
  			$('#nev_delete_video').click(function(){
  				if (confirm('Are you sure you want to delete the video?')) {
  					$.ajax({
        				url: NEV_AJAX_URL,
        				type: 'POST',
        				data : {
        					action: 'nev_delete_video',
        					nonce: NEV_NONCE,
        				},
        				dataType: 'json',  
        				success: function(response){
        					$('#nevVideoProgressMsg').html('');
                            $('#nev_videoDropZone').show();
                            $('#neVideoProgBarContainer').hide();
        					$('#nevVideoConverted').hide();
							$('#nevUploadVideo').show();
        				},
        				error: function(jqXHR, statusText, errorThrown){
        					console.log(statusText + ' ' + errorThrown);
        				}
        			})
        		} else {
    				// Do nothing!
				}
			});
			
		/**
        * will send data to db to be stored for the current video by the user pressing
        * the submit or save form button
        */
			$('.button-primary.nev-send-data').click(function(){
				var data = nev_addvideo.get_form_data();
				$('#nevAJAXalerts').html('');
				if ((data['title'] != "" && this.id == "submit_form") || this.id == "save_form"){
					nev_settings.add_tags( data.tags );
					$('#nevNoTitle').css('display','none');
					$.ajax({
        				url: NEV_AJAX_URL,
        				type: 'POST',
        				data : {
        					action: 'nev_' + this.id,
        					nonce: NEV_NONCE,
        					info: data,
        				},
        				dataType: 'json',  
        				success: function(response){
        				console.log(response);
        					if ( response.action == "save_form"){
        						$('#nevAJAXalerts').html('<p>Successfully saved data!</p>');
        						$( window ).scrollTop( 0 );
        					}
        					else {
        						if (response.success){
        							$('#nevAJAXalerts').html('<p>Successfully submitted the form! It will now appear in the video gallery if you wish to make further edits to it. Refreshing the page...</p>');
        							$( window ).scrollTop( 0 );
        							setTimeout( function(){ self.refreshPage() }, 6000 );
        							
        						} else{
        							$('#nevAJAXalerts').html('<p>' + response.message + '</p>');
        							$( window ).scrollTop( 0 );
        						}
        					}
        				},
        				error: function(jqXHR, statusText, errorThrown){
        					console.log(statusText + ' ' + errorThrown);
        				}
        			})
        		} else {
        			$('#nevNoTitle').css('display','inline-block');
        			$( window ).scrollTop( 0 );
        		}
			});
			
 		/**
        * Opens a new tab with the uploaded video to view
        */
			$('#nev_view_video').click(function(){
        		$.ajax({
        			url: NEV_AJAX_URL,
        			type: 'POST',
        			data : {
        				action: 'nev_get_form_data',
        				nonce: NEV_NONCE,
        			},
        			dataType: 'json',  
        			success: function(response){
        				window.open(response.data["videofile"].split(',')[1],'_newtab');
        			},
        			error: function(jqXHR, statusText, errorThrown){
        				console.log(statusText + ' ' + errorThrown);
        			}
        		})
        	});	
  			
 		/**
        * Shows hidden div that contains all tags stored in db
        */
  			$('#nevClickTags').click(function(event){
            	event.preventDefault();
            	click = click ? 0 : 1;
            	$(this).html(click ? 'Hide Tags' : 'Click to view all tags');
            	$('#nevCurrentTags').css("display", click ? 'block': 'none');
            });
            
            self.initFileDrop( $('.nev_video') );

        }
    }

    $(document).ready(function(){
    	nev_addvideo.load_tags();
    	nev_addvideo.set_form_data();
        nev_addvideo.init();
    });
})(jQuery);