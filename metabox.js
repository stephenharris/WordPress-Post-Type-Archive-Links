/**
 * Handle the custom post type nav menu meta box
 */
jQuery( document ).ready( function($) {
     $( '#submit-post-type-archives' ).click( function( event ) {
		event.preventDefault();
		
		var $hptal_list_items = $( '#' + hptal_obj.metabox_list_id + ' li :checked' );
		var $hptal_submit = $( 'input#submit-post-type-archives' );

		// Get checked boxes
		var postTypes = [];
		$hptal_list_items.each( function() {
			postTypes.push( $( this ).val() );
		} );
		
		// Show spinner
		$( '#' + hptal_obj.metabox_id ).find('.spinner').show();
		
		// Disable button
		$hptal_submit.prop( 'disabled', true );

		// Send checked post types with our action, and nonce
		$.post( hptal_obj.ajaxurl, {
				action: hptal_obj.action,
				posttypearchive_nonce: hptal_obj.nonce,
				post_types: postTypes,
				nonce: hptal_obj.nonce
			},

			// AJAX returns html to add to the menu, hide spinner, remove checks
			function( response ) {
				$( '#menu-to-edit' ).append( response );
				$( '#' + hptal_obj.metabox_id ).find('.spinner').hide();
				$hptal_list_items.prop("checked", false);
				$hptal_submit.prop( 'disabled', false );
			}
		);
	} );
} );
