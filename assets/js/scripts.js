function bpSetUpdatedPrice(l){

	var dataString = "action=pbs-get-price-for-length&pid=" + parseInt(pbs_post_id) + "&l=" + parseFloat(l);

	jQuery.ajax({
		type:			"POST",
		URL:			"/wp-admin/admin-ajax.php",
		data:			dataString,
		success:	function(result){
			console.log(result);
		},
		error: function (e) {
			alert(e.message);
    }
	})
}

jQuery(document).ready(function($){



	$('#add_discount').click(function(){
		var discounts = $('div.bulkdiscount').length;
		var button = $(this).detach();
	
		var element = '';
		element += '<div class="options_group bulkdiscount">';
		element += '<p class="form-field _bulkdiscount ">';
		element += '<label for="_bulkdiscount['+discounts+']"> Min/Max</label>';
		element += '<input type="number" class="bulkdiscount_min_max bulkdiscount_min" size="6" name="_bulkdiscount['+discounts+'][min]" value="" step="1">';
		element += '<input type="number" class="bulkdiscount_min_max bulkdiscount_max" size="6" name="_bulkdiscount['+discounts+'][max]" value="" step="1">';
		element += '<span class="description">Mindest und Maximalstückzahl für den Rabatt. 0 Wenn nichts gesetzt werden soll.</span>';
		element += '</p>';
		element += '<p class="form-field _bulkdiscount">';
		element += '<label for="_bulkdiscount['+discounts+'][price]"> Der Preis</label>';
		element += '<input type="number" class="short _bulkdiscount_price" name="_bulkdiscount['+discounts+'][price]" value="" step="any" min="0">';
		element += '</p>';
		element += '</div>';
		
		
		$('#bulkdiscount_active_tab').append( element );
		$('#bulkdiscount_active_tab').append( button );
	})



})