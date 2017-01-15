/* 
* javascript for front end video gallery
*/

(function($){
    nev_shortcode = {
	/* 
	* closes lightbox
	*/
    	close_box: function(id){
    		$('video').trigger('pause');
			$('.nevbackdrop, #popup' + id).css('display', 'none');
		},
		
	/* 
	* generates a list of videos for matches to the search terms
	*/
		get_videos: function(terms){
			$.ajax({
        		url: NEV_AJAX_URL,
        		type: 'GET',
        		data : {
        			action: 'nev_search_videos',    
        			terms: terms				
        		},
        		dataType: 'json',  
        		success: function(response){
         			var videos = response.videos;
         			var allVideos = [];
         			if (videos.length < 1){$('.nev-no-match').show();}
         			else{$('.nev-no-match').hide();}
					$('#nev-container').children().each(function(){
    					allVideos.push($(this).attr('id').split("-")[1]);
     				})
     				if (allVideos.length == videos.length){
     					$('.nevImageDiv').show();
     					return;
     				}
        			for (var i=0; i < allVideos.length; i++){
        				if (videos.indexOf(allVideos[i]) > -1){$('#div-' + allVideos[i]).show()}
        				else{$('#div-' + allVideos[i]).hide();}
        			}
				},
        		error: function(jqXHR, statusText, errorThrown){
        			console.log(statusText);
        		}
    		}) 
		},

	/* 
	* initializes on document.ready
	*/
    	init: function(){
			// user clicks search, grabs text from input
    		$('#nev-FE-form').submit(function(event){
    			event.preventDefault();
				var terms = $('#search-nev-FE').val();
				nev_shortcode.get_videos(terms);
			})
			
			$(window).resize(function(){
    			var top = $(window).height()/2 - $('.nevbox').height()/2;
    			var left = $(window).width()/2 - $('.nevbox').width()/2;
    			$('.nevbox').css('top',top);
    			$('.nevbox').css('left',left);
			});
				
			// user clicks on thumbnail and generates light box
    		$(".nev-video-link").click(function(event){
    			event.preventDefault();
    			var id = $(this).attr('id');
    			$('.nevbackdrop, #popup' + id).animate({'opacity':'.7'}, 300, 'linear');
				$('.nevbox').animate({'opacity':'1.00'}, 300, 'linear');
				$('.nevbackdrop, #popup' + id).css('display', 'block');
				window.dispatchEvent(new Event('resize'));
				$('.nevclose').click(function(){
					nev_shortcode.close_box(id);
				});
 
				$('.nevbackdrop').click(function(){
					nev_shortcode.close_box(id);
				});
    		})
    	}
    }	
    	
    $(document).ready(function(){
    	nev_shortcode.init();
    });
})(jQuery) ;