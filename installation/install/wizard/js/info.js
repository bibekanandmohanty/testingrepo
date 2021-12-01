 /* ============================================================
 * JS related to final step for installation goes here.
 * This will be called in only last page of the installation.
 * This will get inkXE admin and designer link for the customer.
 * ============================================================ */ 
 $(document).ready(function() {
 	var serviceURL = getBaseURL();
 	var toolURLs = get(serviceURL+"getXEDetails");
 	$("#xeAdmin").attr('href', toolURLs.admin_url);
 	$("#xeAdmin").attr('target', "_blank");
 	$("#xeTool").attr('href', toolURLs.tool_url);
 	$("#xeTool").attr('target', "_blank");
 	updateLanguage();
 	window.stop();
 });