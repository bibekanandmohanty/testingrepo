function nameAndNumberInfo(refId, attr){
	var rurl = ink_pd_vars.siteurl;
	jQuery.get(rurl+'/xetool/api/index.php' + "?reqmethod=getNameAndNumberByRefId&refId=" + refId+"&attValue="+attr, function(data) { 
       if(data.nameNumberData !=''){
            var div = "<div id='name-number' class='modal fade' tabindex='-1' role='dialog' aria-labelledby='myModalLabel' aria-hidden='true'><div class='modal-dialog' role='document'><div id='modal-content' class='modal-content'><div class='modal-header' style='background: #2fb5d2;color: #fff;text-align: center;height:42px;'> <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button><h4 class='modal-title' id='myModalLabel' style='clear:none;padding-top:10px;'>Name and Number List</h4></div><div class='modal-body' style='overflow-y:auto;height:300px;'><table class='table' border='1'><thead><tr><th style='border: 2px solid #f6f6f6;'></th><th colspan='2' style='text-align: center;border: 2px solid #f6f6f6;'>Front</th><th colspan='2' style='text-align: center;border: 2px solid #f6f6f6;'>Back</th></tr> <tr><th style='border: 2px solid #f6f6f6;'>Size</th><th style='border: 2px solid #f6f6f6;'>Name</th><th style='border: 2px solid #f6f6f6;'>Number</th> <th style='border: 2px solid #f6f6f6;'>Name</th><th style='border: 2px solid #f6f6f6;'>Number</th></tr></thead><tbody>";
                jQuery.each(data.nameNumberData, function(i, result) {
                    div += "<tr><td style='border: 2px solid #f6f6f6;'>"+result.size+"</td><td style='border: 2px solid #f6f6f6;'>"+result.front.name+"</td><td style='border: 2px solid #f6f6f6;'>"+result.front.number+"</td><td style='border: 2px solid #f6f6f6;'>"+result.back.name+"</td><td style='border: 2px solid #f6f6f6;'>"+result.back.number+"</td></tr>";
                });
                div += "</tbody></table></div></div></div>";
            showNameAndNumberModals(div);
        }
    });
}
function showNameAndNumberModals(div){
    function getBlockCartModal() {
        return jQuery('#name-number');
    }
    var blockCartModal = getBlockCartModal();
    if (blockCartModal.length) {
        blockCartModal.remove();
    }
    jQuery('#content').append(div);
};
jQuery(document).ready(function(){  
    var elem = jQuery(".shop_table tbody tr td.product-name dt");
    elem.each(function(index) {
        if(jQuery(this).attr('class')=='variation-xe_color')
            jQuery(this).html("Color : ");
        if(jQuery(this).attr('class')=='variation-xe_size')
            jQuery(this).html("Size : ");
    });
    	
	jQuery(".close").live( "click", function() {
		jQuery('.modal').hide();
	})
	jQuery('body').click(function(evt){ 
		if (!jQuery(evt.target).closest('#modal-content').length) {				
			jQuery('.modal').hide();
		} 
	});
});
jQuery('body').on('updated_cart_totals',function() {
    location.reload();
});