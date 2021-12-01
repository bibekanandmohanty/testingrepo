function downloadSVG(){
	var root_url = ink_pd_vars.siteurl;
	var order_id = jQuery("#post_ID").val();
	var url = root_url+'api/v1/order-download?is_download_store=true&order_id='+order_id;
	window.open(url);
}