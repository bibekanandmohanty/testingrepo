//check order is customize or not
var xeApiUrl = xeStoreUrl + "xetool/api/index.php";
jQuery(document).ready(function() {
    jQuery.get(xeApiUrl + "?reqmethod=customizeOrder&order_id=" + order_id, function(data) {
        if (data == 1) {
            $("#downloadDiv").show();
        }
    });
});
//Download order zip by order id
var order_download = function() {
    if (order_id != null && order_id != '') {
        var url = xeApiUrl + '?reqmethod=downloadOrderZipAdmin&order_id=' + order_id+'&increment_id='+order_id;
        window.open(url);
    } else {
        alert("Invalid Order");
    }
}
//Get customize product image by ref_id
jQuery(document).ready(function() {
    $('tr.product-line-row').each(function(k) {
        var orderDetailId = $(this).find('td div.product_price_edit input.edit_product_id_order_detail').attr('value');
        jQuery.get(xeApiUrl + "?reqmethod=getRefIdByOrderDetailId&orderDetailId=" + orderDetailId, function(data) {
            if (data != 0) {
                $.post(xeApiUrl, {
                    reqmethod: "getCustomPreviewImages",
                    refids: data
                }, function(returnedData) {
                    jQuery.each(returnedData, function(m, inner) {
                        if (m != 0) {
                            $('tr.product-line-row').each(function(k) {
                            var orderDetailIds = $(this).find('td div.product_price_edit input.edit_product_id_order_detail').attr('value');
                                if (orderDetailIds == orderDetailId) {
                                    $(this).find('td img.imgm').attr('src', inner[0].customImageUrl);
                                    $(this).find('td img.imgm').attr('height', 55);
                                    $(this).find('td img.imgm').attr('width', 55);
                                }
                            });
                        }
                    });
                });
            }
        });
    });
});