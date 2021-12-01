 /* ============================================================
 * common js functionalities for installation goes here.
 * This will be called in every page of the installation.
 * This will set the header info and check current page status onload.
 * ============================================================ */
 function get(url){
 	var response = $.ajax({
	    url: url, //the page containing php script
	    type: 'get', //request type,
 		async: false,
 		crossDomain:true,
	    success:function(result){
	   }
	 });
 	return JSON.parse(response.responseText);
 }

 function getBaseURL(){
 	var current = window.location.href;
 	var baseURL = current.replace('index.html','');
 	var servicePath = 'service/index.php?reqmethod=';
 	return baseURL+servicePath;
 }

 function getDomain(){
 	var current = window.location.href;
 	var baseURL = current.substr(0, current.indexOf("imprintnext/install"));
 	return baseURL;
 }
 function getBaseSiteURL(){
 	var current = window.location.href;
 	var baseURL = current.substr(0, current.indexOf("install")+7);
 	return baseURL;
 }
 function showPage(showStep, stopAt, storeName){
 	domain = getBaseSiteURL();
 	switch(showStep) {
	  case 1:
    	// $("body").load(domain+"/index.html");
	    break;
	  case 2:
    	$("body").load(domain+"/serverSetup.html");
	    break;
	  case 3:
    	$("body").load(domain+"/storeSetup.html");
	    break;
	  case 4:
    	$("body").load(domain+"/toolSetup.html");
	    break;
	  case 5:
    	$("body").load(domain+"/toolInfo.html");
	    break;
	}
 	if (showStep == 1) {
 		$("#loader").addClass('d-none');
 	}
 }

 function updateLanguage(){
 	var languageList = document.getElementById("langList");
 	if ( languageList != null && languageList.options.length >0) {
	   var language = languageList.options[languageList.selectedIndex].value;
 		var current = window.location.href;
 		var baseURL = current.replace('index.html','');
 		var pageDetails = get(baseURL+"languages/"+language);
 	}else{
	  	var apiURL = getBaseURL();
	  	var pageDetails = get(apiURL+'getLanguageSelected');
 	}
  	$("[data-lang]").each(function(){
	    var langKey = $(this).data('lang');
	    if ($(this).is("input")) {
	    	$(this).val(pageDetails[langKey]);
		}else{
			$(this).text(pageDetails[langKey]);
		}
	});
 }

 $(document).ready(function() {
 	// show package information
 	var serviceURL = getBaseURL();
 	var pageDetails = get(serviceURL+'checkCurrentStep');
 	var showStep = pageDetails.show_step;
 	var stopAt = pageDetails.stop_at;
 	var pkgURL = serviceURL+'getPackageInfo'; 
 	var pkgDetails = get(pkgURL);
 	var storeName = pkgDetails.data.store;
 	showPage(showStep, stopAt, storeName);
 	updateLanguage();
 	var storeVer = pkgDetails.data.store_version;
 	var xeVer = pkgDetails.data.inkXE_version;
 	var domain = pkgDetails.data.registered_domain;
 	var thisDomain = getDomain();
 	$(".storeName").text(storeName);
 	$(".inkVer").after( "<span>  "+xeVer+"</span>" );
 	$(".storeVer").after( "<span>  "+storeVer+"</span>" );
 	$("input#domain").val(domain);
 	$(".domain").text(thisDomain);
 	var storeLogo = $(".storeLogo").attr('src') + storeName + '.svg';
 	$(".storeLogo").attr("src", storeLogo);

 });