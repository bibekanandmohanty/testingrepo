 /* ============================================================
 * JS related to step two for installation goes here.
 * This will be called in only second page of the installation.
 * This will check server compatibility and folder/file set ups
 * ============================================================ */

 function showServerSettings(part, report, langs){
 	var section = 'serverCheck'+part;
 	if (report.proceed_next) {
 		$("li#"+section).removeClass('error');
 	} 
 	if (report.warning_status) {
 		$("li#"+section).addClass('error');
 	}
 	if (report.report) {
 		var content = '';
 		$( report.report ).each(function( key, val ) {
	 		content += '<h4 class="title" style="font-size: 14px;">'+langs[val.settingName]+'</h4>';
	 		content += '<h5 class="sub-title">Please check <a target="_blank" href="https://imprintnext.freshdesk.com/support/solutions/articles/81000194901-imprintnext-technical-requirement">help DOC</a></h5>';
		});
		$("div#"+section).html(content);
 	}

 }

 function setNextButton(status){
 	if (status.proceed_next) {
 		$("div#alert").hide();
 		$('<input type="button" name="next" class="btn btn-lg btn-success next action-button mt-5" value="Continue" onclick="startSetup()" style="margin-top: 1rem !important;">').insertAfter("div#alert");
 	}
 	if (status.is_warning || status.is_error) {
 		$("div#alert").show();
 	}

 }

 function startSetup(){
 	$("#loader").removeClass('d-none');
 	var serviceURL = getBaseURL();
 	var langs = get(serviceURL+'getLanguageSelected');
 	var apiURL = serviceURL+'extractPackage';
 	var settings = {
		  "url": apiURL,
		  "method": "POST",
		  "headers": {
		    "Content-Type": "application/x-www-form-urlencoded"
		  },
		  success: function (result) {
	  		var response = JSON.parse(result);
	            if (response.proceed_next) {
	            	domain = getBaseSiteURL();
	            	$("body").load(domain+"/storeSetup.html");
	            }else{
	            	$('#message').removeClass('d-none');
            		$('#errorMSG').text(langs[response.message_code]);
					$("#loader").addClass('d-none');
	            }
        	}
		};

		$.ajax(settings).done(function (response) {
		});
 }

$(document).ready(function() {
 	// Check Server Settings
 	var serviceURL = getBaseURL();
 	var apiURL = serviceURL+'checkServerCompatibility';
 	var statusReport = get(apiURL);
 	var langs = get(serviceURL+'getLanguageSelected');
 	var PHPSettings = statusReport.php_settings;
 	var filePermission = statusReport.file_permission;
 	var eCommSeting = statusReport.ecomm_settings;
 	var appSettings = statusReport.apps_settings;

 	showServerSettings('1',PHPSettings, langs);
 	showServerSettings('2', filePermission, langs);
 	showServerSettings('3', eCommSeting, langs);
 	showServerSettings('4', appSettings, langs);

 	setNextButton(statusReport);
 	updateLanguage();
 	$("#loader").addClass('d-none');
 	$("#alert").delegate( "div", "click", function() {
	  location.reload(true);
	});
 	window.stop();
 });