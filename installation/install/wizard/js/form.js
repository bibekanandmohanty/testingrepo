 /* ============================================================
 * JS related to step three for installation goes here.
 * This will be called in only 3rd page of the installation.
 * This will check and set Database details, store and inkXE admin credentials
 * ============================================================ */ 
 function DBsetUp(){
 	$('#message').addClass('d-none');
 	var validInput = true;
	var serviceURL = getBaseURL();
 	var langs = get(serviceURL+'getLanguageSelected');
	$("input").each(function() {
	   var element = $(this);
	   if (element.val() == "") {
	       validInput = false;
	       return
	   }
	});
	if (validInput == true) {
		var input = {};
		input['host'] = $('#host').val();
		input['dbname'] = $('#dbName').val();
		input['user'] = $('#dbUser').val();
		input['pwd'] = $('#dbPwd').val();
		var postInfo = btoa(JSON.stringify(input));
	}else {
		$('#message').removeClass('d-none');
        $('#errorMSG').text(langs.ENTER_ALL_VAL);
		return;
	}
	$("#loader").removeClass('d-none');
	var infoURL = serviceURL+'getPackageInfo'; 
 	var info = get(infoURL);
 	var storeName = info.data.store;
 	var storeVersion = info.data.store_api_ver;
 	if(storeName == 'magento'){
 		if(storeVersion == '1.X'){
 			storeName = 'magento1x';
 		}else{
 			storeName = 'magento2x';
 		}
 	}
 	var saveURL = serviceURL+'saveConfiguration';
	var dataArr = {"type": 'db', "data": postInfo};
	// post to server
	var settings = {
	  "url": saveURL,
	  "method": "POST",
	  "headers": {
	    "Content-Type": "application/x-www-form-urlencoded"
	  },
	  "data": dataArr,
	  success: function (result) {
	 		var response = JSON.parse(result);
	       if (response.proceed_next) {
		       	domain = getBaseSiteURL();
		       	$("#head2").addClass('active');
		       	$('#inputForm').load("wizard/html/"+storeName+"_form.html");
	       }else{
	       	$('#message').removeClass('d-none');
        	$('#errorMSG').text(langs[response.message]);
        	$("#loader").addClass('d-none');
	       }
	   	}
	};
	$.ajax(settings).done(function (response) {
		updateLanguage();
		setTimeout(function() { 
	    	$("#loader").addClass('d-none');
    	}, 2000);
	});
}

function setStoreCred(){
	$('#message').addClass('d-none');
	var validInput = true;
	var serviceURL = getBaseURL();
 	var langs = get(serviceURL+'getLanguageSelected');
	var input = {};
	$("input").each(function() {
	   var element = $(this);
	   if (element.val() == "") {
	       validInput = false;
	       return
	   }
	   if (element.attr('type') != 'button') {
	   	 input[element.attr('name')] = element.val();
	   }
	});
	if (validInput == true) {
		var postInfo = btoa(JSON.stringify(input));
		console.log(postInfo);
	}else {
		$('#message').removeClass('d-none');
        $('#errorMSG').text(langs.ENTER_ALL_VAL);
		return;
	}
	$("#loader").removeClass('d-none');
	var infoURL = serviceURL+'getPackageInfo'; 
 	var info = get(infoURL);
 	var storeName = info.data.store;
 	var saveURL = serviceURL+'saveConfiguration';
	var dataArr = {"type": 'store', "data": postInfo};
	// post to server
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
		       	domain = getBaseSiteURL();
		       	$("#head2").addClass('active');
		       	$("#head3").addClass('active');
		       	$('#inputForm').load("wizard/html/admin_form.html");
	       }else{
	       	$('#message').removeClass('d-none');
        	$('#errorMSG').text(langs[response.message]);
	       }
	   	}
	};
	$.ajax(settings).done(function (response) {
		updateLanguage();
	 	setTimeout(function() { 
	    	$("#loader").addClass('d-none');
    	}, 2000);
	});

}

function blockSpace(e){
	var k;
	document.all ? k = e.keyCode : k = e.which;
	return (k != 32);
	}

function createAdmin(){
	$('#message').addClass('d-none');
	var validInput = true;
	var serviceURL = getBaseURL();
 	var langs = get(serviceURL+'getLanguageSelected');
	var input = {};
   var questionID1 = $('#question1').val();
   var questionID2 = $('#question2').val();
	$("input").each(function() {
	   var element = $(this);
	   if (element.val() == "" || !(questionID1) || !(questionID2)) {
	       validInput = false;
	       return;
	   }
	   if (element.attr('type') != 'button') {
	   	 input[element.attr('name')] = element.val();
	   }
	   input['question_id1'] = questionID1;
	   input['question_id2'] = questionID2;
	   input['securAns1'] = unescape(encodeURIComponent(input['securAns1']));
	   input['securAns2'] = unescape(encodeURIComponent(input['securAns2']));
	   var langs = get(serviceURL+'getSelectedLanguageName');
		if(langs != 0){
		  var langName1 = langs.language;
		  var langExt = langName1.split('.').slice(0, -1).join('.');
		  var langSplit = langExt.split("_");
		  var langName = langSplit[1];
		  langName = langName.toLowerCase();
		}else{
		  var langName = 'english';
		}
		input['language_selected'] = langName;
	});
   if ($("input[name=adminPassword]").val() !== $("input[name=password]").val()) {
   	$('#message').removeClass('d-none');
    $('#errorMSG').text(langs.PASSWORD_MISMATCH);
   	return;
   }
   if ($("input[name=adminPassword]").val().length < 8) {
   		$('#message').removeClass('d-none');
    	$('#errorMSG').text(langs.SHORT_PASSWORD);
    	return;
   }
	if (validInput == true) {
		var postInfo = btoa(JSON.stringify(input));
	}else {
		$('#message').removeClass('d-none');
        $('#errorMSG').text(langs.ENTER_ALL_VAL);
		return;
	}
	$("#loader").removeClass('d-none');
	var infoURL = serviceURL+'getPackageInfo'; 
 	var info = get(infoURL);
 	var storeName = info.data.store;
 	var saveURL = serviceURL+'saveConfiguration';
	var dataArr = {"type": 'admin', "data": postInfo};
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
		       	domain = getBaseSiteURL();
            	if (storeName == 'others'){
            		$("body").load(domain+"/toolInfo.html");
			 	} else {
            		$("body").load(domain+"/toolSetup.html");
			 	}
	       }else{
	       	$('#message').removeClass('d-none');
        	$('#errorMSG').text(langs[response.message]);
	       }
	       setTimeout(function() { 
	    	$("#loader").addClass('d-none');
    	}, 2000);
	   	}
	};
	$.ajax(settings).done(function (response) {
      updateLanguage();
	});

}

function preventDupes( select, index ) {
		var serviceURL = getBaseURL();
 		var langs = get(serviceURL+'getLanguageSelected');
	    var options = select.options,
	        len = options.length;
	    while( len-- ) {
	        options[ len ].disabled = false;
	    }
	    select.options[ index ].disabled = true;
	    if( index === select.selectedIndex ) {
	        $('#message').removeClass('d-none');
        	$('#errorMSG').text(langs.SELECT_DIFF_QUESTION);
	        this.selectedIndex = 0;
	    }
	}

 $(document).ready(function() {
 	// Check Server Settings
 	var serviceURL = getBaseURL();
 	var pkgURL = serviceURL+'getPackageInfo'; 
 	var pkgDetails = get(pkgURL);
 	var storeName = pkgDetails.data.store;
 	var storeVersion = pkgDetails.data.store_api_ver;
 	if(storeName == 'magento'){
 		if(storeVersion == '1.X'){
 			storeName = 'magento1x';
 		}else{
 			storeName = 'magento2x';
 		}
 	}
 	if (storeName == 'opencart' || storeName == 'others'){
 		$("#head2").hide();
 	}
 	var pageDetails = get(serviceURL+'checkCurrentStep');
 	var showStep = pageDetails.show_step;
 	var stopAt = pageDetails.stop_at;
 	domain = getBaseSiteURL();
 	switch(stopAt) {
		  case 2:
		  	console.log("/wizard/html/"+storeName+"_form.html");
			$("#inputForm").load("wizard/html/"+storeName+"_form.html");
	    	$("#head2").addClass('active');
		    break;
		  case 3:
	    	$("#head2").addClass('active');
	    	$("#head3").addClass('active');
	    	$("#inputForm").load("wizard/html/admin_form.html");
		    break;
		}
	$("body").on('DOMSubtreeModified', "inputForm", function() {
	    alert('changed');
	});
 	$("#loader").addClass('d-none');
 	
	updateLanguage();
 	// window.stop();
 });