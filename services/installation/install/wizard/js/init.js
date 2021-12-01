 /* ============================================================
 * JS related to step one for installation goes here.
 * This will be called in only first page of the installation.
 * This will set the langiage and designer folder set by installer
 * ============================================================ */
 function setDesignerPath(part){
 	if (part == 2) {
		$('#overview-1').addClass('d-none');
		$('#overview-2').removeClass('d-none');
		localStorage.setItem('language', $('#langList').children("option:selected").val());
		updateLanguage()
 	}else{
 		$('#overview-1').removeClass('d-none');
		$('#overview-2').addClass('d-none');
 	}
 	// console.log(localStorage.getItem('language'));
 }

 function blockSplChar(e){
	var k;
	document.all ? k = e.keyCode : k = e.which;
	return ((k > 64 && k < 91) || (k > 96 && k < 123) || k == 8 || k == 95 || k == 45 || (k >= 48 && k <= 57));
	}

 function goToServerSettings(){
 	$("#loader").removeClass('d-none');
 	var serviceURL = getBaseURL();
  	var langs = get(serviceURL+'getLanguageSelected');
 	var saveURL = serviceURL+'saveInstallationSettings';
 	var thisDomain = window.location.hostname;
 	if (thisDomain.includes($('#domain').val())) {
	 	if (localStorage.getItem("language") != null && localStorage.getItem("language") != 'undefined') {
		  thisLanguageFile = localStorage.getItem("language");
		  folderName = $('#xeFolder').val();
		  if ($('#xeFolder').val() == '') {
		  		$('#xeFolder').val("designer");
		  }
		  var dataArr = {"lang": thisLanguageFile, "root": folderName};
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
	            	$("body").load(domain+"/serverSetup.html");
	            }else{
	            	$('#message').removeClass('d-none');
	            	$('#errorMSG').text(langs[response.message_code]);
	            	$("#loader").addClass('d-none');
	            }
	        	}
			};

			$.ajax(settings).done(function (response) {
			});
		}else setDesignerPath(1);
 	}else{
 		$("#loader").addClass('d-none');
 		$('#message').removeClass('d-none');
	    $('#errorMSG').text(langs['DOMAIN_MISMATCH']);	
 	}
 }
 $(document).ready(function() {
 	// show pacjage information
 	if (localStorage.getItem("language") != null && localStorage.getItem("language") != 'undefined') {
	  setDesignerPath(2);
	}
 	// List available languages
 	var serviceURL = getBaseURL();
 	var languageURL = serviceURL+'getLanguages';
 	var langList = get(languageURL);
 	var allLang = langList.languages;
 	$( allLang ).each(function( key, val ) {
 		if (val.name == 'English (en)') {
 			$('#langList').append($("<option selected></option>").attr("value",val.file).text(val.name));
 		}else
 			$('#langList').append($("<option></option>").attr("value",val.file).text(val.name));
	});
 });