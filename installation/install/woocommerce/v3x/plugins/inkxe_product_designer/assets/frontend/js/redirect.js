jQuery(document).ready(function(){
    var screenWidth = (window.innerWidth > 0) ? window.innerWidth : screen.width;
    var url      = window.location.href; 
    var newUrl = url.replace("product-designer/", "xetool/index.html");
    if(screenWidth < 1024){
        window.location = newUrl;
    }
});