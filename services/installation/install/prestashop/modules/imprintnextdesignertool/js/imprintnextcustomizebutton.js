jQuery(document).ready(function() {
    $("#imprintnextcustomizebutton_block_home").hide();
    $("#imprintnextaddtocartbutton_block_home").hide();
    check_customization();
});
//hide pdp attribute by productId only predeco product case//
jQuery(document).ready(function() {
    var fieldset = $("#attributes fieldset.attribute_fieldset");
    var i;
    for (i = 0; i < fieldset.length; ++i) {
        var label = $(".attribute_fieldset label.attribute_label");
        var j;
        for (var j = 0; j < label.length; ++j) {
            if (label[j].innerHTML == 'pdp&nbsp;') {
                var inerElement = fieldset[j].innerHTML;
                inerElement = inerElement.replace('class="attribute_label"', 'style="display:none;"');
                inerElement = inerElement.replace('class="attribute_list"', 'style="display:none;"');
                fieldset[j].innerHTML = inerElement;
            }
        }
    }
});
var xeApiUrl = xeStoreUrl + "xetool/api/index.php";
var xeStoreId = "1"; // CHANGE THIS
var xePrntTypes = "";
var xeRevNo = "";
var xeStrUrl = "";
var xeSimplPid = "";
var xeCombinationId = "";
var xeProductCustomizable = "";
var customer = "";
var sid = "";
var refid = 0;
//get referenceid by productId and hide store addToCart button and add custom addToCart button
jQuery.get(xeApiUrl + "?reqmethod=getRefId&pid=" + product_id, function(data) {
    refid = data;
    sid = '&ptid=' + data;
    if (refid >= 1) {
        $("#add_to_cart").remove();
        $("#imprintnextaddtocartbutton_block_home").show();
    }
});
// To check if product is customizable, Show/Hide Customize button
jQuery.get(xeApiUrl + "?reqmethod=checkIsCustomiseByProductId&productid=" + product_id, function(data) {
    customer = data[0]['customer_id'];
    if (data[0]['active_product'] == 1) {
        xeProductCustomizable = true;
        $("#imprintnextcustomizebutton_block_home").show();
    }
});
jQuery.get(xeApiUrl + "?reqmethod=getPrintMethodByProduct&pid=" + product_id, function(data) {
    jQuery.each(data, function(i, item) {
        if (xePrntTypes == '') {
            xePrntTypes = item.print_method_id;
        } else {
            xePrntTypes = xePrntTypes + ',' + item.print_method_id;
        }
        if (data.length == i + 1) xeStrUrl = xeStrUrl + "&pt=" + xePrntTypes;
    });
});
jQuery.get(xeApiUrl + "?reqmethod=getLatestRevision", function(data) {
    xeRevNo = data;
    xeStrUrl = xeStrUrl + "&rvn=" + xeRevNo;
});
jQuery.get(xeApiUrl + "?reqmethod=getCmsPageId", function(data) {
    cmsId = "content/" + data + "-designer-tool?";
});
//addToCart predeco product 
var addTemplateToCart = function() {
        var pid = product_id;
        var quantity = $('#quantity_wanted').val();
        var combinationId = $('#idCombination').val();
        jQuery.get(xeApiUrl + "?reqmethod=addTemplateToCart&pid=" + product_id + '&vid=' + combinationId + '&orderQty=' + quantity + '&refid=' + refid, function(data) {
            if (data['status'] == 'success') window.location.href = data['url'];
            else alert(data['status']);
        });
    }
    // Customize button action
var customize_product = function() {
        var xeId = "id=" + product_id;
        var xeStore = "&store=" + xeStoreId;
        var xeQuantity = "&qty=" + $('#quantity_wanted').val();
        var xeCombination = "&simplePdctId=" + $('#idCombination').val();
        var customer_id = "&customer=" + customer;
        var screenWidth = (window.innerWidth > 0) ? window.innerWidth : screen.width;
        if (screenWidth < 1024) {
            var xeUrl = xeStoreUrl + "xetool/index.html?" + xeId + xeStrUrl + xeStore + xeQuantity + xeCombination + customer_id + sid;
        } else {
            var xeUrl = xeStoreUrl + cmsId + xeId + xeStrUrl + xeStore + xeQuantity + xeCombination + customer_id + sid;
        }
        window.location.href = xeUrl;
    }
    // To check if the product combination is modified
var check_customization = function() {
    var xeComb = $('#idCombination').val();
    if (xeCombinationId == "") {
        xeCombinationId = xeComb;
    } else {
        if (xeCombinationId != xeComb) {
            xeCombinationId = xeComb;
        }
    }
}
$(window).bind('hashchange', function() {
    check_customization();
});