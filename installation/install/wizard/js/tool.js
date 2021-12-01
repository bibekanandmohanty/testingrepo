 /* ============================================================
 * JS related to step four for installation goes here.
 * This will be called in only 4th page of the installation.
 * This will create dummy products and set basic admin settings.
 * ============================================================ */ 
function prepareMethodList(methods){
	$(methods).each(function(key, val) {
 		if (val.name == 'DTG') {
 			$('#printProfile').append($("<option selected></option>").attr("value",val.id).text(val.name));
 		}else
 			$('#printProfile').append($("<option></option>").attr("value",val.id).text(val.name));
	});
}

function prepareProductList(products){
	$(products).each(function(key, val) {
 		$('#products').append($("<option></option>").attr("value",val.id).text(val.name));
	});
}

function completeSetUp(){
	$("#loader").removeClass('d-none');
	var serviceURL = getBaseURL();
 	var langs = get(serviceURL+'getLanguageSelected');
	var printMethodIDs = $('#printProfile').val();
	var ProductIDs = $('#products').val();
	if (!printMethodIDs || !ProductIDs) {
		$('#message').removeClass('d-none');
    	$('#errorMSG').text(langs.SELECT_DUMMY_DATA);
    	$("#loader").addClass('d-none');
		return;
	}
	var themeID = '';
	$('.themeVal').each(function(count, element) {
	    if ($(this).prop("checked") == true) {
	    	themeID = $(this).val();
	    }
	});
	var themeCol = $('#themeCol').val();
	var dataArr = {"print_methods": printMethodIDs, "products": ProductIDs, "themeID": themeID, "themeCol":themeCol, "setup_type": "custom"};
	var postInfo = btoa(JSON.stringify(dataArr));
 	var saveURL = serviceURL+'completeXESetup';
	var dataArr = {"data": postInfo};
	var strResponse = get(serviceURL+'getStoreResponseServer');
    $.ajax({
		type:"GET",
		cache:false,
		url:strResponse,
		dataType:"JSON",
		data:{},
		error:function (xhr, ajaxOptions, thrownError){
			if(xhr.status == 404 || xhr.status == 500 || xhr.status == 403) {
				$('#message').removeClass('d-none');
				$('#errorMSG').text("Nginx Server Error");
				$("#loader").addClass('d-none');
				$(".tick-icon").addClass("close_error");
				$(".tick-icon").addClass("error");
				$(".tick-icon").removeClass('tick-icon');
				$('.btn-success').hide();
				$('#errorMSG').html(" Some settings need to be changed! Please check <a href='https://imprintnext.freshdesk.com/support/solutions/articles/81000387809-imprintnext-api-for-nginx-server' target='_blank'>help DOC</a>  <div class='btn btn-sm btn-dark float-right' id='retryBtn'><i class='pg pg-refresh'></i> Retry</div>");
				$("#errorMSG").delegate( "div", "click", function() {
					completeSetUp();
				});
			window.stop();
			}
		}
	});
	var settings = {
	  "url": saveURL,
	  "method": "POST",
	  "headers": {
	    "Content-Type": "application/x-www-form-urlencoded"
	  },
	  "data": dataArr,
	  success: function (result) {
	 		var response = JSON.parse(result);
	 		console.log(response);
	       if (response.proceed_next) {
            	$("body").load(domain+"/toolInfo.html");
	       }else{
	       	$('#message').removeClass('d-none');
            $('#errorMSG').text(langs[response.message_code]);
	       }
	       $("#loader").addClass('d-none');
	   	}
	};
	$.ajax(settings).done(function (response) {
	});
}

$(document).ready(function() {
 	// Check Server Settings
 	var serviceURL = getBaseURL();
 	updateLanguage();
 	var methods = get(serviceURL+'getPrintMethods');
 	prepareMethodList(methods.data);
 	var products = get(serviceURL+'getDummyProducts');
 	prepareProductList(products.data);
 });