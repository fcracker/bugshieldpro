
$(function(){
	
	$("#qty").change(p3_qty_change);
	
	
});

function p3_qty_change(){
	var qty = $("#qty").val();
	
	if(qty<3) {
		$("#product_image_description").attr("src","images/order.png");
	} else {
		$("#product_image_description").attr("src","p3/images/order.png");
	}
}