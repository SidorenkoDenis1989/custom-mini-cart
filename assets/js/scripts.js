jQuery(document).ready(function($){

	$('body').on('change', '.widget_shopping_cart .product-price input[type="number"]', function(){

        var item_hash = $( this ).attr( 'name' ).replace(/cart\[([\w]+)\]\[qty\]/g, "$1");

        var item_quantity = $( this ).val();

        var currentVal = parseFloat(item_quantity);

       	$.ajax({
	        type: 'POST',
	        dataType: 'json',
	        url: cartQtyAjax.url,/*cartQtyAjax.home_url + '?wc-ajax=add_to_cart',*/
	        data: {                
	        	action: 'qty_cart',
                hash: item_hash,
                /*product_id: item_hash,*/
                quantity: currentVal,  
            },
            beforeSend: function (data) {
	        	$( '.widget_shopping_cart_content' ).append('<div class="filter-overlay"><div class="loading"><div class="lds-ring"><div></div><div></div><div></div><div></div></div></div></div>');
	    	},
	        success: function (data) {
	        	/*$( document.body ).trigger( 'wc_fragment_refresh' );*/
	        	$('.categories-cart-items').each(function(){
	        		$(this).replaceWith(data.caterogories)
	        	});
	        	$('.hfe-menu-cart__toggle .elementor-button-text').html(data.cart_total);
	        	$('.hfe-menu-cart__toggle .elementor-button-icon').attr('data-counter', data.cart_total_items);
	        	$('div.widget_shopping_cart_content').html(data.minicart);
	        	$('.filter-overlay').detach();
	    	}
	    }); 
	});


	jQuery('body').on('DOMSubtreeModified', '.widget_shopping_cart_content', function(){
		jQuery('.woocommerce-mini-cart').scrollTop(jQuery('.woocommerce-mini-cart')[0].scrollHeight);
	});
});