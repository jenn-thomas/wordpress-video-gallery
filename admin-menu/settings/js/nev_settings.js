/**
 * Dashboard page settings for video gallery
 */

(function($){
     nev_settings = {
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
                		var html = "Select a tag to add to the delete list:</br>"
                		$('#nev_showTags').html(html);
						for (var i = 0; i < response.tags.length; i++){
							if (response.tags[i]['tag'] != ""){
								var link = $('<a>',{
   									text: response.tags[i]['tag'],
 									id: response.tags[i]['tag'],
    								href: '#',
    								class: 'tag'
								}).appendTo('#nev_showTags');
								if (i != response.tags.length-1) $('#nev_showTags').append(', ');
							}
						}
					}
					else { $('#nev_showTags').html("No tags exist! Why don't you add some in the above input box?");}
					$('a.tag').click(function(event){
            			event.preventDefault();
            			var tag = $(this).attr('id');
            			var tagList = $( '#nev_deleted_tags' ).val();
            			if (tagList !== "") tagList += ", " + tag;
            			else tagList = tag;
            			$( '#nev_deleted_tags' ).val(tagList);
            		});
                },
                error : function(jqXHR, statusText, errorThrown){
                	errorElem = '#nev_errors';
            		$(errorElem).html('<p>Error: ' + statusText + ': ' + errorThrown + '</p>');
            		$(errorElem).parent().show();
                }
            })
        },
    	
         /**
          * AJAX handler for calling nev_AJAX::check_for_ffmpeg() asychronously.
          * @param ffmpeg_path the full path to the ffmpeg program.
          */
        check_for_ffmpeg: function(ffmpeg_path){		
            $.ajax({
                url : NEV_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'nev_check_for_ffmpeg',
                    nonce: NEV_NONCE,
                    ffmpeg_path: ffmpeg_path
                },
                dataType : 'json',
                success : function(response){
                    if( response.success ){

                        $( '#nev_check_ffmpeg_results').html('Success! ffmpeg was successfully called using "' + response.output + '". This path has been saved and will be used to invoke ffmpeg.');

                        var ffmpeg_not_found = $( "#nev_ffmpeg_not_found" );

                        if( ffmpeg_not_found.length ){
                            ffmpeg_not_found.removeClass( 'error').addClass( 'updated').html( '<p>The provided path works! Refresh the page to see new settings.</p>' );
                        }
                    }else{
                    	errorElem = '#nev_errors';
            			$(errorElem).html('<p>Error: ' + 'Exit code: ' + response.status_code + '</p>');
            			$(errorElem).parent().show();
                    }
                },
                error : function(jqXHR, statusText, errorThrown){
                	errorElem = '#nev_errors';
            		$(errorElem).html('<p>Error: ' + statusText + ': ' + errorThrown + '</p>');
            		$(errorElem).parent().show();
                }
            })
        },

        /**
         * Enables HTML5Video conversion if available via the use
         * of a checkbox.
         * @param checkBoxElem - jQuery object representing a checkbox element
         */
        enableHTML5Video: function(checkBoxElem){
            var convertToHTML5 = checkBoxElem.is(':checked') ? 1 : 0;
            $.ajax({
                url	 : NEV_AJAX_URL,
                type : 'POST',
                data: {
                    action : 'nev_enableHTML5VideoAJAX',
                    nonce  : NEV_NONCE,
                    convertToHTML5 : convertToHTML5
                },
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                },
                error : function(jqXHR, statusText, errorThrown){
                    errorElem = '#nev_errors';
            		$(errorElem).html('<p>Error: ' + statusText + ': ' + errorThrown + '</p>');
            		$(errorElem).parent().show();
                }
            });
        },
        
        /**
         * Adds the tags that the user has inputted, separated by comma
         * @param tags - from add tags input box
         */
        add_tags: function(tags){
            $.ajax({
                url : NEV_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'nev_add_tags',
                    nonce: NEV_NONCE,
                    tags: tags
                },
                dataType : 'json',
                success : function(response){
                	var text = "";
                	$('#nev_added_tags').val("");
                	$( '#nev_returndeletetags').html('');
                	if (response.tags == 'none'){
                		$( '#nev_returnaddtags').html('No tags were inputed');
                	}else{
                		if (response.tags.length >= 1){
                			text = 'Success! The following tag(s) were added: <strong>' + response.tags.join(", ") + '</strong></br>';
                		}
                		if (response.tagsExist.length >= 1){
                			text += '<span style="color:black;">The following tag(s) already exist: <strong>' + response.tagsExist.join(", ") + '</strong></span>';
                		}
                		nev_settings.load_tags();
						$('#nev_returnaddtags').html(text);
					}
                },
                error : function(jqXHR, statusText, errorThrown){
                	errorElem = '#nev_errors';
            		$(errorElem).html('<p>Error: ' + statusText + ': ' + errorThrown + '</p>');
            		$(errorElem).parent().show();
                }
            });
        },
        
        /**
         * Deletes the tags that the user has inputted, separated by comma
         * @param tags - from delete tags input box
         */
        delete_tags: function(tags){
            $.ajax({
                url : NEV_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'nev_delete_tags',
                    nonce: NEV_NONCE,
                    tags: tags
                },
                dataType : 'json',
                success : function(response){
                	var text = "";
                	$('#nev_deleted_tags').val("");
                	$( '#nev_returnaddtags').html('');
                	if (response.tags == 'none'){
                		$( '#nev_returndeletetags').html('No tags were inputed');
                	}else{
                		if (response.tags.length >= 1 && response.tags != null){
                			text = 'Success! The following tag(s) were deleted and removed from all videos:<strong> ' + response.tags.join(", ") + '</strong></br>';
                		}
                		if (response.notags.length >= 1 && response.tags != null){
                			text += '<span style="color:black;">The following tag(s) do not exist and were not removed:<strong> ' + response.notags.join(", ") + '</strong></span>';
                		}
                		nev_settings.load_tags();
						$( '#nev_returndeletetags').html(text);
					}
                },
                error : function(jqXHR, statusText, errorThrown){
                	errorElem = '#nev_errors';
            		$(errorElem).html('<p>Error: ' + statusText + ': ' + errorThrown + '</p>');
            		$(errorElem).parent().show();
                }
            })
        },
        
        /**
         * initializes upon document ready
         */
        init: function(){
        	var click = 0;
            var self = this;
            $( '#nev_check_ffmpeg_path' ).click(function(){
                var ffmpeg_path = $( '#nev_ffmpeg_path' ).val();
                self.check_for_ffmpeg( ffmpeg_path );
            });

            $('.nev_enableHTML5Video').click(function(){
                self.enableHTML5Video( $(this) );
            });
            
            $('#nev_add_tags').click(function(){
                var tags = $( '#nev_added_tags' ).val();
                self.add_tags( tags );
            });
            
            $('#nev_delete_tags').click(function(){
                var tags = $( '#nev_deleted_tags' ).val();
                self.delete_tags( tags );
            });
            
            $('#nev_viewTags').click(function(event){
            	event.preventDefault();
            	click = click ? 0 : 1;
            	$(this).html(click ? 'Hide Tags' : 'Click to view all tags');
            	$('#nev_showTags').css("visibility", click ? 'visible': 'hidden');
            });
        }
    }

    $(document).ready(function(){
       nev_settings.load_tags();
       nev_settings.init();
    });

})(jQuery);