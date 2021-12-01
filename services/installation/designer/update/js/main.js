let baseUrl = window.location.href;
//let baseUrl = "https://dev.imprintnext.io/prestashop/designer/update/";
let selLanguage = {};


let storeDetails = {},
    backupStatus = false,
    uploadedFileData;
    upgradeStatus = false;

$(document).ready(function(){
    authenticateUser();
    loadLanguageFile();
    let reqUrl = baseUrl + "service/index.php?reqmethod=getLicenseKey";
    $.get(reqUrl, function(data, status){
        if(status === "success"){
            getStoreDetails(data);
            getLatestVersion();
        }else{
            console.log("Error in getting license key")
        }
    });
    $("#pageOne").show();
    $("#pageTwo").hide();
    $("#pageThree").hide();
    $("#updateLoader").hide();
    document.getElementById("uploadPackage").disabled = true;
    document.getElementById("applyUpdate").disabled = true;
});

/**
 *  Used to get store details.
 * 
 * @param {object} data - license data
 */
function getStoreDetails(data) {
    if(data){
        let licenseData = JSON.parse(data);
        if(licenseData.status){
            const decryptKey = this.getrevdata(licenseData.licence_key).split('|');
            storeDetails.storeName = decryptKey[0];
            storeDetails.storeVersion = decryptKey[1];
            storeDetails.adminVersion = decryptKey[2];
            storeDetails.subscriptionType = decryptKey[3];
            storeDetails.purchaseDate = decryptKey[5];
            document.getElementById("version").innerHTML = storeDetails.adminVersion;
            document.getElementById("subscriptionType").innerHTML = storeDetails.subscriptionType;
            document.getElementById("ecomPlatform").innerHTML = storeDetails.storeName + ' ' + storeDetails.storeVersion;
        }else{
            console.log("Error in getting license key");
        }
    }
}

function xor_encrypt(str, key) {
    let xor = '';
    let tmp;
    for (let i = 0; i < str.length; ++i) {
        tmp = str[i];
        for (let j = 0; j < key.length; ++j) {
            tmp = String.fromCharCode(tmp.charCodeAt(0) ^ key.charCodeAt(j));
        }
        xor += tmp;
    }
    return xor;
}

function getrevdata(str) {
    let value = '';
    try {
        value = atob(str);
        value = this.xor_encrypt(value, '*P&46X-(5u)>#12i06N%');
    } catch (err) {
        console.log(err);
    }
    return value;
}

/**
 *  After back up taken move to next step.
 */
function onBackupTaken(event) {
    authenticateUser();
    backupStatus = event.target.checked;
    let element = document.getElementById("stepTwo");
    let elementThree = document.getElementById("stepThree");
    if(backupStatus){
        element.classList.add("is-done");
        document.getElementById("uploadPackage").disabled = false;
    } else {
        element.classList.remove("is-done");
        elementThree.classList.remove("is-done");
        document.getElementById("uploadPackage").disabled = true;
        document.getElementById("applyUpdate").disabled = true;
        document.getElementById("fileName").innerHTML = '';
        document.getElementById("fileError").innerHTML = '';
        document.getElementById("failedUpdate").innerHTML = '';
        uploadedFileData = '';
        $("#uploadPackage").val('');
    }
}

/**
 *  This function is called when package uploaded.
 */
function onUploadPackage(event) {
    authenticateUser();
    if (event.target.files.length > 0) {
        tempFileData = event.target.files[0];
        let fileExt = tempFileData.name.split('.').pop();
        let element = document.getElementById("stepThree");
        if(fileExt === "zip"){
            uploadedFileData = tempFileData;
            element.classList.add("is-done");
            document.getElementById("fileName").innerHTML = uploadedFileData.name;
            document.getElementById("fileError").innerHTML = '';
            document.getElementById("applyUpdate").disabled = false;
        } else {
            uploadedFileData = '';
            element.classList.remove("is-done");
            document.getElementById("fileName").innerHTML = '';
            document.getElementById("fileError").innerHTML = selLanguage && selLanguage['invalid-file'] ? selLanguage['invalid-file'] : 'Invalid file.';
            document.getElementById("applyUpdate").disabled = true;
        }
    }
}

/**
 *  This function is called when apply button is clicked.
 */
function onApplyUpdate() {
    authenticateUser();
    if(uploadedFileData){
        $("#updateLoader").show();
        let reqUrls = baseUrl + "service/index.php?reqmethod=updatePackage";
        let fd = new FormData();
        fd.append('zip_file', uploadedFileData);
        fd.append('current_version', storeDetails.adminVersion);
        $.ajax({
            url: reqUrls,
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                let tempResp = JSON.parse(res);
                if(tempResp.status){
                    upgradeStatus = true;
                    $("#pageOne").hide();
                    $("#pageTwo").show();
                    document.getElementById("failedUpdate").innerHTML = '';
                    showUpdatedVersion();
                } else {
                    upgradeStatus = false;
                    $("#pageOne").show();
                    $("#pageTwo").hide();
                    document.getElementById("failedUpdate").innerHTML = selLanguage && selLanguage['failed-update'] ? selLanguage['failed-update'] : 'Failed to update.';
                }
                $("#updateLoader").hide();
            }
        });
    }
}

/**
 *  This function is called to get latest version
 */
function getLatestVersion() {
    let reqestedUrl = baseUrl + "service/index.php?reqmethod=getLatestVersion&current_version=" + storeDetails.adminVersion;
    $.get(reqestedUrl, function(data, status){
        if(status === "success"){
            let tempResponse = JSON.parse(data);
            if(tempResponse.status){
                if(tempResponse.data && tempResponse.data.next_version){
                    document.getElementById("latestVersion").innerHTML = tempResponse.data.next_version;
                    compareVersion(storeDetails.adminVersion, tempResponse.data.next_version);
                }
            } else {
                console.log("Error in getting latest version");
            }
        }else{
            console.log("Error in getting latest version");
        }
    });
}

/**
 *  Show latest version after upadte version
 */
function showUpdatedVersion() {
    let reqUrl = baseUrl + "service/index.php?reqmethod=getLicenseKey";
    $.get(reqUrl, function(data, status){
        if(status === "success"){
            getStoreDetails(data);
        }else{
            console.log("Error in getting license key")
        }
    });
}

/**
 *  This function is used to authenticate user
 */
function authenticateUser() {
    let roleId = localStorage.getItem('role_id');
    if(roleId !== '1') {
        localStorage.clear();
        let tempBaseUrl = baseUrl.replace("update/", "");
        let adminUrl = tempBaseUrl + 'admin';
        window.open(adminUrl, "_self");
    }
}

/**
 *  Used for compare version
 *  @param currVersion: curent version
 *  @param latestVersion: latest version
 */
function compareVersion(currVersion, latestVersion){
    let currVerParts = currVersion.split('.'),
        latVerParts = latestVersion.split('.'),
        tempParts,
        isNewVersion = false;
    if(currVerParts && currVerParts.length && latVerParts && latVerParts.length){
      if(currVerParts.length < latVerParts.length){
        tempParts = currVerParts;
      } else {
        tempParts = latVerParts;
      }
      for(let p = 0; p < tempParts.length; p++){
        if(parseInt(latVerParts[p]) > parseInt(currVerParts[p])){
            isNewVersion = true;
          break;
        }
      }
      if(!isNewVersion){
        $("#pageOne").hide();
        $("#pageTwo").hide();
        $("#pageThree").show();
      }
    }
}

/**
 *  This function is used to load selected language file.
 */
function loadLanguageFile() {
    let defaultLanguage = localStorage.getItem('defaultLanguage') ? localStorage.getItem('defaultLanguage') : 'english';
    let languageUrl = baseUrl + "service/index.php?reqmethod=getLanguages";
    $.get(languageUrl, function(data, status){
        if(status === "success"){
            let languageData = JSON.parse(data);
            if(languageData.status){
                if(languageData.data){
                    let defaultLanguageData = languageData.data.filter(function (el){
                        return el.name.toLowerCase() === defaultLanguage;
                    })[0];
                    if(defaultLanguageData){
                        getLanguageFileContent(defaultLanguageData.file_name);
                    }
                }
            } else {
                console.log("Error in getting language.");
            }
        }else{
            console.log("Error in getting language.");
        }
    });
}

/**
 *  This function is used to get language file content.
 *  @param filePath: file path
 */
function getLanguageFileContent(filePath) {
    $.get(filePath,{ headers: {'Content-Type': 'application/json'}}).then((res)=>{
        selLanguage = res['update-package'];
        if(selLanguage){
            $("#versionLabel").html(selLanguage['current-version']);
            $("#subscriptionLabel").html(selLanguage['subsciption-type']);
            $("#ecomPlatformLabel").html(selLanguage['e-commerce-platform']);
            $("#takeBackupLabel").html(selLanguage['take-backup']);
            $("#takeBackupDescLabel").html(selLanguage['take-server-backup']);
            $("#backupTakenLabel").html(selLanguage['backup-taken']);
            $("#updatePackageLabel").html(selLanguage['upload-update-package']);
            $("#downLoadLabel").html(selLanguage['download-upadte-package']);
            $("#licensePortalLabel").html(selLanguage['license-portal']);
            $("#updateFileLabel").html(selLanguage['update-file']);
            $("#uploadUpdateFileLabel").html(selLanguage['upload-update-file']);
            $("#finalStepLabel").html(selLanguage['final-step']);
            $("#clickApplyLabel").html(selLanguage['click-apply']);
            $("#processTimeLabel").html(selLanguage['proccess-time']);
            $("#applyUpdateLabel").html(selLanguage['apply-update']);
            $("#successHeadingLabel").html(selLanguage['message']);
            $("#successDescLabel").html(selLanguage['success-message']);
            $("#latestVerHeadingLabel").html(selLanguage['message']);
            $("#latestVerDescLabel").html(selLanguage['latest-version-message']);
        }
    }).catch((err)=>{
        console.log("Error in getting language file content.");
    });
}



