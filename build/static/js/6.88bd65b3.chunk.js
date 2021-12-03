(this["webpackJsonpinkxe10-designer"]=this["webpackJsonpinkxe10-designer"]||[]).push([[6],{941:function(e,t,a){"use strict";a.r(t);var n=a(9),r=a.n(n),i=a(20),l=a(4),o=a(10),s=a(11),g=a(13),c=a(16),d=a(15),p=a(0),u=a.n(p),m=a(6),h=function(e){Object(c.a)(a,e);var t=Object(d.a)(a);function a(e){var n;return Object(o.a)(this,a),(n=t.call(this,e)).state={drag:!1},n.dropRef=u.a.createRef(),n.uploadArFile=function(e){var t=e[0];if(n.checkFileType(e)&&n.checkFileSize(e)){var a={},r=new FileReader;r.readAsDataURL(t),r.onloadend=function(e){if(a.value=r.result,a.logoName=t.name,a.file=t,a.fileType=t.type,"image/png"===t.type||"image/jpeg"===t.type||"image/jpg"===t.type){var i=4,l=4,o=new Image;o.src=e.target.result,o.onload=function(){o.width>o.height?l=l/o.width*o.height:o.height>o.width&&(i=i/o.height*o.width),a.width=i,a.height=l}}else"video/mp4"===t.type&&(a.width=6,a.height=5);n.props.arFileStatus({status:a})}}else e[0].size/1024/1024<25&&n.props.showNotification("warning","".concat(t.name," ")+n.props.language.ar.invalidFile)},n.checkFileType=function(e){var t=e[0];return"image/png"===t.type||"image/jpeg"===t.type||"image/gif"===t.type||"video/mp4"===t.type||""===t.type||(n.props.showNotification("warning","".concat(t.type," ")+n.props.language.ar.fileNotSupported),!1)},n.checkFileSize=function(e){return!(e[0].size/1024/1024>25)||(n.props.showNotification("warning",n.props.language.ar.fileExceeds),!1)},n.handleDrag=function(e){e.preventDefault(),e.stopPropagation()},n.handleDragIn=function(e){e.preventDefault(),e.stopPropagation(),e.preventDefault(),e.stopPropagation(),n.dragCounter++,e.dataTransfer.items&&e.dataTransfer.items.length>0&&n.setState({drag:!0})},n.handleDragOut=function(e){e.preventDefault(),e.stopPropagation(),n.dragCounter--,0===n.dragCounter&&n.setState({drag:!1})},n.handleDrop=function(e){e.preventDefault(),e.stopPropagation(),n.setState({drag:!1}),e.dataTransfer.files&&e.dataTransfer.files.length>0&&(n.uploadArFile(e.dataTransfer.files),e.dataTransfer.clearData(),n.dragCounter=0)},n}return Object(s.a)(a,[{key:"componentDidMount",value:function(){var e=this.dropRef.current;e.addEventListener("dragenter",this.handleDragIn),e.addEventListener("dragleave",this.handleDragOut),e.addEventListener("dragover",this.handleDrag),e.addEventListener("drop",this.handleDrop)}},{key:"render",value:function(){var e=this;return u.a.createElement(p.Fragment,null,u.a.createElement("h6",{className:"alert alert-primary small-txt tip mb-4"},u.a.createElement("i",{className:"alert-icon"}),u.a.createElement("span",{className:"alert-txt"},this.props.language.ar.arTips)),u.a.createElement("div",{className:"files ar-drops customfileDrops"},u.a.createElement("div",{className:"boxTitle"},this.props.language.ar.browseARFile),this.props.isarFileUpload&&u.a.createElement("div",{className:"files-dropzone fileView"},u.a.createElement("div",{className:"nx-drop-box",style:{cursor:"pointer"}},u.a.createElement("i",{className:"nf nf-tick nx-drop-icon"}),u.a.createElement("span",{className:"nx-drop-text"},this.props.fileName,u.a.createElement("a",{className:"d-block nx-drop-btn"},this.props.language.ar.uploadAgain)),u.a.createElement("input",{accept:".png, .jpg, .jpeg ,.mp4,.gif,.gltf",title:" ",type:"file",onChange:function(t){return e.uploadArFile(t.target.files)},style:{position:"absolute",padding:"0px",margin:"0px",overflow:"hidden",opacity:"0",cursor:"pointer"}}))),!this.props.isarFileUpload&&u.a.createElement("div",{className:"files-dropzone"},u.a.createElement("div",{className:"nx-drop-box",ref:this.dropRef,style:{cursor:"pointer"}},u.a.createElement("i",{className:"nf nf-ar-scan nx-drop-icon"}),u.a.createElement("div",{className:"nx-drop-text"},this.props.language.ar.dragAndDrop,", or ",u.a.createElement("a",{className:"nx-drop-btn"},this.props.language.ar.browse)),u.a.createElement("input",{accept:".png, .jpg, .jpeg ,.mp4,.gif,.gltf",type:"file",onChange:function(t){return e.uploadArFile(t.target.files)}}))),u.a.createElement("h6",{className:"small-txt"},this.props.language.ar.acceptedFileTypes),u.a.createElement("div",{className:"upload-info"},u.a.createElement("span",null,"jpg"),u.a.createElement("span",null,"png"),u.a.createElement("span",null,"mp4"),u.a.createElement("span",null,"gif"),u.a.createElement("span",null,"Gltf"))))}}]),a}(p.Component),C=a(60),f=a.n(C),A=a(26),I=a(37),E=a(1),v=a.n(E),b=a(34),y=a.n(b),F=a(14),B={children:v.a.node,bar:v.a.bool,multi:v.a.bool,tag:F.o,value:v.a.oneOfType([v.a.string,v.a.number]),max:v.a.oneOfType([v.a.string,v.a.number]),animated:v.a.bool,striped:v.a.bool,color:v.a.string,className:v.a.string,barClassName:v.a.string,cssModule:v.a.object},w=function(e){var t=e.children,a=e.className,n=e.barClassName,r=e.cssModule,i=e.value,l=e.max,o=e.animated,s=e.striped,g=e.color,c=e.bar,d=e.multi,p=e.tag,m=Object(I.a)(e,["children","className","barClassName","cssModule","value","max","animated","striped","color","bar","multi","tag"]),h=Object(F.q)(i)/Object(F.q)(l)*100,C=Object(F.k)(y()(a,"progress"),r),f=Object(F.k)(y()("progress-bar",c&&a||n,o?"progress-bar-animated":null,g?"bg-"+g:null,s||o?"progress-bar-striped":null),r),E=d?t:u.a.createElement("div",{className:f,style:{width:h+"%"},role:"progressbar","aria-valuenow":i,"aria-valuemin":"0","aria-valuemax":l,children:t});return c?E:u.a.createElement(p,Object(A.a)({},m,{className:C,children:E}))};w.propTypes=B,w.defaultProps={tag:"div",value:0,max:100};var k=w,j=a(24),x=a(12),Q=a(60),N=function(){function e(){Object(o.a)(this,e)}return Object(s.a)(e,null,[{key:"uploadARPatternFIle",value:function(e){var t=new FormData;return t.append("upload",e,"pattern-marker.patt"),Q({method:"post",url:x.a.base_path+"augmented-reality/pattern",data:t,headers:{"Content-Type":"multipart/form-data"}})}},{key:"b64toBlob",value:function(e){for(var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"",a=arguments.length>2&&void 0!==arguments[2]?arguments[2]:512,n=window.atob(e),r=[],i=0;i<n.length;i+=a){for(var l=n.slice(i,i+a),o=new Array(l.length),s=0;s<l.length;s++)o[s]=l.charCodeAt(s);var g=new Uint8Array(o);r.push(g)}var c=new Blob(r,{type:t});return c}},{key:"getMarker",value:function(){return"data:image/png;base64,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"}}]),e}(),R=a(3),G=function(){function e(){Object(o.a)(this,e)}return Object(s.a)(e,null,[{key:"buildFullMarker",value:function(e,t){var a=document.createElement("canvas"),n=a.getContext("2d");a.width=a.height=512,n.fillStyle="white",n.fillRect(0,0,a.width,a.height),n.fillStyle="black",n.fillRect(.1*a.width,.1*a.height,.8*a.width,.8*a.height),n.fillStyle="white",n.fillRect((.1+.2)*a.width,(.1+.2)*a.height,a.width*(1-2*(.1+.2)),a.height*(1-2*(.1+.2)));var r=document.createElement("img");r.addEventListener("load",(function(){n.drawImage(r,(.1+.2)*a.width,(.1+.2)*a.height,a.width*(1-2*(.1+.2)),a.height*(1-2*(.1+.2)));var e=a.toDataURL();t(e)})),r.src=e}},{key:"generatePattern",value:function(e){var t=document.createElement("canvas"),a=t.getContext("2d");t.width=16,t.height=16;for(var n="",r=0;r>-2*Math.PI;r-=Math.PI/2){a.save(),a.clearRect(0,0,t.width,t.height),a.translate(t.width/2,t.height/2),a.rotate(r),a.drawImage(e,-t.width/2,-t.height/2,t.width,t.height),a.restore();var i=a.getImageData(0,0,t.width,t.height);0!==r&&(n+="\n");for(var l=2;l>=0;l--)for(var o=0;o<i.height;o++){for(var s=0;s<i.width;s++){0!==s&&(n+=" ");var g=o*i.width*4+4*s+l,c=i.data[g];n+=String(c).padStart(3)}n+="\n"}}return n}}]),e}(),S="ARExt",D=function(e){return Object(i.a)(r.a.mark((function t(){var a,n,i;return r.a.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return e.$,a=e.editor,i=(n=a).canvas,n.importARSvgQrCodes=function(e){var t=i.addSVGElementFromJson({element:"g",attr:{id:i.getNextId()}});t.innerHTML=e,i.selectOnly([t]);var a=i.getSelectedElems()[0];i.scaleElem(a),i.alignSelectedElements("m","page"),i.alignSelectedElements("c","page")},t.abrupt("return",{callback:function(){}});case 5:case"end":return t.stop()}}),t)})))()},L=a(22),O=a.n(L),K=a(886),U=function(e){Object(c.a)(a,e);var t=Object(d.a)(a);function a(e){var n;return Object(o.a)(this,a),(n=t.call(this,e)).extAddedCb=function(e){D({$:O.a,editor:R.a})},n.generateQrCode=function(){var e=new FormData;e.append("upload",n.state.selectedFile),n.state.arFileProps.hasOwnProperty("width")&&n.state.arFileProps.hasOwnProperty("height")&&(e.append("width",n.state.arFileProps.width),e.append("height",n.state.arFileProps.height)),""!==n.patterenFile&&e.append("pattern",n.patterenFile,"pattern-marker.patt"),f.a.post(x.a.base_path+"augmented-reality",e,{onUploadProgress:function(e){n.setState({loaded:e.loaded/e.total*100})}}).then((function(e){if(e.data&&1===e.data.status){var t=Object(l.a)({},n.state.qrcodeValue);t.value=e.data.data.html;var a=N.getMarker(),o=N.b64toBlob(a.split(",")[1],"image/png"),s=new File([o],"logo");n.props.dispatch(m.hb(n.props.currentSideIndex,s,"png",1)).then(function(){var e=Object(i.a)(r.a.mark((function e(a){return r.a.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:1===a.status&&(t.imageSettings.src=a.data.url,n.setState({loaded:0,selectedFile:null,linkGenerated:!0,qrcodeValue:t},(function(){n.addSvgQRcodeToStage()})));case 1:case"end":return e.stop()}}),e)})));return function(t){return e.apply(this,arguments)}}()).catch((function(e){n.props.showNotification("danger",n.props.language.qr.logoUploadError)}))}else n.props.showNotification("danger",n.props.language.ar.generateQRError),n.reset()})).catch((function(e){n.props.showNotification("danger",n.props.language.ar.serverError),n.reset()}))},n.encodeImageURL=function(e){var t=new Image;t.onload=function(){var e=G.generatePattern(t),a=new Blob([e],{type:"text/plain"});n.patterenFile=a},t.src=e},n.updateFullMarkerImage=function(e){G.buildFullMarker(e,(function(e){var t=e,a=Object(l.a)({},n.state.qrcodeValue);a.logoImage=t,n.setState({qrcodeValue:a})}))},n.addSvgQRcodeToStage=function(){var e=document.getElementById("qrCode");R.a.importARSvgQrCodes(e.innerHTML),n.addXePropsToArObject()},n.addXePropsToArObject=function(){var e=R.a.getActiveObject(),t={type:"ar",logoName:n.state.logoName,objectTitle:"",fileType:"svg",lock:{resize:!1,move:!1,rotate:!1,delete:!1,edit:!1},qrCodeValue:n.state.qrcodeValue};e.setAttribute("type","ar"),e.prefs=t},n.changeQrCode=function(){n.reset(),n.props.setHeaderDispay(!0)},n.reset=function(){n.setState({elemSelected:!1,linkGenerated:!1,isarFileUpload:!1,loaded:0,selectedFile:null})},n.state={qrcodeValue:{id:"qrCode",value:"",size:230,fgColor:"#000000",bgColor:"none",level:"H",renderAs:"svg",includeMargin:!1,imageSettings:{src:N.getMarker(),x:null,y:null,height:50,width:50,excavate:!0}},loaded:0,selectedFile:null,fileName:"",linkGenerated:!1,isarFileUpload:!1,arFileProps:{},elemSelected:!1,logoName:""},n.elementSelected=n.elementSelected.bind(Object(g.a)(n)),n.openView=n.openView.bind(Object(g.a)(n)),n.handleLogoChange=function(e){var t=e.target;n.setState({logoName:t.logoName}),n.updateFullMarkerImage(t.value),n.encodeImageURL(t.value)},n.arFileStatus=function(e){var t=e.status;n.setState({selectedFile:t.file,fileName:t.logoName,isarFileUpload:!0,linkGenerated:!1,arFileProps:t})},n}return Object(s.a)(a,[{key:"elementSelected",value:function(e){e?e.prefs&&"ar"===e.prefs.type&&(this.setState({qrcodeValue:e.prefs.qrCodeValue,elemSelected:!0}),this.props.setHeaderDispay(!1)):this.openView("")}},{key:"componentDidMount",value:function(){this.patterenFile="",S in R.a.canvas.extensions||R.a.addExtension(S,this.extAddedCb,{$:O.a})}},{key:"openView",value:function(e){""===e&&(this.reset(),this.props.setHeaderDispay(!0))}},{key:"render",value:function(){var e=this;return u.a.createElement(u.a.Fragment,null,this.state.elemSelected&&u.a.createElement("div",{className:"element-controls-header d-none d-md-flex d-lg-flex d-xl-flex"},u.a.createElement("section",{className:"boxTitle back-to-back",onClick:function(){return e.openView("")}},u.a.createElement("div",{className:"icon",title:this.props.language.main.backToSection},u.a.createElement("i",{className:"nf nf-left-arrow"})),u.a.createElement("div",{className:"text"},this.props.language.panel.back)),u.a.createElement("div",{className:"boxTitle back-to-menu",title:this.props.language.main.backToMain},u.a.createElement("i",{className:"nf nf-delete"}))),u.a.createElement("div",{className:"arSecWrap",style:{display:this.state.elemSelected?"none":"block"}},u.a.createElement("div",{className:"arUploadWrap",style:{display:this.state.linkGenerated?"none":"block"}},u.a.createElement(h,{arFileStatus:this.arFileStatus,fileName:this.state.fileName,isarFileUpload:this.state.isarFileUpload,language:this.props.language,showNotification:this.props.showNotification}),u.a.createElement("hr",{className:"mt-3 mb-3"}),this.state.isarFileUpload&&u.a.createElement("button",{type:"button",onClick:this.generateQrCode,className:"btn btn-dark btn-block mt-4"},this.props.language.ar.generateQrCode)),0!=this.state.loaded&&!this.state.linkGenerated&&u.a.createElement("div",{className:"mt-3"},u.a.createElement("h6",{className:"small-txt"},this.props.language.ar.creating),u.a.createElement(k,{className:"mb-2",max:"100",color:"success",value:this.state.loaded},Math.round(this.state.loaded,2),"%"))),u.a.createElement("div",{className:"qr-code-wrap",style:{display:this.state.elemSelected?"block":"none"}},u.a.createElement("h6",{className:"alert alert-primary small-txt tip mt-2"},u.a.createElement("i",{className:"alert-icon"}),u.a.createElement("span",{className:"alert-txt"},this.props.language.ar.arStatus)),u.a.createElement(K,this.state.qrcodeValue),u.a.createElement("button",{type:"button",className:"btn btn-dark btn-block mt-2",onClick:this.changeQrCode},this.props.language.ar.changeQrCode)))}}]),a}(u.a.Component),P=Object(j.b)((function(e){return{currentSideIndex:e.productCanvasReducer.active_product_side,language:e.languageReducer.translation_data.filter((function(t){return t.langCode===e.languageReducer.default_lang}))[0]}}))(U);a.d(t,"default",(function(){return P}))}}]);