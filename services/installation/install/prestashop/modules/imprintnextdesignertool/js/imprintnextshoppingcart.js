jQuery(document).ready(function() {
    //hide extra 'pdp' attribute from the products
    //hide in minicart
    $('.product-atributes a').each(function() {
        var value = $(this).text(); // will give you the value.
        if (value != '') {
            if (value.indexOf(", active") >= 0) {
                value = value.replace(', active', '');
                $(this).text(value);
            }
            if (value.indexOf(", inactive") >= 0) {
                value = value.replace(', inactive', '');
                $(this).text(value);
            }
			if (value.indexOf("active, ") >= 0) {
                value = value.replace('active, ', '');
                $(this).text(value);
            }
            if (value.indexOf("inactive, ") >= 0) {
                value = value.replace('inactive, ', '');
                $(this).text(value);
            }
        }
    });
    //hide in cart description
    $('.cart_description a').each(function() {
        var value = $(this).text(); // will give you the value.
        if (value != '') {
            if (value.indexOf(", pdp : active") >= 0) {
                value = value.replace(', pdp : active', '');
                $(this).text(value);
            }
            if (value.indexOf(", pdp : inactive") >= 0) {
                value = value.replace(', pdp : inactive', '');
                $(this).text(value);
            }
			if (value.indexOf("pdp : active, ") >= 0) {
                value = value.replace('pdp : active, ', '');
                $(this).text(value);
            }
            if (value.indexOf("pdp : inactive, ") >= 0) {
                value = value.replace('pdp : inactive, ', '');
                $(this).text(value);
            } 
        }
    });
    var xeApiUrl = xeStoreUrl + "xetool/api/index.php";
    // Get product_id, id_product_attribute, id_address_delivery, id_shop, quantity by cartId
    jQuery.get(xeApiUrl + "?reqmethod=fetchProductBycartId&cartId=" + cart_id, function(fetchProductBycartIdRes) {
        if (fetchProductBycartIdRes != '') {
            var data = fetchProductBycartIdRes;
            var refIdTxt = "";
            for (var i = 0; i < data.length; i++) {
                if (i == 0) refIdTxt += data[i].ref_id;
                else refIdTxt += "," + data[i].ref_id;
            }
            if (refIdTxt != 0) {
                // Get thumb SVG image paths by refIds
                $.post(xeApiUrl, {
                    reqmethod: "getCustomPreviewImages",
                    refids: refIdTxt
                }, function(returnedData) {
                    //Start code upadte minicart for custom product
                    var cartbox = $('.cart_block_list');
                    if (cartbox) {
                        for (var l = 0; l < data.length; l++) {
                            if (data[l].ref_id != 0) {
                                var quantity = 0; // HAVE TO CHECK THIS
                                var dt = "cart_block_product_" + data[l].product_id + "_" + data[l].id_product_attribute + "_" + data[l].id_address_delivery + "_" + data[l].ref_id;
                                // Get default image properties
                                var a_href = "",
                                    imgAlt = "",
                                    title = '',
                                    aquantity = 0,
                                    href = '',
                                    avlaue = '',
                                    r_href = '',
                                    r_title = '',
                                    price = 0,
									attrId = 0;
                                $('[data-id="' + dt + '"]').each(function() {
                                    a_href = $(this).find('a.cart-images').attr('href');
                                    title = $(this).find('a.cart-images').attr('title');
                                    imgAlt = $(this).find('a.cart-images img').attr('alt');
                                    aquantity = $(this).find('span.quantity').text();
                                    href = $(this).find('a.cart_block_product_name').attr('href');
                                    avlaue = $(this).find('a.cart_block_product_name').text();
                                    r_href = $(this).find('a.ajax_cart_block_remove_link').attr('href');
                                    r_title = $(this).find('a.ajax_cart_block_remove_link').attr('title');
                                    pa_href = $(this).find('div.product-atributes a').attr('href');
                                    pa_title = $(this).find('div.product-atributes a').attr('title');
                                    pa_vlaue = $(this).find('div.product-atributes a').text();
                                    if (pa_vlaue.indexOf(", active") >= 0) {
                                        pa_vlaue = pa_vlaue.replace(', active', '');
                                        $(this).text(pa_vlaue);
                                    }
                                    if (pa_vlaue.indexOf(", inactive") >= 0) {
                                        pa_vlaue = pa_vlaue.replace(', inactive', '');
                                        $(this).text(pa_vlaue);
                                    }
									if (pa_vlaue.indexOf("active, ") >= 0) {
                                        pa_vlaue = pa_vlaue.replace('active, ', '');
                                        $(this).text(pa_vlaue);
                                    }
                                    if (pa_vlaue.indexOf("inactive, ") >= 0) {
                                        pa_vlaue = pa_vlaue.replace('inactive, ', '');
                                        $(this).text(pa_vlaue);
                                    }
                                    price = $(this).find('span.price').text();
                                });
                                jQuery.each(returnedData, function(m, inner) {
                                    if (data[l].ref_id == m) {
                                        var thumbImages = "";
                                        thumbImages += "<a class='cart-images' href='" + a_href + "' title='" + title + "'><img class='replace-2x' width='80' height='80' alt='" + imgAlt + "' src='" + inner[0].customImageUrl + "'></a><div class='cart-info'><div class='product-name'><span class='quantity-formated'><span class='quantity'>" + aquantity + "</span>&nbsp;x&nbsp</span><a class='cart_block_product_name' href='" + href + "' title='" + title + "'>" + avlaue + "</a></div><div class='product-atributes'><a href='" + pa_href + "' title='" + pa_title + "'>" + pa_vlaue + "</a></div><span class='price'>" + price + "<div class='hookDisplayProductPriceBlock-price'></div></span></div><span class='remove_link'><a class='ajax_cart_block_remove_link' href='" + r_href + "' rel='nofollow' title='" + r_title + "'></a></span>";
                                        $('[data-id="' + dt + '"]').html(thumbImages);
                                    }
                                });
                            }
                        }
                    }
                    //End code upadte minicart for custom product
                    //Start update cart page for custom product
                    for (var j = 0; j < data.length; j++) {
                        if (data[j].ref_id != 0) {
                            var quantity = 0; // HAVE TO CHECK THIS
                            var trId = "product_" + data[j].product_id + "_" + data[j].id_product_attribute + "_" + quantity + "_" + data[j].id_address_delivery + "_" + data[j].ref_id;
                            // Get default image properties
                            var a_href = "";
                            var imgAlt = "";
                            $("tr#" + trId).each(function() {
                                a_href = $(this).find('td.cart_product a').attr('href');
                                imgAlt = $(this).find('td.cart_product a img').attr('alt');
                            });
                            jQuery.each(returnedData, function(k, item) {
                                if (data[j].ref_id == k) {
									attrId = data[j].id_product_attribute;
                                    var thumbImages = "";
                                    jQuery.each(item, function(l, itemInner) {
										if(itemInner.nameAndNumber){
											if(attrId !=0){
												thumbImages += "<a href='" + a_href + "'><img width='98' height='98' alt='" + imgAlt + "' src='" + itemInner.customImageUrl + "'></a><a class='label label-success' href='javascript:void(0)' title=Name and number item parameters onclick='nameAndNumberInfo("+data[j].ref_id+","+attrId+")' >Info</a>";
											}
										}else{ 
											thumbImages += "<a href='" + a_href + "'><img width='98' height='98' alt='" + imgAlt + "' src='" + itemInner.customImageUrl + "'></a>";
										}
                                    });
                                    $('#' + trId + ' td:eq(0)').html(thumbImages);
                                }
                            });
                        }
                    }
                    //End update cart page for custom product
                });
            }
        }
    });
});
var nameAndNumberInfo = function(refid, id){
	jQuery.get(xeApiUrl + "?reqmethod=getNameAndNumberByRefId&refId=" + refid+"&pid="+id, function(data) { 
		if(data.nameNumberData !=''){
			var div = "<div id='myModal' class='modal'><div id='popupdiv' class='modal-content' align='center'><span class='close' onclick = 'closeModal();'>x</span><table class='custom-table' ><thead><h2>Name And Number List</h2><tr><th style='border: 2px solid #f6f6f6;'></th><th colspan='2' style='text-align: center;border: 2px solid #f6f6f6;'>Front</th><th colspan='2' style='text-align: center;border: 2px solid #f6f6f6;'>Back</th></tr> <tr><th style='border: 2px solid #f6f6f6;'>Size</th><th style='border: 2px solid #f6f6f6;'>Name</th><th style='border: 2px solid #f6f6f6;'>Number</th> <th style='border: 2px solid #f6f6f6;'>Name</th><th style='border: 2px solid #f6f6f6;'>Number</th></tr></thead><tbody>";
			jQuery.each(data.nameNumberData, function(i, result) {
				 div += "<tr><td style='border: 2px solid #f6f6f6;'>"+result.size+"</td><td style='border: 2px solid #f6f6f6;'>"+result.front.name+"</td><td style='border: 2px solid #f6f6f6;'>"+result.front.number+"</td><td style='border: 2px solid #f6f6f6;'>"+result.back.name+"</td><td style='border: 2px solid #f6f6f6;'>"+result.back.number+"</td></tr>";
			});
			div += "</tbody></table></div></div>";
			var divData = jQuery('#myModal');
			if(divData.length) divData.remove();
			jQuery('body').append(div);
			addDiv();
		}
	});
}
 var addDiv = function(){
	var modal = document.getElementById('myModal');
	modal.style.display = "block";
}
 var closeModal = function(){
	var modal = document.getElementById('myModal');
	modal.style.display = "none";
}
jQuery(document).mouseup(function (e) {
    var modal = document.getElementById('myModal');
	if(modal !=null){
		modal.style.display = "none";
	}
});