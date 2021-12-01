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
//jQuery(".product_meta").before('<a id="customize" href="javascript:void(0)" class="customize-btn button alt disabled" >Customize</a>');
//Customize button Placement
jQuery('[name=add-to-cart]').after('<a id="customize" href="javascript:void(0)" class="customize-btn button alt disabled" >Customize</a>');

jQuery(".quantity").before('<div class="col-12 d-flex justify-content-between"><h4 class="text">Option Price:</h4><h4 class="number"><span class = "option-price">0</span></h4></div><div class="col-12"><hr></div><div class="col-12 d-flex justify-content-between"><h4 class="text">Unit Price:</h4><h4 class="number"><span class = "unit-price">'+jQuery('.woocommerce-Price-amount').html()+'</span></h4></div><div class="col-12"><hr></div><div class="col-12 d-flex justify-content-between total"><h4 class="text">Total Price:</h4><h4 class="number"><span class="total-tm-price"></span></h4></div>');

var original_price = jQuery('.woocommerce-Price-amount').html();
jQuery('<div class="price_total_2 tm-extra-product-options-totals"><p class="price">'+original_price+'</p></div>').insertAfter('.total-tm-price');

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

jQuery(document).ready(function() {
    jQuery('#customize').click(function(e) {
        e.preventDefault();
        var str = jQuery(".cart").serialize();
		var arrStr = str.split('&');
		console.log("STR :"+str);
		var newArray = new Array();
		var Base64={_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",encode:function(e){var t="";var n,r,i,s,o,u,a;var f=0;e=Base64._utf8_encode(e);while(f<e.length){n=e.charCodeAt(f++);r=e.charCodeAt(f++);i=e.charCodeAt(f++);s=n>>2;o=(n&3)<<4|r>>4;u=(r&15)<<2|i>>6;a=i&63;if(isNaN(r)){u=a=64}else if(isNaN(i)){a=64}t=t+this._keyStr.charAt(s)+this._keyStr.charAt(o)+this._keyStr.charAt(u)+this._keyStr.charAt(a)}return t},decode:function(e){var t="";var n,r,i;var s,o,u,a;var f=0;e=e.replace(/[^A-Za-z0-9+/=]/g,"");while(f<e.length){s=this._keyStr.indexOf(e.charAt(f++));o=this._keyStr.indexOf(e.charAt(f++));u=this._keyStr.indexOf(e.charAt(f++));a=this._keyStr.indexOf(e.charAt(f++));n=s<<2|o>>4;r=(o&15)<<4|u>>2;i=(u&3)<<6|a;t=t+String.fromCharCode(n);if(u!=64){t=t+String.fromCharCode(r)}if(a!=64){t=t+String.fromCharCode(i)}}t=Base64._utf8_decode(t);return t},_utf8_encode:function(e){e=e.replace(/rn/g,"n");var t="";for(var n=0;n<e.length;n++){var r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r)}else if(r>127&&r<2048){t+=String.fromCharCode(r>>6|192);t+=String.fromCharCode(r&63|128)}else{t+=String.fromCharCode(r>>12|224);t+=String.fromCharCode(r>>6&63|128);t+=String.fromCharCode(r&63|128)}}return t},_utf8_decode:function(e){var t="";var n=0;var r=c1=c2=0;while(n<e.length){r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r);n++}else if(r>191&&r<224){c2=e.charCodeAt(n+1);t+=String.fromCharCode((r&31)<<6|c2&63);n+=2}else{c2=e.charCodeAt(n+1);c3=e.charCodeAt(n+2);t+=String.fromCharCode((r&15)<<12|(c2&63)<<6|c3&63);n+=3}}return t}}
		var i = 0;
		for (var key in arrStr) {
		    if (arrStr[key].match(/tmcp_.*/)) {
		    	newArray[i] = arrStr[key];
		    	i++;
		    }
		}		
		var quantity = jQuery('[name=quantity]').val(); 
		var optionPrice = jQuery('.tm-custom-price-totals .final').html(); 
		if (typeof(optionPrice) !== "undefined") {
			var tPrice = String(optionPrice).substr(1, optionPrice.length);
		    tPrice = parseFloat(tPrice.replace(/,/g, '')) / (parseInt(quantity));
		    var originalTotalProductPrice = jQuery('#product_total_price .discount-price').data('original-price') *  parseInt(quantity);
		    var totalPrice = tPrice * parseInt(quantity);
		    var customPrice = (totalPrice - originalTotalProductPrice) / parseInt(quantity);
		    newArray.push("option_price="+customPrice);
		}
	    var customData = Base64.encode(decodeURIComponent(newArray.join('&')));
		var url = jQuery("#customize").attr('href');
		url = url+"&token="+customData;
		//console.log("url:"+url);return false;
		window.location.href = url;
		return false;
    });
});