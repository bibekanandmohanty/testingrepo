var BASE_URL = 'https://dev.imprintnext.io/designer/',
    STORE_URL = 'https://dev.imprintnext.io/',
    SECRET_KEY = "SgUkXp2s5v8y/B?E(H+MbQeThWmYq3t6w9z^C&F)J@NcRfUjXn2r4u7x!A%D*G-K",
    LICENCE_KEY = "Jx8fEx8dHRUCExUMQl4IDEFeQV5ADDUDAxUeBBkRHAwZHQACGR4EHhUIBF4ZH1wUFQZeGR0AAhkeBB4VCAReGR9cHB8TERwYHwMEXBQVBkJeGR0AAhkeBB4VCAReGR9cBBUDBBkeF14ZHQACGR4EHhUIBF4ZH1wEFR0AXhkdAAIZHgQeFQgEXhkfXAQVAwQZHhdeGR0AAhkeBB4VCAReGR9cBBUDBBkeF14ZHQACGR4EHhUIBF4ZH1wEFQMEXkFCQ14THx1cBxNDXhkeGwgVXhMfHVwEFQMEGR4XXhkdAAIZHgQeFQgEXhkfDEJAQkBdQEhdQEgMSUZDDAQVER1cEQJcQzRcAwQZExsVAlwVHRICHxkUFQIJXBUeFwIRBhVcAQUfBBEEGR8eXBMRBBEcHxcFFVwfHhwZHhU7GR8DGw==",
    RVN = 98,
    LANG = 'en',
    DPI = 96,
    FORCE_RETAIN_DESIGN = true,
    STORE = 'Woocommerce',
    SHOW_TOOL_BAR = false,
    COLOR_NO = 6,
    CONVERTED_SVG_COLOR_CHANGE = false,
    DEFAULT_EDIT_TEMPLATE = false;

// --------------------Multi store starts------------------
let tempUrl="";tempUrl="Shopify"!==STORE&&"Others"!==STORE&&"Hosted"!==STORE?window.parent.location.href:window.location.href;let currentUrl=new URL(tempUrl),storeId=currentUrl.searchParams.get("store_id");if(storeId&&(storeId=parseInt(storeId))>1){let r=new URL(BASE_URL),e=new URL(STORE_URL);BASE_URL=BASE_URL.replace(r.origin,currentUrl.origin),STORE_URL=STORE_URL.replace(e.origin,currentUrl.origin)}
//---------------------Multi store ends--------------------


// --------------- load product data starts ---------------
function loadProdData(){try{var t,i,a,d,o,e;window.in_isapicall="1";const r=new URLSearchParams(window.location.search);if(r.get("id"))t=r.get("id"),i=r.get("vid"),a=r.get("option_id"),d=r.get("store_id")?r.get("store_id"):1,o=r.get("temp"),e=r.get("quot");else{const n=new URLSearchParams(window.parent.location.search);t=n.get("id"),i=n.get("vid"),a=n.get("option_id"),d=n.get("store_id")?n.get("store_id"):1,o=n.get("temp"),e=n.get("quot")}var n="";t&&i?(n=a?o||e?BASE_URL+"api/v1/product-details/"+t+"?variant_id="+i+"&option_id="+a+"&store_id="+d+"&source=admin":BASE_URL+"api/v1/product-details/"+t+"?variant_id="+i+"&option_id="+a+"&store_id="+d:o||e?BASE_URL+"api/v1/product-details/"+t+"?variant_id="+i+"&store_id="+d+"&source=admin":BASE_URL+"api/v1/product-details/"+t+"?variant_id="+i+"&store_id="+d,fetch(n).then(t=>t.json()).then(t=>{t.status?window.in_prodData=JSON.stringify(t.data):window.in_prodData=JSON.stringify({})}).catch(t=>{window.in_prodData=JSON.stringify({})})):window.in_prodData=JSON.stringify({})}catch(t){}}loadProdData();
// --------------- load product data ends ---------------
