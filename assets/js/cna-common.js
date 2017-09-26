;jQuery(document).ready(function ($) {

	/**
	 * Affiliate Add / Remove.
	 */
	var CNA_Affiliate = {

		init : function() {
			// this.clone();
			this.add();
			this.remove();
			this.clearable();
			this.reindex();
			this.sortable();
		},
		clone : function( row, key ) {

			var source = $( row );

			// Clone the row.
			var clone = source.clone( true );

			// Remove Chosen elements
			// clone.find( '.search-choice' ).remove();
			// clone.find( '.chosen-container' ).remove();

			// Look through the cloned row and find each select
			// @link https://stackoverflow.com/a/38385166/5351316
			clone.find( 'select.cn-enhanced-select' ).each( function() {

				// Because chosen is a bitch with deep cloned events
				// We need to make another internal clone with no events
				var select = $( this ).clone().off();

				// Now we can delete the original select box
				// AND delete the chosen elements and events
				// THEN add the new raw select box back to the TD
				var td = $( this ).closest( 'td' );
				td.empty().append( $( select ).show() );

				// Finally, we can initialize this new chosen select
				// td.find( '.cn-enhanced-select' ).chosen({ width: '100%' });
			});

			// Change the id and name attributes to the supplied key.
			clone.find( 'input, select' ).each( function() {

				var name = $( this ).attr( 'name' );

				name = name.replace( /(\[\d+\])/, '[' + parseInt( key ) + ']' );

				// Update name and id attributes.
				$( this )
					.attr( 'name', name )
					.attr( 'id', name );

				// Reset input/select values.
				$( this ).not( 'select' ).val( '' );
				$( this ).not( 'input' ).find( 'option:selected' ).removeAttr( 'selected' );
			});


			var $inp = clone.find( 'input:text' ),
			    $cle = clone.find( '.cnd-clearable__clear' );

			// $inp.on( 'input', function() {
				$cle.toggle( !!this.value );
			// });

			// $cle.on( 'touchstart click', function( e ) {
			// 	e.preventDefault();
			// 	$inp.val( '' ).trigger( 'input' );
			// });


			// Increment the data-key attribute.
			clone.attr( 'data-key', key );

			// Unhide the cloned <tr>.
			clone.toggle();

			return clone;
		},
		add : function() {

			$( '.cna-add-affiliate' ).on( 'click', function() {

				var table = $( '#cn-affiliations' );

				var row = $( this ).closest( 'tr' );

				// Increment the row counter.
				var data = table.cndCount( 1 ).data();

				var clone = CNA_Affiliate.clone( row, data.count );

				// Insert the cloned row after the current row.
				row.after( clone );

				// Setup chosen fields again if they exist
				// Initialize this new Chosen.
				row.next().find( '.cn-enhanced-select' ).chosen();

				// After adding a row, the row input need to be reindexed.
				CNA_Affiliate.reindex();

				// if ( 1 < data.count ) {
				//
				// 	$( '.cnd-remove-affiliate' ).removeClass( 'disabled' );
				// }
			});
		},
		remove : function() {

			$( '.cna-remove-affiliate' ).on( 'click', function() {

				var table = $( '#cn-affiliations' );

				// Get table data.
				var data = table.data();

				if ( 1 < data.count ) {

					var row = $( this ).closest( 'tr' );

					// Decrement the period counter for the day.
					table.cndCount( -1 );

					row.remove();

					// After removing a row, the row inputs need to be reindexed.
					CNA_Affiliate.reindex();

				}

				// if ( 1 >= table.data( 'count' ) ) {
				//
				// 	$( '.cnd-remove-affiliate' ).addClass( 'disabled' );
				// }

			});
		},
		reindex : function() {

			// Process each row.
			$( '#cn-affiliations tr' ).each( function( i, el ) {

				var row = $( el );

				// In each row find the inputs.
				row.find( 'input, select' ).not( 'input.chosen-search-input').each( function() {

					// Grab the name of the current row being processed.
					var name = $( this ).attr( 'name' );

					// Replace the name with the current day and index.
					name = name.replace( /(\[\d+\])/, '[' + parseInt( i ) + ']' );

					// Update both the name and id attributes with the new day and index.
					$( this ).attr( 'name', name ).attr( 'id', name );
				});

				row.attr( 'data-key', i );
			});

			var table = $( '#cn-affiliations' );

			if ( 1 >= table.data( 'count' ) ) {

				$( '.cna-remove-affiliate' ).addClass( 'disabled' );

			} else {

				$( '.cna-remove-affiliate' ).removeClass( 'disabled' );
			}
		},
		clearable : function() {

			/**
			 * Clearable text inputs
			 * @link https://stackoverflow.com/a/6258628/5351316
			 */

			$( '.cnd-clearable' ).each( function() {

				var object = $( this );

				var input = object.find( 'input:text' ),
				    clear = object.find( '.cnd-clearable__clear' );

				clear.toggle( !!input.val() );

				input.on( 'input', function() {

					var input = $( this );
					var clear = input.next( '.cnd-clearable__clear' );

					// console.log( input.val() );
					clear.toggle( !!input.val() );
				});

				clear.on( 'touchstart click', function( e ) {
					e.preventDefault();

					var input = $( this ).prev( 'input:text' );

					// console.log( input.val() );
					input.val( '' ).trigger( 'input' );
				});

			});

		},
		sortable : function() {

			var fixHelperModified = function( e, tr ) {

				    var $originals = tr.children();
				    var $helper = tr.clone();

				    $helper.children().each( function( index ) {
					    $( this ).width( $originals.eq( index ).width() )
				    });

				    return $helper;
			    },
			    updateIndex       = function( e, ui ) {

				    // $( 'td.index', ui.item.parent() ).each( function( i ) {
					 //    $( this ).html( i + 1 );
				    // } );

				    // After moving a row, the rows need to be reindexed.
				    CNA_Affiliate.reindex();
			    };

			$( '#cn-affiliations tbody' ).sortable({
				helper: fixHelperModified,
				containment: 'parent',
				cursor: 'move',
				handle: 'i.fa.fa-sort',
				placeholder: 'widget-placeholder',
				stop:   updateIndex
			}).disableSelection();
		}
	};

	CNA_Affiliate.init();

	// Counter Functions Credit:
	// http://stackoverflow.com/a/5656660
	$.fn.cndCount = function( val ) {

		return this.each( function() {

			var data = $( this ).data();

			if ( ! ( 'count' in data ) ) {

				data['count'] = 0;
			}

			data['count'] += val;
		});
	};

});
