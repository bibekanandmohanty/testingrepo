(this["webpackJsonpinkxe10-designer"]=this["webpackJsonpinkxe10-designer"]||[]).push([[7],{944:function(t,e,a){"use strict";a.r(e);var n=a(7),i=a(10),r=a(11),o=a(13),l=a(16),s=a(15),c=a(0),d=a.n(c),p=a(24),u=a(120),h=a.n(u),g=a(19),m=a.n(g),b=a(877),f=a(880),k=a.n(f),v=a(156),C=a(111),S=function(t){function e(){if(t.chooseFitChecked)return d.a.createElement(c.Fragment,null,d.a.createElement("hr",null),d.a.createElement("div",{className:"d-flex justify-content-between align-items-center mt-3"},d.a.createElement("h3",{className:"sectitle"},t.language.background.chooseFit)),d.a.createElement("div",{className:"btn-group custom-btn-group w-100 mt-2"},t.fitOptions.map((function(e){return d.a.createElement("div",{key:e.name,className:"btn btn-sm btn-default"+(e.isActive?" active":""),onClick:function(){return t.onFitOptSelected(e.name)}},e.label)}))),function(){if(t.fitOptions[0].isActive)return d.a.createElement(c.Fragment,null,d.a.createElement("div",{className:"custom-control-group mt-3 mb-1"},d.a.createElement("div",{className:"custom-control custom-radio custom-control-inline"},d.a.createElement("input",{className:"custom-control-input",id:"customRadioThumbView",type:"radio",value:t.fitOptData.tileData.isGrid,checked:1===t.fitOptData.tileData.isGrid,onChange:function(){return t.switchStyle(1)}}),d.a.createElement("label",{className:"custom-control-label",htmlFor:"customRadioThumbView"},t.language.background.brick)),d.a.createElement("div",{className:"custom-control custom-radio custom-control-inline"},d.a.createElement("input",{className:"custom-control-input",id:"customRadioListView",type:"radio",value:t.fitOptData.tileData.isGrid,checked:0===t.fitOptData.tileData.isGrid,onChange:function(){return t.switchStyle(0)}}),d.a.createElement("label",{className:"custom-control-label",htmlFor:"customRadioListView"},t.language.background.tile))),d.a.createElement("div",{className:"listView noMinH"},d.a.createElement("div",{className:"listView-wrap"},d.a.createElement("div",{className:"listView-elements"},d.a.createElement("div",{className:"list-options"},d.a.createElement("div",{className:"list-options-left"},t.language.main.spacing),d.a.createElement("div",{className:"text-options-right"},d.a.createElement(k.a,{minValue:0,maxValue:10,step:1,value:t.fitOptData.tileData.spacing,onChange:function(e){return t.spacingValChanged(e)}}),d.a.createElement(v.DebounceInput,{className:"output",minLength:1,value:t.fitOptData.tileData.spacing?t.fitOptData.tileData.spacing:"0",onChange:t.validateSpacingVal,pattern:"[0-9]*"})))),d.a.createElement("div",{className:"listView-elements"},d.a.createElement("div",{className:"list-options"},d.a.createElement("div",{className:"list-options-left"},t.language.main.size),d.a.createElement("div",{className:"text-options-right"},d.a.createElement(k.a,{minValue:1,mmaxValueax:10,step:1,value:t.fitOptData.tileData.zoom,onChange:function(e){return t.zoomValChanged(e)}}),d.a.createElement(v.DebounceInput,{className:"output",minLength:1,value:t.fitOptData.tileData.zoom?t.fitOptData.tileData.zoom:"0",onChange:t.validateZoomVal,pattern:"[0-9]*"})))))))}(),d.a.createElement("div",{className:"buttonGroup d-flex mt-4"},d.a.createElement("button",{className:"btn btn-sm btn-dark btn-block mr-1",onClick:function(){return t.onCancelButtonClicked()}},t.language.main.cancel),d.a.createElement("button",{className:"btn btn-sm btn-success btn-block m-0 ml-1",onClick:function(){return t.onDoneButtonClicked()}},t.language.main.done)))}return d.a.createElement(c.Fragment,null,d.a.createElement("ul",{className:"nav nav-pills custom-pills mt-3 mb-3",id:"pills-tab",role:"tablist"},t.tabList&&t.tabList.map((function(e){return d.a.createElement("li",{className:"nav-item",key:e.type,onClick:function(){return t.onTabSelected(e.type)}},d.a.createElement("span",{className:"nav-link"+(e.isActive?" active":"")},t.language.background[e.name]))}))),0===t.selectedTabType?d.a.createElement("div",{className:"mob-scroll color"},d.a.createElement("div",{className:"thumbView bg-color-background scrollbar"},t.error?d.a.createElement("div",null,"Error! ",t.error.message):t.loading||t.catLoading?d.a.createElement(c.Fragment,null,d.a.createElement("div",{className:"advanced-search d-none d-md-block d-lg-block d-xl-block",key:"BackgroundView"},d.a.createElement("div",{className:"thumbView bg-color-background scrollbar",style:{overflow:"hidden"}},d.a.createElement("div",{className:"bg-wrap"},d.a.createElement("div",{className:"bg-wrap-colors"},d.a.createElement("div",{className:"thumbView-wrap"},[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20].map((function(t){return d.a.createElement("div",{className:"thumbView-elements",key:t},d.a.createElement(m.a,{height:30,width:30}))}))))))),d.a.createElement("div",{className:"advanced-search d-block d-sm-block d-md-none d-lg-none d-xl-none",key:"BackgroundViewMob"},d.a.createElement("div",{className:"multiSelect"},d.a.createElement(m.a,{height:34,width:350})),d.a.createElement("ul",{className:"nav nav-pills custom-pills mt-3 mb-3",id:"pills-tab",role:"tablist",style:{width:"initial"}},d.a.createElement("li",{className:"nav-item"},d.a.createElement("span",{className:"nav-link active"},d.a.createElement(m.a,{height:8,width:100}))),d.a.createElement("li",{className:"nav-item"},d.a.createElement("span",{className:"nav-link"},d.a.createElement(m.a,{height:8,width:100})))),d.a.createElement("div",{className:"thumbView bg-color-background scrollbar",style:{overflow:"hidden"}},d.a.createElement("div",{className:"bg-wrap"},d.a.createElement("div",{className:"bg-wrap-colors"},d.a.createElement("div",{className:"thumbView-wrap"},[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20].map((function(t){return d.a.createElement("div",{className:"thumbView-elements",key:t},d.a.createElement(m.a,{height:61,width:61}))})))))))):t.backgrounds&&t.backgrounds.length?d.a.createElement(h.a,{className:"bg-wrap-colors",pageStart:0,loadMore:function(){return t.loadMoreBackground()},hasMore:t.hasMore,useWindow:!1},d.a.createElement("div",{className:"thumbView-wrap"},t.backgrounds.map((function(e,a){return d.a.createElement("div",{key:e.xe_id,className:"thumbView-elements"+(t.selBackgroundPref.selBackground===e.xe_id?" active":"")},d.a.createElement("div",{className:"color-btn",id:"background-"+a},function(){switch(e.type){case 0:return d.a.createElement("figure",{className:"imageWrap-box",style:{backgroundColor:e.value},onClick:function(){return t.addDesign(e)}},d.a.createElement("img",{src:e.thumbnail,alt:""}));case 1:return d.a.createElement("figure",{className:"imageWrap-box",onClick:function(){return t.addDesign(e)}},d.a.createElement("img",{src:e.thumbnail,alt:""}));default:return null}}()),!1===t.isMobileView&&d.a.createElement(b.a,{placement:"right",target:"background-"+a},e.name))})))):t.backgrounds&&0===t.backgrounds.length?d.a.createElement("div",{className:"alert alert-warning small-txt tip"},d.a.createElement("i",{className:"alert-icon"}),d.a.createElement("span",{className:"alert-txt"},t.language.background.nodataMsg)):void 0),function(){if(t.backgrounds&&t.backgrounds.length&&t.hasMore)return d.a.createElement("div",{className:"info-text"},d.a.createElement("i",{className:"nxi nxi-down"}),t.language.main.scrollToLoadMore)}(),function(){if(!t.loading&&!t.catLoading&&!1===t.showColorPalette)return d.a.createElement(C.a,{onColorSelect:t.onColorSelect,onColorHover:t.onColorHover,showColorPalette:t.showColorPalette,selectedColor:t.selectedColor})}(),d.a.createElement("div",{className:t.isTempReplace?"buttonGroup d-flex mt-4":"buttonGroup d-flex mt-4 hidden"},d.a.createElement("button",{className:"btn btn-sm btn-dark btn-block mr-1",onClick:function(){return t.onCancelButtonClicked()}},t.language.main.cancel),d.a.createElement("button",{className:"btn btn-sm btn-success btn-block m-0 ml-1",onClick:function(){return t.onDoneButtonClicked()}},t.language.main.done))):1===t.selectedTabType?d.a.createElement(c.Fragment,null,d.a.createElement("div",{className:"mob-scroll pattern"},t.selectedPrintProfileData&&t.selectedPrintProfileData.misc_data&&1===t.selectedPrintProfileData.misc_data.allow_user_uplaod_bg&&d.a.createElement(c.Fragment,null,d.a.createElement("div",{className:"custom-file custom-file-link"},d.a.createElement("input",{type:"file",className:"custom-file-input",id:"inputGroupFile01","aria-describedby":"inputGroupFileAddon01",accept:t.fileAccept,onChange:function(e){return t.onBackgroundImgChange(e)}}),d.a.createElement("label",{className:"custom-file-label",htmlFor:"inputGroupFile01"},d.a.createElement("i",{className:"nxi nxi-upload"})," ",d.a.createElement("span",{className:"txt"},t.language.background.uploadPattern))),d.a.createElement("h6",{className:"small-txt mt-2"},t.language.background.acceptedFileTypes),d.a.createElement("div",{className:"upload-info mb-3"},t.allowedFileFormat&&t.allowedFileFormat.map((function(t,e){return d.a.createElement("span",{key:e},t)}))),d.a.createElement("div",{className:"w-100 orBox mt-3"},d.a.createElement("span",null,t.language.main.chooseFromBelow))),d.a.createElement("div",{className:"thumbView bg-color-pattern scrollbar"},t.patternError?d.a.createElement("div",null,"Error! ",t.patternError.message):t.patternLoading?d.a.createElement("div",{key:"BackgroundView",className:"thumbView-wrap"},d.a.createElement(m.a,{height:34,width:34,count:12})):t.patterns&&t.patterns.length?d.a.createElement(h.a,{className:"bg-wrap-colors",pageStart:0,loadMore:function(){return t.loadMorePatterns()},hasMore:t.hasMorePattern,useWindow:!1},d.a.createElement("div",{className:"thumbView-wrap"},t.patterns.map((function(e,a){return d.a.createElement("div",{key:e.xe_id,className:"thumbView-elements"+(t.selBackgroundPref.selBackground===e.xe_id?" active":"")},d.a.createElement("div",{className:"color-btn",id:"pattern-"+a},function(){switch(e.type){case 0:return d.a.createElement("figure",{className:"imageWrap-box",style:{backgroundColor:e.value},onClick:function(){return t.addDesign(e)}},d.a.createElement("img",{src:e.thumbnail,alt:""}));case 1:return d.a.createElement("figure",{className:"imageWrap-box",onClick:function(){return t.addDesign(e)}},d.a.createElement("img",{src:e.thumbnail,alt:""}));default:return null}}()),!1===t.isMobileView&&d.a.createElement(b.a,{placement:"right",target:"pattern-"+a},e.name))})))):t.patterns&&0===t.patterns.length?d.a.createElement("div",{className:"alert alert-warning small-txt tip"},d.a.createElement("i",{className:"alert-icon"}),d.a.createElement("span",{className:"alert-txt"},t.language.background.nodataMsg)):void 0),function(){if(t.patterns&&t.patterns.length&&t.hasMorePattern&&t.totalPatterns-t.patterns.length>0)return d.a.createElement("div",{className:"info-text"},d.a.createElement("i",{className:"nxi nxi-down"})," ",t.language.main.scrollToLoadMore)}(),e())):void 0)},y=a(3),E=a(65),N=a(9),w=a.n(N),P=a(20),O="backgroundExt",D=function(t){return Object(P.a)(w.a.mark((function e(){var a,n,i,r,o,l,s,c,d;return w.a.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:d=function(){var t,e,a,n,i;i=o.getCurrentLayerElems().filter((function(t){return"background"===t.id}))[0],s="",c=!1,(null===(t=i)||void 0===t||null===(e=t.prefs)||void 0===e?void 0:e.objectTitle)&&(s=i.prefs.objectTitle),(null===(a=i)||void 0===a||null===(n=a.prefs)||void 0===n?void 0:n.lock)&&(c=i.prefs.lock.delete)},a=t.$,n=t.editor,i=t.NS,o=(r=n).canvas,c=!1,r.addBackgroundPattern=function(t,e,n,r,p,u,h,g){var m,b,f,k,v,C,S,y;(d(),(h||"h"===n[0])&&(l=h),"add"===r)&&function(){var r,d=function(t,e){r.prefs={type:"background",backgroundType:l?l.type:1,fitOptData:t,selBackground:l?l.xe_id:0,price:l?l.price:0,applyedBackground:l||e,lock:{delete:c||!1,edit:!1},objectTitle:s}},h=a(o.getLayerStage()).children("g"),N="bg_area_"+Number(o.getLayerStage().getAttribute("id").split("_")[1]);if(h.length&&u){var w=h[0].getElementsByTagName("image")[0];w&&(n=w.getAttribute("xlink:href"))}for(m=0;m<h.length;m++)h[m]&&h[m].getAttribute("type")===N&&function(){r=h[m],a(document.getElementById("bound_"+N.split("_")[2])).children("rect").length?(b=a(document.getElementById("bound_"+N.split("_")[2])).children("rect"),f=a(document.getElementById("bound_"+N.split("_")[2])).children("rect")):(b=a(document.getElementById("bound_"+N.split("_")[2])).children("path"),f=a(document.getElementById("bound_"+N.split("_")[2])).children("path"));var t={x:f[0].getAttribute("x"),y:f[0].getAttribute("y")};if(f[0].getAttribute("transform")&&"path"!==f[0].nodeName)if("path"===f[0].nodeName)t.width=f[0].getAttribute("width"),t.height=f[0].getAttribute("height");else{t.width=f[0].getAttribute("height"),t.height=f[0].getAttribute("width");var l=(Number(t.height)-Number(t.width))/2,s=(Number(t.width)-Number(t.height))/2;b="translate("+l+","+s+")",r.setAttribute("transform",b)}else t.width=f[0].getAttribute("width"),t.height=f[0].getAttribute("height");if(r.setAttribute("x",t.x),r.setAttribute("y",t.y),r.setAttribute("width",t.width),r.setAttribute("height",t.height),a(r).empty(),r.prefs=null,""!==e){d();var c=document.createElementNS(i.SVG,"rect");c.setAttributeNS(null,"id",o.getNextId()),c.setAttributeNS(null,"x",t.x),c.setAttributeNS(null,"y",t.y),c.setAttributeNS(null,"width",t.width),c.setAttributeNS(null,"height",t.height),c.setAttributeNS(null,"fill",e),c.setAttributeNS(null,"preserveAspectRatio","none"),c.setAttributeNS(null,"style","color-interpolation-filters:sRGB;"),r.appendChild(c)}else if(""!==n){g&&g(!0),d(p,n);var u="none";u="Stretch"===p.selectedOpt?"":"none",C=0,S=0,(y=new Image).onload=function(){C=y.width/30,k=y.width/C,v=y.height/C;var e=Math.ceil(t.width/k),a=Math.ceil(t.height/v),l=t.width/e,s=t.height/a,c=2*s,d=l;p.tileData.zoom&&(c=parseFloat(c)+2*parseFloat(p.tileData.zoom*s/2),d=parseFloat(d)+parseInt(p.tileData.zoom*l/2),l=parseFloat(l)+parseFloat(p.tileData.zoom*l/2),s=parseFloat(s)+parseFloat(p.tileData.zoom*s/2)),p&&p.tileData&&(S=0===p.tileData.spacing?-1:p.tileData.spacing);var h=[{x:0+parseInt(S),y:0+parseInt(S),width:E(d-2*parseInt(S)),height:E(c/2-2*parseInt(S))},{x:d/2+parseInt(S),y:c/2+parseInt(S),width:E(d-2*parseInt(S)),height:E(c/2-2*parseInt(S))},{x:-d/2+parseInt(S),y:c/2+parseInt(S),width:E(d-2*parseInt(S)),height:E(c/2-2*parseInt(S))}];if("Tile"===p.selectedOpt)0===p.tileData.isGrid&&function(){var t=document.createElementNS(i.SVG,"pattern");t.setAttributeNS(null,"id",o.getNextId()),t.setAttributeNS(null,"x",p.tileData.offsetX),t.setAttributeNS(null,"y",p.tileData.offsetY),t.setAttributeNS(null,"width",l),t.setAttributeNS(null,"height",s),t.setAttributeNS(null,"patternUnits","userSpaceOnUse"),t.setAttributeNS(null,"preserveAspectRatio","none");var e=document.createElementNS(i.SVG,"image");o.assignAttributes(e,{id:o.getNextId(),width:h[0].width,height:h[0].height,preserveAspectRatio:"none"}),o.setHref(e,n),t.appendChild(e),r.append(t)}(),1===p.tileData.isGrid&&function(){var t=document.createElementNS(i.SVG,"pattern");t.setAttributeNS(null,"id",o.getNextId()),t.setAttributeNS(null,"x",p.tileData.offsetX),t.setAttributeNS(null,"y",p.tileData.offsetY),t.setAttributeNS(null,"width",d),t.setAttributeNS(null,"height",c),t.setAttributeNS(null,"patternUnits","userSpaceOnUse"),t.setAttributeNS(null,"preserveAspectRatio","none");for(var e=0;e<h.length;e++){var a=document.createElementNS(i.SVG,"image");o.assignAttributes(a,{id:o.getNextId(),x:h[e].x,y:h[e].y,width:h[e].width,height:h[e].height,preserveAspectRatio:"none"}),o.setHref(a,n),t.appendChild(a)}r.append(t)}(),function(){var e=r.getElementsByTagName("pattern")[0].id,a=document.createElementNS(i.SVG,"rect");a.setAttributeNS(null,"id",o.getNextId()),a.setAttributeNS(null,"x",t.x),a.setAttributeNS(null,"y",t.y),a.setAttributeNS(null,"width",t.width),a.setAttributeNS(null,"height",t.height),a.setAttributeNS(null,"fill","url(#"+e+")"),r.append(a)}();else{var m=document.createElementNS(i.SVG,"image");o.assignAttributes(m,{id:o.getNextId(),x:t.x,y:t.y,width:t.width,height:t.height,preserveAspectRatio:u}),o.setHref(m,n),r.append(m)}g&&(o.extractAlphaPx(),g(!1))},y.src=n}}();t||o.extractAlphaPx()}();function E(t){return t<0?0:t}},r.removeBackgroundPattern=function(){for(var t,e=a(o.getLayerStage()).children("g"),n="bg_area_"+Number(o.getLayerStage().getAttribute("id").split("_")[1]),i=0;i<e.length;i++)e[i]&&e[i].getAttribute("type")===n&&((t=e[i]).prefs=null,a(t).empty())};case 7:case"end":return e.stop()}}),e)})))()},B=a(8),I=a(22),A=a.n(I),F=a(157),V=function(t){Object(l.a)(a,t);var e=Object(s.a)(a);function a(t){var n;return Object(i.a)(this,a),(n=e.call(this,t)).extAddedCb=function(t){D({$:A.a,editor:y.a,NS:B.a})},n.validateSpacingVal=function(t){if(t.target.value){var e=0;t.target.validity.valid&&(e=parseInt(t.target.value.replace(/^0+/,"")),(e=isNaN(e)?0:e)>10&&(e=10)),n.spacingValChanged(e)}},n.validateZoomVal=function(t){if(t.target.value){var e=0;t.target.validity.valid&&(e=parseInt(t.target.value.replace(/^0+/,"")),(e=isNaN(e)?0:e)>10&&(e=10)),n.zoomValChanged(e)}},n.openTemplateEditForm=function(){var t=y.a.canvas.getSelectedElems();y.a.canvas.addToSelection([t]),y.a.canvas.call("changed",t)},n.decoProductEditForm=function(){n.props.changeTempReplace(!1,"edit",!1),y.a.canvas.call("changed",[])},n.state={tabList:[{name:"Colors",type:0,isActive:!0},{name:"Patterns",type:1,isActive:!1}],chooseFitChecked:!1,fitOptions:[{name:"Tile",label:"Repeat",isActive:!0},{name:"Fit",label:"Exact fit",isActive:!1},{name:"Stretch",label:"Scale fit",isActive:!1}],fitOptData:{selectedOpt:"Tile",tileData:{isGrid:1,zoom:5,spacing:0,offsetX:0,offsetY:0,rotate:90}},selBackgroundPref:""},n.loadMoreBackground=n.loadMoreBackground.bind(Object(o.a)(n)),n.backgroundInfo={name:"",page:"1",sortby:"xe_id",order:"desc",perpage:"86",type:"0",category:"",print_profile_id:""},n.hasMorePage=!0,n.remainingBackground=0,n.isInitialLoad=!0,n.onTabSelected=n.onTabSelected.bind(Object(o.a)(n)),n.selectedTabType=0,n.patternInfo={name:"",page:"1",sortby:"xe_id",order:"desc",perpage:"86",type:"1",category:"",print_profile_id:""},n.selectedCatSubCat="",n.loadMorePatterns=n.loadMorePatterns.bind(Object(o.a)(n)),n.hasMorePatternPage=!0,n.remainingPattern=0,n.onBackgroundImgChange=n.onBackgroundImgChange.bind(Object(o.a)(n)),n.onChooseFitChange=n.onChooseFitChange.bind(Object(o.a)(n)),n.onFitOptSelected=n.onFitOptSelected.bind(Object(o.a)(n)),n.addDesign=n.addDesign.bind(Object(o.a)(n)),n.switchStyle=n.switchStyle.bind(Object(o.a)(n)),n.zoomValChanged=n.zoomValChanged.bind(Object(o.a)(n)),n.spacingValChanged=n.spacingValChanged.bind(Object(o.a)(n)),n.offsetXvalChanged=n.offsetXvalChanged.bind(Object(o.a)(n)),n.offsetYvalChanged=n.offsetYvalChanged.bind(Object(o.a)(n)),n.rotateValChanged=n.rotateValChanged.bind(Object(o.a)(n)),n.showBackgroundFilter=n.showBackgroundFilter.bind(Object(o.a)(n)),n.onCatSubcatSelect=n.onCatSubcatSelect.bind(Object(o.a)(n)),n.onFilterApplyClicked=n.onFilterApplyClicked.bind(Object(o.a)(n)),n.onFilterSearch=n.onFilterSearch.bind(Object(o.a)(n)),n.afterClearFilter=n.afterClearFilter.bind(Object(o.a)(n)),n.onDoneButtonClicked=n.onDoneButtonClicked.bind(Object(o.a)(n)),n.onCancelButtonClicked=n.onCancelButtonClicked.bind(Object(o.a)(n)),n.showHideBackLoader=n.showHideBackLoader.bind(Object(o.a)(n)),n.showBackGroundView=n.showBackGroundView.bind(Object(o.a)(n)),n.prevPropsData="",n.allowedFileFormat=["jpg","jpeg","png"],n.fileAccept="image/jpeg, image/png",n.isPrimeryCategory=!1,n.afterPrimeCatSet=n.afterPrimeCatSet.bind(Object(o.a)(n)),n.isEngraveEnable=!!(n.props.selectedPrintProfileData&&n.props.selectedPrintProfileData.engrave&&n.props.selectedPrintProfileData.engrave.is_laser_engrave_enabled),n.validateSpacingVal=n.validateSpacingVal.bind(Object(o.a)(n)),n.validateZoomVal=n.validateZoomVal.bind(Object(o.a)(n)),n.showColorPalette=!1,n.onColorSelect=n.onColorSelect.bind(Object(o.a)(n)),n.onColorHover=n.onColorHover.bind(Object(o.a)(n)),n.prevColor="",n}return Object(r.a)(a,[{key:"componentDidMount",value:function(){var t=this;this.elementSelected(),this.backgroundInfo.print_profile_id=this.props.printProfileReducer.selected_print_profile;var e=this.props.printProfileReducer.selected_print_profile,a=this.props.allCatData.filter((function(t){return t.printProfileId===e}))[0];a&&a.catLoadStatus||this.props.dispatch(Object(E.r)(e,this.props.allCatData)).then((function(e){(e&&!e.data||e&&e.data&&0===e.data.length)&&t.afterPrimeCatSet([])})),this.props.tagLoadStatus||this.props.dispatch(Object(E.s)()),O in y.a.canvas.extensions||y.a.addExtension(O,this.extAddedCb,{$:A.a})}},{key:"afterPrimeCatSet",value:function(t){this.selectedCatSubCat=Object(n.a)(t),this.selectedCatSubCat&&(this.backgroundInfo.category=this.selectedCatSubCat.length?JSON.stringify(this.selectedCatSubCat):null,this.patternInfo.category=this.selectedCatSubCat.length?JSON.stringify(this.selectedCatSubCat):null);var e=this.props.printProfileReducer.selected_print_profile,a=this.props.allBackColor.filter((function(t){return t.printProfileId===e}))[0];a&&a.backColorLoadStatus||this.props.dispatch(Object(E.q)(this.backgroundInfo,this.props.allBackColor)),this.patternInfo.print_profile_id=this.props.printProfileReducer.selected_print_profile;var i=this.props.allBackPattern.filter((function(t){return t.printProfileId===e}))[0];i&&i.backPatternLoadStatus||this.props.dispatch(Object(E.t)(this.patternInfo,this.props.allBackPattern)),this.selectedCatSubCat&&this.selectedCatSubCat.length?this.isPrimeryCategory=!0:this.isPrimeryCategory=!1}},{key:"componentDidUpdate",value:function(t){}},{key:"addDesign",value:function(t){""===t.thumbnail?(y.a.addBackgroundPattern(this.props.isMobileView,t.value,"","add","","",t),this.prevColor=t.value):y.a.addBackgroundPattern(this.props.isMobileView,"",t.value,"add",this.state.fitOptData,"",t,this.showHideBackLoader),this.elementSelected()}},{key:"loadMoreBackground",value:function(){this.patternInfo.page=this.props.backgrounds.pageNo;var t=Math.ceil(this.props.backgrounds.backColorData.total_records/this.backgroundInfo.perpage);this.backgroundInfo.page<t&&(this.backgroundInfo.page=parseInt(this.backgroundInfo.page)+1,this.props.dispatch(Object(E.q)(this.backgroundInfo,this.props.allBackColor)),this.remainingBackground=this.props.backgrounds.backColorData.total_records-this.props.backgrounds.backColorData.data.length,this.remainingBackground<this.backgroundInfo.perpage&&(this.hasMorePage=!1))}},{key:"onTabSelected",value:function(t){for(var e=this,a=Object(n.a)(this.state.tabList),i=0;i<a.length;i++)a[i].type===t?a[i].isActive=!0:a[i].isActive=!1;this.setState({tabList:Object(n.a)(a)},(function(){e.selectedTabType=t,0===t?e.props.backColorLoadStatus?e.forceUpdate():e.props.dispatch(Object(E.q)(e.backgroundInfo,e.props.allBackColor)):1===t&&(e.props.backPatternLoadStatus?e.forceUpdate():e.props.dispatch(Object(E.t)(e.patternInfo,e.props.allBackPattern)))}))}},{key:"loadMorePatterns",value:function(){this.patternInfo.page=this.props.patterns.pageNo;var t=Math.ceil(this.props.patterns.backPatternData.total_records/this.patternInfo.perpage);this.patternInfo.page<t&&(this.patternInfo.page=parseInt(this.patternInfo.page)+1,this.props.dispatch(Object(E.t)(this.patternInfo,this.props.allBackPattern)),this.remainingPattern=this.props.patterns.backPatternData.total_records-this.props.patterns.backPatternData.data.length,this.remainingPattern<this.patternInfo.perpage&&(this.hasMorePatternPage=!1))}},{key:"onBackgroundImgChange",value:function(t){var e=this,a=t.target.files[0];if(a){var n=a.name.replace(/.*\./,"").toLowerCase();if(-1!==this.allowedFileFormat.indexOf(n)){var i=new FileReader;i.onloadend=function(t){var i=t.target.result,r=e.b64toBlob(i.split(",")[1],a.type);e.showHideBackLoader(!0),e.props.dispatch(Object(E.v)(r,n,1)).then((function(t){t&&1===t.status&&t.data?(y.a.addBackgroundPattern(e.props.isMobileView,"",t.data.url,"add",e.state.fitOptData,"","",e.showHideBackLoader),e.elementSelected()):(e.props.showNotification("danger",e.props.language.canvas.imageUploadError),e.showHideBackLoader(!1))}))},i.readAsDataURL(a)}else this.props.showNotification("danger",this.props.language.background.invalidFileFormat)}}},{key:"b64toBlob",value:function(t){for(var e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"",a=arguments.length>2&&void 0!==arguments[2]?arguments[2]:512,n=window.atob(t),i=[],r=0;r<n.length;r+=a){for(var o=n.slice(r,r+a),l=new Array(o.length),s=0;s<o.length;s++)l[s]=o.charCodeAt(s);var c=new Uint8Array(l);i.push(c)}var d=new Blob(i,{type:e});return d}},{key:"onChooseFitChange",value:function(){this.setState({chooseFitChecked:!this.state.chooseFitChecked})}},{key:"onFitOptSelected",value:function(t){for(var e=this,a=Object(n.a)(this.state.fitOptions),i=0;i<a.length;i++)a[i].name===t?a[i].isActive=!0:a[i].isActive=!1;this.setState({fitOptions:Object(n.a)(a)},(function(){var a=e.state.fitOptData;a.selectedOpt=t,e.setState({fitOptData:a});y.a.addBackgroundPattern(e.props.isMobileView,"","","add",e.state.fitOptData,!0)}))}},{key:"switchStyle",value:function(t){var e=this.state.fitOptData;e.tileData.isGrid=t,this.setState({fitOptData:e}),y.a.addBackgroundPattern(this.props.isMobileView,"","","add",this.state.fitOptData,!0)}},{key:"zoomValChanged",value:function(t){var e=this.state.fitOptData;e.tileData.zoom=t,this.setState({fitOptData:e}),y.a.addBackgroundPattern(this.props.isMobileView,"","","add",this.state.fitOptData,!0)}},{key:"spacingValChanged",value:function(t){var e=this.state.fitOptData;e.tileData.spacing=t,this.setState({fitOptData:e}),y.a.addBackgroundPattern(this.props.isMobileView,"","","add",this.state.fitOptData,!0)}},{key:"offsetXvalChanged",value:function(t){var e=this.state.fitOptData;e.tileData.offsetX=t.target.value,this.setState({fitOptData:e}),y.a.addBackgroundPattern(this.props.isMobileView,"","","add",this.state.fitOptData,!0)}},{key:"offsetYvalChanged",value:function(t){var e=this.state.fitOptData;e.tileData.offsetY=t.target.value,this.setState({fitOptData:e}),y.a.addBackgroundPattern(this.props.isMobileView,"","","add",this.state.fitOptData,!0)}},{key:"rotateValChanged",value:function(t){var e=this.state.fitOptData;e.tileData.rotate=t.target.value,this.setState({fitOptData:e}),y.a.addBackgroundPattern(this.props.isMobileView,"","","add",this.state.fitOptData,!0)}},{key:"onCatSubcatSelect",value:function(t){this.selectedCatSubCat=Object(n.a)(t)}},{key:"onFilterApplyClicked",value:function(){this.selectedCatSubCat&&(this.backgroundInfo.category=this.selectedCatSubCat.length?JSON.stringify(this.selectedCatSubCat):null,this.patternInfo.category=this.selectedCatSubCat.length?JSON.stringify(this.selectedCatSubCat):null),0===this.selectedTabType?(this.backgroundInfo.page=1,this.props.dispatch(Object(E.q)(this.backgroundInfo,this.props.allBackColor))):1===this.selectedTabType&&(this.patternInfo.page=1,this.props.dispatch(Object(E.t)(this.patternInfo,this.props.allBackPattern)))}},{key:"onFilterSearch",value:function(t){this.backgroundInfo.name=t,this.backgroundInfo.page=1,this.patternInfo.name=t,this.patternInfo.page=1,this.selectedCatSubCat&&(this.backgroundInfo.category=this.selectedCatSubCat.length?JSON.stringify(this.selectedCatSubCat):null,this.patternInfo.category=this.selectedCatSubCat.length?JSON.stringify(this.selectedCatSubCat):null),0===this.selectedTabType?this.props.dispatch(Object(E.q)(this.backgroundInfo,this.props.allBackColor,!0)):1===this.selectedTabType&&this.props.dispatch(Object(E.t)(this.patternInfo,this.props.allBackPattern,!0))}},{key:"afterClearFilter",value:function(){this.selectedCatSubCat&&(this.backgroundInfo.category=this.selectedCatSubCat.length?JSON.stringify(this.selectedCatSubCat):null,this.patternInfo.category=this.selectedCatSubCat.length?JSON.stringify(this.selectedCatSubCat):null),0===this.selectedTabType?(this.backgroundInfo.page=1,this.props.dispatch(Object(E.q)(this.backgroundInfo,this.props.allBackColor,!0))):1===this.selectedTabType&&(this.patternInfo.page=1,this.props.dispatch(Object(E.t)(this.patternInfo,this.props.allBackPattern,!0))),this.setPrimeCatStatus()}},{key:"showBackgroundFilter",value:function(t,e,a,n,i,r){var o=this;if(t&&t.catData&&t.catData.length)return d.a.createElement(F.a,{onRef:function(t){return o.multiselectChild=t},options:t.catData,onSelectOptions:this.onCatSubcatSelect,onApplyClicked:this.onFilterApplyClicked,onSearchDataEntered:this.onFilterSearch,afterClearAll:this.afterClearFilter,tagList:n.data,afterPrimeCatSet:this.afterPrimeCatSet,primeCatStatus:t.primeCatStatus})}},{key:"onDoneButtonClicked",value:function(){y.a.canvas.getIsFormEdit()?this.decoProductEditForm():this.props.isTempReplace?this.openTemplateEditForm():this.props.backToModules(),this.props.handleSplitScreen("")}},{key:"onCancelButtonClicked",value:function(){var t=this,e=this.props.editBackgroundDetails;e?(this.onTabSelected(e.backgroundType),0===e.backgroundType?(this.addDesign(e.applyedBackground),this.afterCancelButtonClicked()):1===e.backgroundType&&this.setState({fitOptData:e.fitOptData},(function(){e.applyedBackground.xe_id?t.addDesign(e.applyedBackground):y.a.addBackgroundPattern(t.props.isMobileView,"",e.applyedBackground,"add",t.state.fitOptData),t.afterCancelButtonClicked()}))):(y.a.removeBackgroundPattern(),this.afterCancelButtonClicked())}},{key:"afterCancelButtonClicked",value:function(){y.a.canvas.getIsFormEdit()?this.decoProductEditForm():this.props.isTempReplace?this.openTemplateEditForm():this.props.backToModules(),this.props.checkBackgroundElm(),this.props.handleSplitScreen("")}},{key:"elementSelected",value:function(){var t,e=this,a=y.a.canvas.getCurrentLayerElems();if(a.length)for(var i=function(i){if(a[i].prefs&&"background"===a[i].prefs.type)return a[i].prefs.fitOptData?(e.setState({chooseFitChecked:!0}),e.setState({fitOptData:a[i].prefs.fitOptData},(function(){t=Object(n.a)(e.state.fitOptions);for(var r=0;r<t.length;r++)t[r].name===a[i].prefs.fitOptData.selectedOpt?t[r].isActive=!0:t[r].isActive=!1;e.setState({fitOptions:Object(n.a)(t)})}))):e.setState({chooseFitChecked:!1}),0===a[i].prefs.backgroundType&&(a[i].prefs.colors=[a[i].getElementsByTagName("rect")[0].getAttribute("fill")]),e.setState({selBackgroundPref:a[i].prefs}),e.onTabSelected(a[i].prefs.backgroundType),"break";e.setState({chooseFitChecked:!1}),e.setState({selBackgroundPref:""});var r=JSON.parse(JSON.stringify(e.state.fitOptData));r.selectedOpt="Tile",r.tileData.isGrid=1,r.tileData.spacing=0,r.tileData.zoom=5,e.setState({fitOptData:r})},r=0;r<a.length;r++){if("break"===i(r))break}else{this.setState({chooseFitChecked:!1}),this.setState({selBackgroundPref:""});var o=JSON.parse(JSON.stringify(this.state.fitOptData));o.selectedOpt="Tile",o.tileData.isGrid=1,o.tileData.spacing=0,o.tileData.zoom=5,this.setState({fitOptData:o})}this.props.checkBackgroundElm()}},{key:"showHideBackLoader",value:function(t){this.props.showHideBackLoader(t,"background")}},{key:"setPrimeCatStatus",value:function(){for(var t=this.props.printProfileReducer.selected_print_profile,e=Object(n.a)(this.props.allCatData),a=0;a<e.length;a++)if(e[a].printProfileId===t){e[a].primeCatStatus=!1;break}this.props.dispatch(Object(E.u)(e)),this.isPrimeryCategory&&(0===this.selectedTabType?(this.patternInfo.page=1,this.props.dispatch(Object(E.t)(this.patternInfo,this.props.allBackPattern,!0))):1===this.selectedTabType&&(this.backgroundInfo.page=1,this.props.dispatch(Object(E.q)(this.backgroundInfo,this.props.allBackColor,!0))),this.isPrimeryCategory=!1)}},{key:"onColorSelect",value:function(t){y.a.addBackgroundPattern(this.props.isMobileView,t.hex_value,"","add","","",{price:0,type:0,value:t.hex_value,thumbnail:""}),this.props.checkBackgroundElm(),this.prevColor=t.hex_value,this.elementSelected()}},{key:"onColorHover",value:function(t){y.a.addBackgroundPattern(this.props.isMobileView,t.hex_value,"","add","","",{price:0,type:0,value:t.hex_value})}},{key:"showBackGroundView",value:function(t,e,a,n,i,r){return a&&a.backColorData&&a.backColorData.data&&r&&r.backPatternData&&r.backPatternData.data?(a.backColorData.records<this.backgroundInfo.perpage?this.hasMorePage=!1:this.hasMorePage=!0,r.backPatternData.records<this.patternInfo.perpage?this.hasMorePatternPage=!1:this.hasMorePatternPage=!0,d.a.createElement(S,{backgrounds:a.backColorData.data,addDesign:this.addDesign,loadMoreBackground:this.loadMoreBackground,error:t,loading:e,hasMore:this.hasMorePage,totalBackgrounds:this.props.backgrounds.backColorData.total_records,tabList:this.state.tabList,onTabSelected:this.onTabSelected,selectedTabType:this.selectedTabType,patterns:r.backPatternData.data,patternError:n,patternLoading:i,loadMorePatterns:this.loadMorePatterns,hasMorePattern:this.hasMorePatternPage,totalPatterns:this.props.patterns.backPatternData.total_records,onBackgroundImgChange:this.onBackgroundImgChange,chooseFitChecked:this.state.chooseFitChecked,onChooseFitChange:this.onChooseFitChange,fitOptions:this.state.fitOptions,onFitOptSelected:this.onFitOptSelected,fitOptData:this.state.fitOptData,switchStyle:this.switchStyle,zoomValChanged:this.zoomValChanged,spacingValChanged:this.spacingValChanged,offsetXvalChanged:this.offsetXvalChanged,offsetYvalChanged:this.offsetYvalChanged,rotateValChanged:this.rotateValChanged,onDoneButtonClicked:this.onDoneButtonClicked,onCancelButtonClicked:this.onCancelButtonClicked,selBackgroundPref:this.state.selBackgroundPref,language:this.props.language,selectedPrintProfileData:this.props.selectedPrintProfileData,allowedFileFormat:this.allowedFileFormat,fileAccept:this.fileAccept,isMobileView:this.props.isMobileView,catLoading:this.props.catLoading,validateSpacingVal:this.validateSpacingVal,validateZoomVal:this.validateZoomVal,onColorSelect:this.onColorSelect,onColorHover:this.onColorHover,showColorPalette:this.showColorPalette,selectedColor:this.prevColor,isTempReplace:this.props.isTempReplace})):d.a.createElement(S,{error:t,loading:e,patternError:n,patternLoading:i,tabList:this.state.tabList,onTabSelected:this.onTabSelected,selectedTabType:this.selectedTabType,language:this.props.language,selectedPrintProfileData:this.props.selectedPrintProfileData,allowedFileFormat:this.allowedFileFormat,fileAccept:this.fileAccept,catLoading:this.props.catLoading})}},{key:"render",value:function(){var t=this.props,e=t.error,a=t.loading,n=t.backgrounds,i=t.patternError,r=t.patternLoading,o=t.patterns,l=t.category,s=t.catLoading,p=t.catError,u=t.backgroundTag,h=t.tagLoading,g=t.tagError;return d.a.createElement(c.Fragment,null,this.showBackgroundFilter(l,s,p,u,h,g),this.showBackGroundView(e,a,n,i,r,o))}}]),a}(d.a.Component),x=Object(p.b)((function(t){return{backgrounds:t.background.items.filter((function(e){return e.printProfileId===t.printProfileReducer.selected_print_profile}))[0],loading:t.background.loading,error:t.background.error,patterns:t.background.patternItem.filter((function(e){return e.printProfileId===t.printProfileReducer.selected_print_profile}))[0],patternLoading:t.background.patternLoading,patternError:t.background.patternError,category:t.background.catItem.filter((function(e){return e.printProfileId===t.printProfileReducer.selected_print_profile}))[0],catLoading:t.background.catLoading,catError:t.background.catError,backgroundTag:t.background.tagItem,tagLoading:t.background.tagLoading,tagError:t.background.tagError,backColorLoadStatus:t.background.backColorLoadStatus,backPatternLoadStatus:t.background.backPatternLoadStatus,categoryLoadStatus:t.background.categoryLoadStatus,tagLoadStatus:t.background.tagLoadStatus,backColorPageNo:t.background.backColorPageNo,backPatternPageNo:t.background.backPatternPageNo,language:t.languageReducer.translation_data.filter((function(e){return e.langCode===t.languageReducer.default_lang}))[0],printProfileReducer:t.printProfileReducer,allBackColor:t.background.items,allBackPattern:t.background.patternItem,allCatData:t.background.catItem,selectedPrintProfileData:t.printProfileReducer.print_profile_data.filter((function(e){return e.id===t.printProfileReducer.selected_print_profile}))[0]}}))(V);a.d(e,"default",(function(){return x}))}}]);