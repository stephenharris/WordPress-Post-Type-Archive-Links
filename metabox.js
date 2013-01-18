jQuery( document ).ready( function($) {
	$( '#submit-post-type-archives' ).click( function( event ) {
		event.preventDefault();

		/* Get checked boxes */
		var postTypes = [];
		$( '#' + hptal_obj.metabox_id + ' li :checked' ).each( function() {
			postTypes.push( $( this ).val() );
		} );

		/* Send checked post types with our action, and nonce */
		$.post( hptal_obj.ajaxurl, {
				action: hptal_obj.nonce,
				posttypearchive_nonce: hptal_obj.nonce,
				post_types: postTypes
			},

			/* AJAX returns html to add to the menu */
			function( response ) {
				$( '#menu-to-edit' ).append( response );
			}
		);
	} );
} );