// jQuery("form.variations_form").hide();		
jQuery('.variations tr').each(function () {
	var label = jQuery(this).find('.label');
	var labeltxt = label.html();
	if (labeltxt.toLowerCase().indexOf(">xe_size<")) {
		jQuery("label[for='pa_xe_size']").text("Size");
	}
	if (labeltxt.toLowerCase().indexOf(">xe_color<")) {
		jQuery("label[for='pa_xe_color']").text("Color");
	}
});
jQuery(document).ready(function(){ 	
if(jQuery("input[name=add-to-cart]").length > 0)
	var productid = jQuery("input[name=add-to-cart]").val();	
else 
	var productid = jQuery("button[name=add-to-cart]").val();	
var root_url = ink_pd_vars.siteurl;	
jQuery(".product_meta").before('<a id="customize" href="javascript:void(0)" class="customize-btn button alt disabled" >Customize</a>');
var iframe_links = '<iframe id="css_links" src="'+root_url+'/xetool/xeiframe.html" style="display:none;"></iframe><div id="image_data"></div><div id="image_data"></div';
jQuery(".product_meta").after(iframe_links);
var url = root_url+'/xetool/api/index.php';	
var data2 = {reqmethod:'isCustomizable', pid:productid}; 	
var data3 = {reqmethod:'isDisabledAddToCart', pid:productid}; 
var data = {reqmethod:'getPrintMethodByProduct', pid:productid}; 	
var data1 = {reqmethod:'getLatestRevision'}; 
	jQuery.ajax({
		type        : 'POST', //Method type
		url         : url, //Your form processing file url
		data        : data2, //Forms name
		dataType    : 'html',
		success     : function(res) {
			if(res==1){				
				jQuery.ajax({
					type        : 'POST', //Method type
					url         : url, //Your form processing file url
					data        : data3, //Forms name
					dataType    : 'html',
					success     : function(result) {	
						if(result==1){
							jQuery("form.variations_form").hide();
						} else {
							jQuery("form.variations_form").show();
						}
					}
				});
				jQuery.ajax({ //Process the form using $.ajax()
					type        : 'POST', //Method type
					url         : url, //Your form processing file url
					data        : data, //Forms name
					dataType    : 'html',
					success     : function(res) {
						var jsonObj = jQuery.parseJSON(res);
						if(jsonObj && !jsonObj.hasOwnProperty('status'))
						{ 
							jQuery.ajax({
								type        : 'POST', //Method type
								url         : url, //Your form processing file url
								data        : data1, //Forms name
								dataType    : 'html',
								success     : function(result) {									
									var print_method = '';
									var refid = 0;
									for(var i=0;i<jsonObj.length;i++)
									{
										if(print_method=='')
											print_method += jsonObj[i]['print_method_id'];
										else
											print_method += ','+jsonObj[i]['print_method_id'];
										
										if(typeof(jsonObj[i]['refid']) != "undefined" && jsonObj[i]['refid'] !== null)								
											refid = jsonObj[i]['refid'];
									}
									var rvn_no = result;
									var qty = jQuery("input[name=quantity]").val();
									var screenWidth = (window.innerWidth > 0) ? window.innerWidth : screen.width;
									if(qty == null)
										qty = 1;
									var url = "";
									if(screenWidth < 1024){
										url = root_url+"/xetool/index.html?id="+productid+"&ptid="+refid+"&pt="+print_method+"&rvn="+rvn_no+"&customer=0&qty="+qty;
									}else{
										url = root_url+"/product-designer?id="+productid+"&ptid="+refid+"&pt="+print_method+"&rvn="+rvn_no+"&customer=0&qty="+qty;
									}
									jQuery("#customize").attr('href',url);
									// Read property of localsettings.js
									jQuery.getScript(root_url+'/xetool/localsettings.js', function()
									{
										var api_key = RIAXEAPP.localSettings.api_key;
										var dataProduct = {reqmethod:'getSimpleProductClient', id:productid,apikey:api_key,confId:productid};
										var purl = root_url+'/xetool/api/index.php';	
										jQuery.ajax({
											type        : 'GET', //Method type
											url         : purl, //Your form processing file url
											data        : dataProduct, //Forms name
											dataType    : 'html',
											success     : function(data) {
												var productDetails = jQuery.parseJSON(data);
												sessionStorage.removeItem('productDetails');
												sessionStorage.setItem('productDetails', JSON.stringify(productDetails));
												var retrievedObject = sessionStorage.getItem('productDetails');
												var image = '<div id="image_side" style="display:none">';
												for(var i =0; i< productDetails.sides.length;i++)
												{
													image+='<img src="'+productDetails.sides[i]+'" />';
												}
												image+='</div>';
												document.getElementById("image_data").innerHTML = image;
												jQuery("#customize").removeClass('disabled');
											}
										});
									});
								}
							});
						}
					   
					},
					error   : function(xhr,status,error)
					{
						//alert(xhr+":"+status+":"+error);
						console.log(xhr);
						}
				});
			}
		}
	});	   // event.preventDefault(); //Prevent the default submit
});
jQuery("input[name=variation_id]").bind("change paste keyup", function() {
	var value = jQuery(this).val()
	if(value!='')
	{
		var url = jQuery("#customize").attr('href');
		if(url.search("simplePdctId")!=-1)
		{
			var urlSplit = url.split("&simplePdctId=");
			url = urlSplit[0]+"&simplePdctId="+value;
		}
		else
		{
			url = url+"&simplePdctId="+value;
		}		
		jQuery("#customize").attr('href',url);
	}
  // alert(jQuery(this).val()); 
});
jQuery("input[name=quantity]").bind("change paste keyup", function() {
		var qty = jQuery("input[name=quantity]").val();
		var url = jQuery("#customize").attr('href');
		url = url.split("&qty");
		url = url[0]+"&qty="+qty
		jQuery("#customize").attr('href',url);
});