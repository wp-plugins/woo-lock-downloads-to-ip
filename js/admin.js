jQuery( function(){


	var lock_type = jQuery( '#wooiplock_lock_type' ).val();

	if( lock_type!='countries' ){

		//jQuery( '#ip_range_row' ).next().hide();

	}

	jQuery( '#wooiplock_lock_type' ).change( function(){

		var value = jQuery(this).val();

		if( value == 'ip_range' ){
		//	jQuery( '#ip_range_row' ).next().hide();
			jQuery( '#ip_range_row' ).slideDown();


		}else if( value == 'countries' ){

			jQuery( '#ip_range_row' ).hide();
			//jQuery( '#ip_range_row' ).next().slideDown();

		}else{

			jQuery( '#ip_range_row' ).hide();
			//jQuery( '#ip_range_row' ).next().hide();

		}
	})


})