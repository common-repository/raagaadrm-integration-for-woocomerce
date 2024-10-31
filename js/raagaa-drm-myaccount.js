jQuery(document).ready(function($) {
  



	jQuery(".view-book-drm").click(function(){
		
		  rid= $(this).attr('data-rid');
          email= $(this).attr('data-email');		
    $.ajax({
        url: ajax_object.ajax_url,
        data: {
			_ajax_nonce: ajax_object.nonce,
            'action':'woo_rgdrm_ebook_request',
            'email' : email,
			'r_id':rid
          
        },
        success:function(data) {
			//window.open(data, '_blank');
                 //  window.open(data);
				 // if ( /^((?!chrome|android).)*safari/i.test(navigator.userAgent)) {window.location.assign(data)}

window.location.assign(data)
           console.log(data);
        },
        error: function(errorThrown){
            console.log(errorThrown);
        }
    });

	});
	
});