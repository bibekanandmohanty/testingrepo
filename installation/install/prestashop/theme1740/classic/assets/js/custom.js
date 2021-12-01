/*
 * Custom code goes here.
 * A template should always ship with an empty custom.js
 */
var productImg, butoonType ,addTocartDiv, span,divValue, selectValue, label, value ,spanValue = '';
//Change Predeco Color Variant Image
function changePredecoImage(url){
    $(".predeco-image").attr("href",url);
    $(".predeco-image").attr("xlink:href",url);
}
var fullUrl = window.location.href;
var splitUrl = fullUrl.split("/");
var domainUrl = splitUrl[0] + "//" + splitUrl[2] + '/designer';
if(localStorage.getItem("xeStoreUrl") != ''){
    domainUrl = localStorage.getItem("xeStoreUrl");
}
$("#tshirtIFrame").attr("src",domainUrl+"/index.html");
//To check whether addToCart button disable or not
addTocartDiv = $('div.add');
if (typeof storeId !== 'undefined')
{
    store = '&store_id='+storeId;
}else{
    store = '&store_id=1';
}
if (addTocartDiv != '') butoonType = $('button.btn').attr('disabled');
jQuery(document).ready(function() {
    
    $("#js-thumbs").on("click",function(){
        var predecoImageSrc = $(this).attr('data-predeco-url');
        $(".predeco-image").attr("href",predecoImageSrc);
        $(".predeco-image").attr("xlink:href",predecoImageSrc);
    });
    //Start hide extra attribute from cart page for predeco product
    $('div.details').each(function() {
        spanValue = $(this).find('span').text();
        if (spanValue != '') {
            if (spanValue.indexOf(", pdp : active") >= 0) {
                spanValue = spanValue.replace(', pdp : active', '');
                $(this).find('span').text(spanValue);
            }
            if (spanValue.indexOf(", pdp : inactive") >= 0) {
                spanValue = spanValue.replace(', pdp : inactive', '');
                $(this).find('span').text(spanValue);
            }
        }
    });
    $('div.product-line-info').each(function() {
        label = $(this).find('span.label').text();
        value = $(this).find('span.value').text();
        if (label != '' && label == 'pdp:') {
            if (label.indexOf("pdp:") >= 0) {
                $(this).find('span.label').text('');
            }
            if (value.indexOf("inactive") >= 0) {
                $(this).find('span.value').text('');
            }
            if (value.indexOf("active") >= 0) {
                $(this).find('span.value').text('');
            }
        }
    });
    $('div.product-variants div').each(function() {
        span = $(this).find('span.control-label').text();
        if (span != '' && span == 'pdp') {
            divValue = $(this).find('div.clearfix');
            divValue.context.innerHTML = '';
        }
    });
});
if (typeof xeStoreUrl !== 'undefined' && typeof product_id !== 'undefined') {
    var xeApiUrl = xeStoreUrl + "/api/v1/";
    var xeStoreId = "1"; // CHANGE THIS
    var xeStrUrl = "";
    var customer = "";
    var cmsId = cms_page;
    var designId = parseInt(xe_is_temp);
    var pbti = 0;
    localStorage.setItem("xeStoreUrl", xeStoreUrl);
    if (typeof p_category !== 'undefined') {
        jQuery.get(xeApiUrl+"template-products?prodcatID="+p_category,function(data){
            data = JSON.parse(data);
            jQuery.each(data, function(i, item) {
                if(item != false){
                    pbti = 1;
                }
            });
        });
    }

    // Customize button action
    function customize_product() {
        var pbtiStr = '&pbti='+pbti;
        var cominationIds = $('#product-details').data('product');
        var screenWidth = (window.innerWidth > 0) ? window.innerWidth : screen.width;
        var selectedVariantId = cominationIds.id_product_attribute;
        if(selectedVariantId == 0){
            selectedVariantId = product_id;
        }
        var url = '';
        var variantId = '';
        var qty = "&qty=" + $('#quantity_wanted').val();
        var dpid = '';
        if(designId>0){
            dpid = '&dpid='+ designId;
        }
        //Check for mobile device
        variantId =  variantId+"&vid="+selectedVariantId;
        if (screenWidth < 1024){
            url = xeStoreUrl+"/index.html?id="+product_id+variantId+qty+dpid+pbtiStr+store;
        }else{
            url = cmsId +"id="+product_id+variantId+qty+dpid+pbtiStr+store;
        }
        window.location.href = url;
    }
}
 //Start update cart page for custom product
if (typeof xeStoreUrl !== 'undefined' && typeof cart_id !== 'undefined') {
    var refId = 0, attrId = 0, productId = 0, mainProductId=0;
    $('ul.cart-items  li').each(function(k) {
        attrId = $(this).find('a.remove-from-cart').attr('data-id-product-attribute');
        mainProductId = $(this).find('a.remove-from-cart').attr('data-id-product');
        if(attrId>0){
            attrId = attrId;
        }else{
           attrId = mainProductId; 
        }
        refId = $(this).find('a.remove-from-cart').attr('data-id-ref_id');
        //refId = parseInt(refId);
        var imageTagScr = $(this).find('span.product-image img');
        var imageTagheight = $(this).find('span.product-image img');
        var imageTagWidth = $(this).find('span.product-image img');
        if(refId && refId>0){
            updateProductCustomizeImage(refId, attrId, imageTagScr);
            imageTagheight.attr('height', 125);
            imageTagWidth.attr('width', 125);

        }
    });
}
//End update cart page for custom product

function updateProductCustomizeImage(refId, attrId, imageTagScr){
    var designData = [];
    var xeApiUrl = xeStoreUrl + "/api/v1/preview-images";
    jQuery.get(xeApiUrl + "?custom_design_id="+refId+"&product_id="+attrId, function(data) {
        designData = data[refId];
        var i;
        var image = '';
        for (i = 0; i < designData.length; i++) {
            if(designData[i].design_status){
                image = designData[i].customImageUrl[i];
                imageTagScr.attr('src', image);
            }
        }
    });
}

//cart edit button
function cartEdit(refid,id_product_attribute,id_product,cart_key,qty) {
    var itemIncrmntId = cart_key-1;
    var cmsId = cms_page;
    var screenWidth = (window.innerWidth > 0) ? window.innerWidth : screen.width;
    var selectedVariantId = id_product_attribute;
    var url = '';
    var variantId = '';
    var qty = "&qty=" + $('.js-cart-line-product-quantity:eq('+itemIncrmntId+')').val();
    console.log('qty',qty);
    var dpid = '';
    if(refId>0){
        dpid = '&dpid='+ refId;
    }
    var itemId = '&cart_item_id='+refid;
    //Check for mobile device
    variantId =  variantId+"&vid="+selectedVariantId;
    if (screenWidth < 1024){
        url = xeStoreUrl+"/index.html?id="+id_product+variantId+qty+dpid+itemId+store;
    }else{
        url = cmsId +"id="+id_product+variantId+qty+dpid+itemId+store;
    }
    window.location.href = url; 
}
//addToCart for predeco product 
function addTemplateToCart(refid) {
    var designId = refid;
    var cominationIds = $('#product-details').data('product');
    var quantity = $('#quantity_wanted').val();
    var combinationId = cominationIds.id_product_attribute?cominationIds.id_product_attribute:0;
    var url = xeApiUrl+'carts/directcart';
     $.post(url, {
        design_id: refid,
        product_id:product_id,
        variant_id:combinationId,
        order_qty:quantity
    }, function(returnedData) { 
        if (returnedData['status']){
            window.location.href = returnedData['url'];
        }else{
            alert(data['message']);
        }  
    });
}

//Start Update order details page after order with customize products
jQuery(document).ready(function() {
    if (typeof xeStoreUrl !== 'undefined' && typeof cart_ids !== 'undefined') {
        var refId = 0, attrId = 0, productId = 0, mainProductId=0;
        $('div.order-confirmation-table div.order-line').each(function(k) {
            attrId = $(this).find('span.image').attr('data-id-product-attribute');
            mainProductId = $(this).find('span.image').attr('data-id-product');
            if(attrId>0){
                attrId = attrId;
            }else{
               attrId = mainProductId; 
            }
            refId = $(this).find('span.image').attr('data-id-ref_id');
            refId = parseInt(refId);
            var pdpValue = $(this).find('div.details span').text();
            if (pdpValue != '') {
                if (pdpValue.indexOf(" pdp : active,") >= 0) {
                    pdpValue = pdpValue.replace(' pdp : active,', '');
                    $(this).find('div.details span').text(pdpValue);
                }
                if (pdpValue.indexOf(" pdp : inactive,") >= 0) {
                    pdpValue = pdpValue.replace(' pdp : inactive,', '');
                    $(this).find('div.details span').text(pdpValue);
                }
            }
            var imageTagScr = $(this).find('span.image img');
            if(refId && refId>0){
                updateProductCustomizeImage(refId, attrId, imageTagScr);
            }
        });
    }
});
//End