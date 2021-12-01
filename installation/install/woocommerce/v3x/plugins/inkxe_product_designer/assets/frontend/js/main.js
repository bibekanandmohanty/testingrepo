/*!
 * imagesLoaded PACKAGED v3.1.8
 * JavaScript is all like "You images are done yet or what?"
 * MIT License
 */

(function(){function e(){}function t(e,t){for(var n=e.length;n--;)if(e[n].listener===t)return n;return-1}function n(e){return function(){return this[e].apply(this,arguments)}}var i=e.prototype,r=this,o=r.EventEmitter;i.getListeners=function(e){var t,n,i=this._getEvents();if("object"==typeof e){t={};for(n in i)i.hasOwnProperty(n)&&e.test(n)&&(t[n]=i[n])}else t=i[e]||(i[e]=[]);return t},i.flattenListeners=function(e){var t,n=[];for(t=0;e.length>t;t+=1)n.push(e[t].listener);return n},i.getListenersAsObject=function(e){var t,n=this.getListeners(e);return n instanceof Array&&(t={},t[e]=n),t||n},i.addListener=function(e,n){var i,r=this.getListenersAsObject(e),o="object"==typeof n;for(i in r)r.hasOwnProperty(i)&&-1===t(r[i],n)&&r[i].push(o?n:{listener:n,once:!1});return this},i.on=n("addListener"),i.addOnceListener=function(e,t){return this.addListener(e,{listener:t,once:!0})},i.once=n("addOnceListener"),i.defineEvent=function(e){return this.getListeners(e),this},i.defineEvents=function(e){for(var t=0;e.length>t;t+=1)this.defineEvent(e[t]);return this},i.removeListener=function(e,n){var i,r,o=this.getListenersAsObject(e);for(r in o)o.hasOwnProperty(r)&&(i=t(o[r],n),-1!==i&&o[r].splice(i,1));return this},i.off=n("removeListener"),i.addListeners=function(e,t){return this.manipulateListeners(!1,e,t)},i.removeListeners=function(e,t){return this.manipulateListeners(!0,e,t)},i.manipulateListeners=function(e,t,n){var i,r,o=e?this.removeListener:this.addListener,s=e?this.removeListeners:this.addListeners;if("object"!=typeof t||t instanceof RegExp)for(i=n.length;i--;)o.call(this,t,n[i]);else for(i in t)t.hasOwnProperty(i)&&(r=t[i])&&("function"==typeof r?o.call(this,i,r):s.call(this,i,r));return this},i.removeEvent=function(e){var t,n=typeof e,i=this._getEvents();if("string"===n)delete i[e];else if("object"===n)for(t in i)i.hasOwnProperty(t)&&e.test(t)&&delete i[t];else delete this._events;return this},i.removeAllListeners=n("removeEvent"),i.emitEvent=function(e,t){var n,i,r,o,s=this.getListenersAsObject(e);for(r in s)if(s.hasOwnProperty(r))for(i=s[r].length;i--;)n=s[r][i],n.once===!0&&this.removeListener(e,n.listener),o=n.listener.apply(this,t||[]),o===this._getOnceReturnValue()&&this.removeListener(e,n.listener);return this},i.trigger=n("emitEvent"),i.emit=function(e){var t=Array.prototype.slice.call(arguments,1);return this.emitEvent(e,t)},i.setOnceReturnValue=function(e){return this._onceReturnValue=e,this},i._getOnceReturnValue=function(){return this.hasOwnProperty("_onceReturnValue")?this._onceReturnValue:!0},i._getEvents=function(){return this._events||(this._events={})},e.noConflict=function(){return r.EventEmitter=o,e},"function"==typeof define&&define.amd?define("eventEmitter/EventEmitter",[],function(){return e}):"object"==typeof module&&module.exports?module.exports=e:this.EventEmitter=e}).call(this),function(e){function t(t){var n=e.event;return n.target=n.target||n.srcElement||t,n}var n=document.documentElement,i=function(){};n.addEventListener?i=function(e,t,n){e.addEventListener(t,n,!1)}:n.attachEvent&&(i=function(e,n,i){e[n+i]=i.handleEvent?function(){var n=t(e);i.handleEvent.call(i,n)}:function(){var n=t(e);i.call(e,n)},e.attachEvent("on"+n,e[n+i])});var r=function(){};n.removeEventListener?r=function(e,t,n){e.removeEventListener(t,n,!1)}:n.detachEvent&&(r=function(e,t,n){e.detachEvent("on"+t,e[t+n]);try{delete e[t+n]}catch(i){e[t+n]=void 0}});var o={bind:i,unbind:r};"function"==typeof define&&define.amd?define("eventie/eventie",o):e.eventie=o}(this),function(e,t){"function"==typeof define&&define.amd?define(["eventEmitter/EventEmitter","eventie/eventie"],function(n,i){return t(e,n,i)}):"object"==typeof exports?module.exports=t(e,require("wolfy87-eventemitter"),require("eventie")):e.imagesLoaded=t(e,e.EventEmitter,e.eventie)}(window,function(e,t,n){function i(e,t){for(var n in t)e[n]=t[n];return e}function r(e){return"[object Array]"===d.call(e)}function o(e){var t=[];if(r(e))t=e;else if("number"==typeof e.length)for(var n=0,i=e.length;i>n;n++)t.push(e[n]);else t.push(e);return t}function s(e,t,n){if(!(this instanceof s))return new s(e,t);"string"==typeof e&&(e=document.querySelectorAll(e)),this.elements=o(e),this.options=i({},this.options),"function"==typeof t?n=t:i(this.options,t),n&&this.on("always",n),this.getImages(),a&&(this.jqDeferred=new a.Deferred);var r=this;setTimeout(function(){r.check()})}function f(e){this.img=e}function c(e){this.src=e,v[e]=this}var a=e.jQuery,u=e.console,h=u!==void 0,d=Object.prototype.toString;s.prototype=new t,s.prototype.options={},s.prototype.getImages=function(){this.images=[];for(var e=0,t=this.elements.length;t>e;e++){var n=this.elements[e];"IMG"===n.nodeName&&this.addImage(n);var i=n.nodeType;if(i&&(1===i||9===i||11===i))for(var r=n.querySelectorAll("img"),o=0,s=r.length;s>o;o++){var f=r[o];this.addImage(f)}}},s.prototype.addImage=function(e){var t=new f(e);this.images.push(t)},s.prototype.check=function(){function e(e,r){return t.options.debug&&h&&u.log("confirm",e,r),t.progress(e),n++,n===i&&t.complete(),!0}var t=this,n=0,i=this.images.length;if(this.hasAnyBroken=!1,!i)return this.complete(),void 0;for(var r=0;i>r;r++){var o=this.images[r];o.on("confirm",e),o.check()}},s.prototype.progress=function(e){this.hasAnyBroken=this.hasAnyBroken||!e.isLoaded;var t=this;setTimeout(function(){t.emit("progress",t,e),t.jqDeferred&&t.jqDeferred.notify&&t.jqDeferred.notify(t,e)})},s.prototype.complete=function(){var e=this.hasAnyBroken?"fail":"done";this.isComplete=!0;var t=this;setTimeout(function(){if(t.emit(e,t),t.emit("always",t),t.jqDeferred){var n=t.hasAnyBroken?"reject":"resolve";t.jqDeferred[n](t)}})},a&&(a.fn.imagesLoaded=function(e,t){var n=new s(this,e,t);return n.jqDeferred.promise(a(this))}),f.prototype=new t,f.prototype.check=function(){var e=v[this.img.src]||new c(this.img.src);if(e.isConfirmed)return this.confirm(e.isLoaded,"cached was confirmed"),void 0;if(this.img.complete&&void 0!==this.img.naturalWidth)return this.confirm(0!==this.img.naturalWidth,"naturalWidth"),void 0;var t=this;e.on("confirm",function(e,n){return t.confirm(e.isLoaded,n),!0}),e.check()},f.prototype.confirm=function(e,t){this.isLoaded=e,this.emit("confirm",this,t)};var v={};return c.prototype=new t,c.prototype.check=function(){if(!this.isChecked){var e=new Image;n.bind(e,"load",this),n.bind(e,"error",this),e.src=this.src,this.isChecked=!0}},c.prototype.handleEvent=function(e){var t="on"+e.type;this[t]&&this[t](e)},c.prototype.onload=function(e){this.confirm(!0,"onload"),this.unbindProxyEvents(e)},c.prototype.onerror=function(e){this.confirm(!1,"onerror"),this.unbindProxyEvents(e)},c.prototype.confirm=function(e,t){this.isConfirmed=!0,this.isLoaded=e,this.emit("confirm",this,t)},c.prototype.unbindProxyEvents=function(e){n.unbind(e.target,"load",this),n.unbind(e.target,"error",this)},s});
/***
 * BxSlider v4.2.3 - Fully loaded, responsive content slider
 * http://bxslider.com
 *
 * Copyright 2014, Steven Wanderski - http://stevenwanderski.com - http://bxcreative.com
 * Written while drinking Belgian ales and listening to jazz
 *
 * Released under the MIT license - http://opensource.org/licenses/MIT
 ***/
!function(e){var t={mode:"horizontal",slideSelector:"",infiniteLoop:!0,hideControlOnEnd:!1,speed:500,easing:null,slideMargin:0,startSlide:0,randomStart:!1,captions:!1,ticker:!1,tickerHover:!1,adaptiveHeight:!1,adaptiveHeightSpeed:500,video:!1,useCSS:!0,preloadImages:"visible",responsive:!0,slideZIndex:50,wrapperClass:"bx-wrapper",touchEnabled:!0,swipeThreshold:50,oneToOneTouch:!0,preventDefaultSwipeX:!0,preventDefaultSwipeY:!1,keyboardEnabled:!1,pager:!0,pagerType:"full",pagerShortSeparator:" / ",pagerSelector:null,buildPager:null,pagerCustom:null,controls:!0,nextText:"Next",prevText:"Prev",nextSelector:null,prevSelector:null,autoControls:!1,startText:"Start",stopText:"Stop",autoControlsCombine:!1,autoControlsSelector:null,auto:!1,pause:4e3,autoStart:!0,autoDirection:"next",autoHover:!1,autoDelay:0,autoSlideForOnePage:!1,minSlides:1,maxSlides:1,moveSlides:0,slideWidth:0,onSliderLoad:function(){return!0},onSlideBefore:function(){return!0},onSlideAfter:function(){return!0},onSlideNext:function(){return!0},onSlidePrev:function(){return!0},onSliderResize:function(){return!0}};e.fn.bxSlider=function(n){if(0===this.length)return this;if(this.length>1)return this.each(function(){e(this).bxSlider(n)}),this;var s={},o=this,r=e(window).width(),a=e(window).height(),l=function(){s.settings=e.extend({},t,n),s.settings.slideWidth=parseInt(s.settings.slideWidth),s.children=o.children(s.settings.slideSelector),s.children.length<s.settings.minSlides&&(s.settings.minSlides=s.children.length),s.children.length<s.settings.maxSlides&&(s.settings.maxSlides=s.children.length),s.settings.randomStart&&(s.settings.startSlide=Math.floor(Math.random()*s.children.length)),s.active={index:s.settings.startSlide},s.carousel=s.settings.minSlides>1||s.settings.maxSlides>1?!0:!1,s.carousel&&(s.settings.preloadImages="all"),s.minThreshold=s.settings.minSlides*s.settings.slideWidth+(s.settings.minSlides-1)*s.settings.slideMargin,s.maxThreshold=s.settings.maxSlides*s.settings.slideWidth+(s.settings.maxSlides-1)*s.settings.slideMargin,s.working=!1,s.controls={},s.interval=null,s.animProp="vertical"===s.settings.mode?"top":"left",s.usingCSS=s.settings.useCSS&&"fade"!==s.settings.mode&&function(){var e=document.createElement("div"),t=["WebkitPerspective","MozPerspective","OPerspective","msPerspective"];for(var i in t)if(void 0!==e.style[t[i]])return s.cssPrefix=t[i].replace("Perspective","").toLowerCase(),s.animProp="-"+s.cssPrefix+"-transform",!0;return!1}(),"vertical"===s.settings.mode&&(s.settings.maxSlides=s.settings.minSlides),o.data("origStyle",o.attr("style")),o.children(s.settings.slideSelector).each(function(){e(this).data("origStyle",e(this).attr("style"))}),d()},d=function(){o.wrap('<div class="'+s.settings.wrapperClass+'"><div class="bx-viewport"></div></div>'),s.viewport=o.parent(),s.loader=e('<div class="bx-loading" />'),s.viewport.prepend(s.loader),o.css({width:"horizontal"===s.settings.mode?1e3*s.children.length+215+"%":"auto",position:"absolute"}),s.usingCSS&&s.settings.easing?o.css("-"+s.cssPrefix+"-transition-timing-function",s.settings.easing):s.settings.easing||(s.settings.easing="swing");v();s.viewport.css({width:"100%",overflow:"hidden",position:"relative"}),s.viewport.parent().css({maxWidth:u()}),s.settings.pager||s.settings.controls||s.viewport.parent().css({margin:"0 auto 0px"}),s.children.css({"float":"horizontal"===s.settings.mode?"left":"none",listStyle:"none",position:"relative"}),s.children.css("width",h()),"horizontal"===s.settings.mode&&s.settings.slideMargin>0&&s.children.css("marginRight",s.settings.slideMargin),"vertical"===s.settings.mode&&s.settings.slideMargin>0&&s.children.css("marginBottom",s.settings.slideMargin),"fade"===s.settings.mode&&(s.children.css({position:"absolute",zIndex:0,display:"none"}),s.children.eq(s.settings.startSlide).css({zIndex:s.settings.slideZIndex,display:"block"})),s.controls.el=e('<div class="bx-controls" />'),s.settings.captions&&P(),s.active.last=s.settings.startSlide===f()-1,s.settings.video&&o.fitVids();var t=s.children.eq(s.settings.startSlide);("all"===s.settings.preloadImages||s.settings.ticker)&&(t=s.children),s.settings.ticker?s.settings.pager=!1:(s.settings.controls&&C(),s.settings.auto&&s.settings.autoControls&&T(),s.settings.pager&&w(),(s.settings.controls||s.settings.autoControls||s.settings.pager)&&s.viewport.after(s.controls.el)),c(t,g)},c=function(t,i){var n=t.find('img:not([src=""]), iframe').length;if(0===n)return void i();var s=0;t.find('img:not([src=""]), iframe').each(function(){e(this).one("load error",function(){++s===n&&i()}).each(function(){this.complete&&e(this).load()})})},g=function(){if(s.settings.infiniteLoop&&"fade"!==s.settings.mode&&!s.settings.ticker){var t="vertical"===s.settings.mode?s.settings.minSlides:s.settings.maxSlides,i=s.children.slice(0,t).clone(!0).addClass("bx-clone"),n=s.children.slice(-t).clone(!0).addClass("bx-clone");o.append(i).prepend(n)}s.loader.remove(),m(),"vertical"===s.settings.mode&&(s.settings.adaptiveHeight=!0),s.viewport.height(p()),o.redrawSlider(),s.settings.onSliderLoad(s,s.active.index),s.initialized=!0,s.settings.responsive&&e(window).bind("resize",Z),s.settings.auto&&s.settings.autoStart&&(f()>1||s.settings.autoSlideForOnePage)&&A(),s.settings.ticker&&H(),s.settings.pager&&I(s.settings.startSlide),s.settings.controls&&W(),s.settings.touchEnabled&&!s.settings.ticker&&O(),s.settings.keyboardEnabled&&!s.settings.ticker&&e(document).keydown(N)},p=function(){var t=0,n=e();if("vertical"===s.settings.mode||s.settings.adaptiveHeight)if(s.carousel){var o=1===s.settings.moveSlides?s.active.index:s.active.index*x();for(n=s.children.eq(o),i=1;i<=s.settings.maxSlides-1;i++)n=n.add(o+i>=s.children.length?s.children.eq(i-1):s.children.eq(o+i))}else n=s.children.eq(s.active.index);else n=s.children;return"vertical"===s.settings.mode?(n.each(function(){t+=e(this).outerHeight()}),s.settings.slideMargin>0&&(t+=s.settings.slideMargin*(s.settings.minSlides-1))):t=Math.max.apply(Math,n.map(function(){return e(this).outerHeight(!1)}).get()),"border-box"===s.viewport.css("box-sizing")?t+=parseFloat(s.viewport.css("padding-top"))+parseFloat(s.viewport.css("padding-bottom"))+parseFloat(s.viewport.css("border-top-width"))+parseFloat(s.viewport.css("border-bottom-width")):"padding-box"===s.viewport.css("box-sizing")&&(t+=parseFloat(s.viewport.css("padding-top"))+parseFloat(s.viewport.css("padding-bottom"))),t},u=function(){var e="100%";return s.settings.slideWidth>0&&(e="horizontal"===s.settings.mode?s.settings.maxSlides*s.settings.slideWidth+(s.settings.maxSlides-1)*s.settings.slideMargin:s.settings.slideWidth),e},h=function(){var e=s.settings.slideWidth,t=s.viewport.width();return 0===s.settings.slideWidth||s.settings.slideWidth>t&&!s.carousel||"vertical"===s.settings.mode?e=t:s.settings.maxSlides>1&&"horizontal"===s.settings.mode&&(t>s.maxThreshold||t<s.minThreshold&&(e=(t-s.settings.slideMargin*(s.settings.minSlides-1))/s.settings.minSlides)),e},v=function(){var e=1;if("horizontal"===s.settings.mode&&s.settings.slideWidth>0)if(s.viewport.width()<s.minThreshold)e=s.settings.minSlides;else if(s.viewport.width()>s.maxThreshold)e=s.settings.maxSlides;else{var t=s.children.first().width()+s.settings.slideMargin;e=Math.floor((s.viewport.width()+s.settings.slideMargin)/t)}else"vertical"===s.settings.mode&&(e=s.settings.minSlides);return e},f=function(){var e=0;if(s.settings.moveSlides>0)if(s.settings.infiniteLoop)e=Math.ceil(s.children.length/x());else for(var t=0,i=0;t<s.children.length;)++e,t=i+v(),i+=s.settings.moveSlides<=v()?s.settings.moveSlides:v();else e=Math.ceil(s.children.length/v());return e},x=function(){return s.settings.moveSlides>0&&s.settings.moveSlides<=v()?s.settings.moveSlides:v()},m=function(){var e;if(s.children.length>s.settings.maxSlides&&s.active.last&&!s.settings.infiniteLoop){if("horizontal"===s.settings.mode){var t=s.children.last();e=t.position(),S(-(e.left-(s.viewport.width()-t.outerWidth())),"reset",0)}else if("vertical"===s.settings.mode){var i=s.children.length-s.settings.minSlides;e=s.children.eq(i).position(),S(-e.top,"reset",0)}}else e=s.children.eq(s.active.index*x()).position(),s.active.index===f()-1&&(s.active.last=!0),void 0!==e&&("horizontal"===s.settings.mode?S(-e.left,"reset",0):"vertical"===s.settings.mode&&S(-e.top,"reset",0))},S=function(e,t,i,n){if(s.usingCSS){var r="vertical"===s.settings.mode?"translate3d(0, "+e+"px, 0)":"translate3d("+e+"px, 0, 0)";o.css("-"+s.cssPrefix+"-transition-duration",i/1e3+"s"),"slide"===t?setTimeout(function(){o.css(s.animProp,r),0===e?q():o.bind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd",function(){o.unbind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd"),q()})},0):"reset"===t?o.css(s.animProp,r):"ticker"===t&&(o.css("-"+s.cssPrefix+"-transition-timing-function","linear"),o.css(s.animProp,r),o.bind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd",function(){o.unbind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd"),S(n.resetValue,"reset",0),L()}))}else{var a={};a[s.animProp]=e,"slide"===t?o.animate(a,i,s.settings.easing,function(){q()}):"reset"===t?o.css(s.animProp,e):"ticker"===t&&o.animate(a,speed,"linear",function(){S(n.resetValue,"reset",0),L()})}},b=function(){for(var t="",i=f(),n=0;i>n;n++){var o="";s.settings.buildPager&&e.isFunction(s.settings.buildPager)||s.settings.pagerCustom?(o=s.settings.buildPager(n),s.pagerEl.addClass("bx-custom-pager")):(o=n+1,s.pagerEl.addClass("bx-default-pager")),t+='<div class="bx-pager-item"><a href="" data-slide-index="'+n+'" class="bx-pager-link">'+o+"</a></div>"}s.pagerEl.html(t)},w=function(){s.settings.pagerCustom?s.pagerEl=e(s.settings.pagerCustom):(s.pagerEl=e('<div class="bx-pager" />'),s.settings.pagerSelector?e(s.settings.pagerSelector).html(s.pagerEl):s.controls.el.addClass("bx-has-pager").append(s.pagerEl),b()),s.pagerEl.on("click touchend","a",z)},C=function(){s.controls.next=e('<a class="bx-next" href="">'+s.settings.nextText+"</a>"),s.controls.prev=e('<a class="bx-prev" href="">'+s.settings.prevText+"</a>"),s.controls.next.bind("click touchend",E),s.controls.prev.bind("click touchend",y),s.settings.nextSelector&&e(s.settings.nextSelector).append(s.controls.next),s.settings.prevSelector&&e(s.settings.prevSelector).append(s.controls.prev),s.settings.nextSelector||s.settings.prevSelector||(s.controls.directionEl=e('<div class="bx-controls-direction" />'),s.controls.directionEl.append(s.controls.prev).append(s.controls.next),s.controls.el.addClass("bx-has-controls-direction").append(s.controls.directionEl))},T=function(){s.controls.start=e('<div class="bx-controls-auto-item"><a class="bx-start" href="">'+s.settings.startText+"</a></div>"),s.controls.stop=e('<div class="bx-controls-auto-item"><a class="bx-stop" href="">'+s.settings.stopText+"</a></div>"),s.controls.autoEl=e('<div class="bx-controls-auto" />'),s.controls.autoEl.on("click",".bx-start",k),s.controls.autoEl.on("click",".bx-stop",M),s.settings.autoControlsCombine?s.controls.autoEl.append(s.controls.start):s.controls.autoEl.append(s.controls.start).append(s.controls.stop),s.settings.autoControlsSelector?e(s.settings.autoControlsSelector).html(s.controls.autoEl):s.controls.el.addClass("bx-has-controls-auto").append(s.controls.autoEl),D(s.settings.autoStart?"stop":"start")},P=function(){s.children.each(function(){var t=e(this).find("img:first").attr("title");void 0!==t&&(""+t).length&&e(this).append('<div class="bx-caption"><span>'+t+"</span></div>")})},E=function(e){e.preventDefault(),s.controls.el.hasClass("disabled")||(s.settings.auto&&o.stopAuto(),o.goToNextSlide())},y=function(e){e.preventDefault(),s.controls.el.hasClass("disabled")||(s.settings.auto&&o.stopAuto(),o.goToPrevSlide())},k=function(e){o.startAuto(),e.preventDefault()},M=function(e){o.stopAuto(),e.preventDefault()},z=function(t){if(t.preventDefault(),!s.controls.el.hasClass("disabled")){s.settings.auto&&o.stopAuto();var i=e(t.currentTarget);if(void 0!==i.attr("data-slide-index")){var n=parseInt(i.attr("data-slide-index"));n!==s.active.index&&o.goToSlide(n)}}},I=function(t){var i=s.children.length;return"short"===s.settings.pagerType?(s.settings.maxSlides>1&&(i=Math.ceil(s.children.length/s.settings.maxSlides)),void s.pagerEl.html(t+1+s.settings.pagerShortSeparator+i)):(s.pagerEl.find("a").removeClass("active"),void s.pagerEl.each(function(i,n){e(n).find("a").eq(t).addClass("active")}))},q=function(){if(s.settings.infiniteLoop){var e="";0===s.active.index?e=s.children.eq(0).position():s.active.index===f()-1&&s.carousel?e=s.children.eq((f()-1)*x()).position():s.active.index===s.children.length-1&&(e=s.children.eq(s.children.length-1).position()),e&&("horizontal"===s.settings.mode?S(-e.left,"reset",0):"vertical"===s.settings.mode&&S(-e.top,"reset",0))}s.working=!1,s.settings.onSlideAfter(s.children.eq(s.active.index),s.oldIndex,s.active.index)},D=function(e){s.settings.autoControlsCombine?s.controls.autoEl.html(s.controls[e]):(s.controls.autoEl.find("a").removeClass("active"),s.controls.autoEl.find("a:not(.bx-"+e+")").addClass("active"))},W=function(){1===f()?(s.controls.prev.addClass("disabled"),s.controls.next.addClass("disabled")):!s.settings.infiniteLoop&&s.settings.hideControlOnEnd&&(0===s.active.index?(s.controls.prev.addClass("disabled"),s.controls.next.removeClass("disabled")):s.active.index===f()-1?(s.controls.next.addClass("disabled"),s.controls.prev.removeClass("disabled")):(s.controls.prev.removeClass("disabled"),s.controls.next.removeClass("disabled")))},A=function(){if(s.settings.autoDelay>0){setTimeout(o.startAuto,s.settings.autoDelay)}else o.startAuto(),e(window).focus(function(){o.startAuto()}).blur(function(){o.stopAuto()});s.settings.autoHover&&o.hover(function(){s.interval&&(o.stopAuto(!0),s.autoPaused=!0)},function(){s.autoPaused&&(o.startAuto(!0),s.autoPaused=null)})},H=function(){var t=0;if("next"===s.settings.autoDirection)o.append(s.children.clone().addClass("bx-clone"));else{o.prepend(s.children.clone().addClass("bx-clone"));var i=s.children.first().position();t="horizontal"===s.settings.mode?-i.left:-i.top}if(S(t,"reset",0),s.settings.pager=!1,s.settings.controls=!1,s.settings.autoControls=!1,s.settings.tickerHover)if(s.usingCSS){var n,r="horizontal"==s.settings.mode?4:5;s.viewport.hover(function(){var e=o.css("-"+s.cssPrefix+"-transform");n=parseFloat(e.split(",")[r]),S(n,"reset",0)},function(){var t=0;s.children.each(function(){t+="horizontal"==s.settings.mode?e(this).outerWidth(!0):e(this).outerHeight(!0)});var i=s.settings.speed/t,o=("horizontal"==s.settings.mode?"left":"top",i*(t-Math.abs(parseInt(n))));L(o)})}else s.viewport.hover(function(){o.stop()},function(){var t=0;s.children.each(function(){t+="horizontal"==s.settings.mode?e(this).outerWidth(!0):e(this).outerHeight(!0)});var i=s.settings.speed/t,n="horizontal"==s.settings.mode?"left":"top",r=i*(t-Math.abs(parseInt(o.css(n))));L(r)});L()},L=function(e){speed=e?e:s.settings.speed;var t={left:0,top:0},i={left:0,top:0};"next"===s.settings.autoDirection?t=o.find(".bx-clone").first().position():i=s.children.first().position();var n="horizontal"===s.settings.mode?-t.left:-t.top,r="horizontal"===s.settings.mode?-i.left:-i.top,a={resetValue:r};S(n,"ticker",speed,a)},F=function(t){var i=e(window),n={top:i.scrollTop(),left:i.scrollLeft()};n.right=n.left+i.width(),n.bottom=n.top+i.height();var s=t.offset();return s.right=s.left+t.outerWidth(),s.bottom=s.top+t.outerHeight(),!(n.right<s.left||n.left>s.right||n.bottom<s.top||n.top>s.bottom)},N=function(e){var t=document.activeElement.tagName.toLowerCase(),i="input|textarea",n=new RegExp(t,["i"]),s=n.exec(i);if(null==s&&F(o)){if(39==e.keyCode)return E(e),!1;if(37==e.keyCode)return y(e),!1}},O=function(){s.touch={start:{x:0,y:0},end:{x:0,y:0}},s.viewport.bind("touchstart MSPointerDown pointerdown",X),s.viewport.on("click",".bxslider a",function(e){s.viewport.hasClass("click-disabled")&&(e.preventDefault(),s.viewport.removeClass("click-disabled"))})},X=function(e){if(s.controls.el.addClass("disabled"),s.working)e.preventDefault(),s.controls.el.removeClass("disabled");else{s.touch.originalPos=o.position();var t=e.originalEvent,i="undefined"!=typeof t.changedTouches?t.changedTouches:[t];s.touch.start.x=i[0].pageX,s.touch.start.y=i[0].pageY,s.viewport.get(0).setPointerCapture&&(s.pointerId=t.pointerId,s.viewport.get(0).setPointerCapture(s.pointerId)),s.viewport.bind("touchmove MSPointerMove pointermove",R),s.viewport.bind("touchend MSPointerUp pointerup",V),s.viewport.bind("MSPointerCancel pointercancel",Y)}},Y=function(){S(s.touch.originalPos.left,"reset",0),s.controls.el.removeClass("disabled"),s.viewport.unbind("MSPointerCancel pointercancel",Y),s.viewport.unbind("touchmove MSPointerMove pointermove",R),s.viewport.unbind("touchend MSPointerUp pointerup",V),s.viewport.get(0).releasePointerCapture&&s.viewport.get(0).releasePointerCapture(s.pointerId)},R=function(e){var t=e.originalEvent,i="undefined"!=typeof t.changedTouches?t.changedTouches:[t],n=Math.abs(i[0].pageX-s.touch.start.x),o=Math.abs(i[0].pageY-s.touch.start.y);if(3*n>o&&s.settings.preventDefaultSwipeX?e.preventDefault():3*o>n&&s.settings.preventDefaultSwipeY&&e.preventDefault(),"fade"!==s.settings.mode&&s.settings.oneToOneTouch){var r=0,a=0;"horizontal"===s.settings.mode?(a=i[0].pageX-s.touch.start.x,r=s.touch.originalPos.left+a):(a=i[0].pageY-s.touch.start.y,r=s.touch.originalPos.top+a),S(r,"reset",0)}},V=function(e){s.viewport.unbind("touchmove MSPointerMove pointermove",R),s.controls.el.removeClass("disabled");var t=e.originalEvent,i="undefined"!=typeof t.changedTouches?t.changedTouches:[t],n=0,r=0;s.touch.end.x=i[0].pageX,s.touch.end.y=i[0].pageY,"fade"===s.settings.mode?(r=Math.abs(s.touch.start.x-s.touch.end.x),r>=s.settings.swipeThreshold&&(s.touch.start.x>s.touch.end.x?o.goToNextSlide():o.goToPrevSlide(),o.stopAuto())):("horizontal"===s.settings.mode?(r=s.touch.end.x-s.touch.start.x,n=s.touch.originalPos.left):(r=s.touch.end.y-s.touch.start.y,n=s.touch.originalPos.top),!s.settings.infiniteLoop&&(0===s.active.index&&r>0||s.active.last&&0>r)?S(n,"reset",200):Math.abs(r)>=s.settings.swipeThreshold?(0>r?o.goToNextSlide():o.goToPrevSlide(),o.stopAuto()):S(n,"reset",200)),s.viewport.unbind("touchend MSPointerUp pointerup",V),s.viewport.get(0).releasePointerCapture&&s.viewport.get(0).releasePointerCapture(s.pointerId)},Z=function(){if(s.initialized)if(s.working)window.setTimeout(Z,10);else{var t=e(window).width(),i=e(window).height();(r!==t||a!==i)&&(r=t,a=i,o.redrawSlider(),s.settings.onSliderResize.call(o,s.active.index))}};return o.goToSlide=function(t,i){if(!s.working&&s.active.index!==t){s.working=!0,s.oldIndex=s.active.index,s.active.index=0>t?f()-1:t>=f()?0:t;var n=!0;if(n=s.settings.onSlideBefore(s.children.eq(s.active.index),s.oldIndex,s.active.index),"undefined"!=typeof n&&!n)return s.active.index=s.oldIndex,void(s.working=!1);if("next"===i?s.settings.onSlideNext(s.children.eq(s.active.index),s.oldIndex,s.active.index)||(n=!1):"prev"===i&&(s.settings.onSlidePrev(s.children.eq(s.active.index),s.oldIndex,s.active.index)||(n=!1)),"undefined"!=typeof n&&!n)return s.active.index=s.oldIndex,void(s.working=!1);if(s.active.last=s.active.index>=f()-1,(s.settings.pager||s.settings.pagerCustom)&&I(s.active.index),s.settings.controls&&W(),"fade"===s.settings.mode)s.settings.adaptiveHeight&&s.viewport.height()!==p()&&s.viewport.animate({height:p()},s.settings.adaptiveHeightSpeed),s.children.filter(":visible").fadeOut(s.settings.speed).css({zIndex:0}),s.children.eq(s.active.index).css("zIndex",s.settings.slideZIndex+1).fadeIn(s.settings.speed,function(){e(this).css("zIndex",s.settings.slideZIndex),q()});else{s.settings.adaptiveHeight&&s.viewport.height()!==p()&&s.viewport.animate({height:p()},s.settings.adaptiveHeightSpeed);var r=0,a={left:0,top:0},l=null;if(!s.settings.infiniteLoop&&s.carousel&&s.active.last)if("horizontal"===s.settings.mode)l=s.children.eq(s.children.length-1),a=l.position(),r=s.viewport.width()-l.outerWidth();else{var d=s.children.length-s.settings.minSlides;a=s.children.eq(d).position()}else if(s.carousel&&s.active.last&&"prev"===i){var c=1===s.settings.moveSlides?s.settings.maxSlides-x():(f()-1)*x()-(s.children.length-s.settings.maxSlides);l=o.children(".bx-clone").eq(c),a=l.position()}else if("next"===i&&0===s.active.index)a=o.find("> .bx-clone").eq(s.settings.maxSlides).position(),s.active.last=!1;else if(t>=0){var g=t*x();a=s.children.eq(g).position()}if("undefined"!=typeof a){var u="horizontal"===s.settings.mode?-(a.left-r):-a.top;S(u,"slide",s.settings.speed)}}}},o.goToNextSlide=function(){if(s.settings.infiniteLoop||!s.active.last){var e=parseInt(s.active.index)+1;o.goToSlide(e,"next")}},o.goToPrevSlide=function(){if(s.settings.infiniteLoop||0!==s.active.index){var e=parseInt(s.active.index)-1;o.goToSlide(e,"prev")}},o.startAuto=function(e){s.interval||(s.interval=setInterval(function(){"next"===s.settings.autoDirection?o.goToNextSlide():o.goToPrevSlide()},s.settings.pause),s.settings.autoControls&&e!==!0&&D("stop"))},o.stopAuto=function(e){s.interval&&(clearInterval(s.interval),s.interval=null,s.settings.autoControls&&e!==!0&&D("start"))},o.getCurrentSlide=function(){return s.active.index},o.getCurrentSlideElement=function(){return s.children.eq(s.active.index)},o.getSlideCount=function(){return s.children.length},o.isWorking=function(){return s.working},o.redrawSlider=function(){s.children.add(o.find(".bx-clone")).outerWidth(h()),s.viewport.css("height",p()),s.settings.ticker||m(),s.active.last&&(s.active.index=f()-1),s.active.index>=f()&&(s.active.last=!0),s.settings.pager&&!s.settings.pagerCustom&&(b(),I(s.active.index))},o.destroySlider=function(){s.initialized&&(s.initialized=!1,e(".bx-clone",this).remove(),s.children.each(function(){void 0!==e(this).data("origStyle")?e(this).attr("style",e(this).data("origStyle")):e(this).removeAttr("style")}),void 0!==e(this).data("origStyle")?this.attr("style",e(this).data("origStyle")):e(this).removeAttr("style"),e(this).unwrap().unwrap(),s.controls.el&&s.controls.el.remove(),s.controls.next&&s.controls.next.remove(),s.controls.prev&&s.controls.prev.remove(),s.pagerEl&&s.settings.controls&&!s.settings.pagerCustom&&s.pagerEl.remove(),e(".bx-caption",this).remove(),s.controls.autoEl&&s.controls.autoEl.remove(),clearInterval(s.interval),s.settings.responsive&&e(window).unbind("resize",Z),s.settings.keyboardEnabled&&e(document).unbind("keydown",N))},o.reloadSlider=function(e){void 0!==e&&(n=e),o.destroySlider(),l()},l(),this}}(jQuery);
/*
*	ImageZoom - Responsive jQuery Image Zoom Pluin
*	by hkeyjun
*   http://codecanyon.net/user/hkeyjun	
*/
!function(a,b){a.ImageZoom=function(c,d){function f(a){var b=parseInt(a);return b=isNaN(b)?0:b}var e=this;e.$el=a(c),e.$el.data("imagezoom",e),e.init=function(b){e.options=a.extend({},a.ImageZoom.defaults,b),e.$viewer=a('<div class="zm-viewer '+e.options.zoomViewerClass+'"></div>').appendTo("body"),e.$handler=a('<div class="zm-handler'+e.options.zoomHandlerClass+'"></div>').appendTo("body"),e.isBigImageReady=-1,e.$largeImg=null,e.isActive=!1,e.$handlerArea=null,e.isWebkit=/chrome/.test(navigator.userAgent.toLowerCase())||/safari/.test(navigator.userAgent.toLowerCase()),e.evt={x:-1,y:-1},e.options.bigImageSrc=""==e.options.bigImageSrc?e.$el.attr("src"):e.options.bigImageSrc,(new Image).src=e.options.bigImageSrc,e.callIndex=a.ImageZoom._calltimes+1,e.animateTimer=null,a.ImageZoom._calltimes+=1,a(document).bind("mousemove.imagezoom"+e.callIndex,function(a){e.isActive&&e.moveHandler(a.pageX,a.pageY)}),e.$el.bind("mouseover.imagezoom",function(a){e.isActive=!0,e.showViewer(a)})},e.moveHandler=function(a,c){var i,j,k,l,m,n,o,p,d=e.$el.offset(),g=e.$el.outerWidth(!1),h=e.$el.outerHeight(!1);a>=d.left&&a<=d.left+g&&c>=d.top&&c<=d.top+h?(d.left=d.left+f(e.$el.css("borderLeftWidth"))+f(e.$el.css("paddingLeft")),d.top=d.top+f(e.$el.css("borderTopWidth"))+f(e.$el.css("paddingTop")),g=e.$el.width(),h=e.$el.height(),a>=d.left&&a<=d.left+g&&c>=d.top&&c<=d.top+h&&(e.evt={x:a,y:c},"follow"==e.options.type&&e.$viewer.css({top:c-e.$viewer.outerHeight()/2,left:a-e.$viewer.outerWidth()/2}),1==e.isBigImageReady&&(k=c-d.top,l=a-d.left,"inner"==e.options.type?(i=-e.$largeImg.height()*k/h+k,j=-e.$largeImg.width()*l/g+l):"standard"==e.options.type?(m=l-e.$handlerArea.width()/2,n=k-e.$handlerArea.height()/2,o=e.$handlerArea.width(),p=e.$handlerArea.height(),0>m?m=0:m>g-o&&(m=g-o),0>n?n=0:n>h-p&&(n=h-p),j=-m/e.scale,i=-n/e.scale,e.isWebkit?(e.$handlerArea.css({opacity:.99}),setTimeout(function(){e.$handlerArea.css({top:n,left:m,opacity:1})},0)):e.$handlerArea.css({top:n,left:m})):"follow"==e.options.type&&(i=-e.$largeImg.height()/h*k+e.options.zoomSize[1]/2,j=-e.$largeImg.width()/g*l+e.options.zoomSize[0]/2,-i>e.$largeImg.height()-e.options.zoomSize[1]?i=-(e.$largeImg.height()-e.options.zoomSize[1]):i>0&&(i=0),-j>e.$largeImg.width()-e.options.zoomSize[0]?j=-(e.$largeImg.width()-e.options.zoomSize[0]):j>0&&(j=0)),e.options.smoothMove?(b.clearTimeout(e.animateTimer),e.smoothMove(j,i)):e.$viewer.find("img").css({top:i,left:j})))):(e.isActive=!1,e.$viewer.hide(),e.$handler.hide(),e.options.onHide(e),b.clearTimeout(e.animateTimer),e.animateTimer=null)},e.showViewer=function(b){var k,l,m,n,o,c=e.$el.offset().top,d=f(e.$el.css("borderTopWidth")),g=f(e.$el.css("paddingTop")),h=e.$el.offset().left,i=f(e.$el.css("borderLeftWidth")),j=f(e.$el.css("paddingLeft"));c=c+d+g,h=h+i+j,k=e.$el.width(),l=e.$el.height(),e.isBigImageReady<1&&a("div",e.$viewer).remove(),"inner"==e.options.type?e.$viewer.css({top:c,left:h,width:k,height:l}).show():"standard"==e.options.type?(m=""==e.options.alignTo?e.$el:a("#"+e.options.alignTo),"left"==e.options.position?(n=m.offset().left-e.options.zoomSize[0]-e.options.offset[0],o=m.offset().top+e.options.offset[1]):"right"==e.options.position&&(n=m.offset().left+m.width()+e.options.offset[0],o=m.offset().top+e.options.offset[1]),e.$viewer.css({top:o,left:n,width:e.options.zoomSize[0],height:e.options.zoomSize[1]}).show(),e.$handlerArea&&(e.scale=k/e.$largeImg.width(),e.$handlerArea.css({width:e.$viewer.width()*e.scale,height:e.$viewer.height()*e.scale}))):"follow"==e.options.type&&e.$viewer.css({width:e.options.zoomSize[0],height:e.options.zoomSize[1],top:b.pageY-e.options.zoomSize[1]/2,left:b.pageX-e.options.zoomSize[0]/2}).show(),e.$handler.css({top:c,left:h,width:k,height:l}).show(),e.options.onShow(e),-1==e.isBigImageReady&&(e.isBigImageReady=0,fastImg(e.options.bigImageSrc,function(){if(a.trim(a(this).attr("src"))==a.trim(e.options.bigImageSrc)){if(e.$viewer.append('<img src="'+e.$el.attr("src")+'" class="zm-fast" style="position:absolute;width:'+this.width+"px;height:"+this.height+'px">'),e.isBigImageReady=1,e.$largeImg=a('<img src="'+e.options.bigImageSrc+'" style="position:absolute;width:'+this.width+"px;height:"+this.height+'px">'),e.$viewer.append(e.$largeImg),"standard"==e.options.type){var c=k/this.width;e.$handlerArea=a('<div class="zm-handlerarea" style="width:'+e.$viewer.width()*c+"px;height:"+e.$viewer.height()*c+'px"></div>').appendTo(e.$handler),e.scale=c}-1==e.evt.x&&-1==e.evt.y?e.moveHandler(b.pageX,b.pageY):e.moveHandler(e.evt.x,e.evt.y),e.options.showDescription&&e.$el.attr("alt")&&""!=a.trim(e.$el.attr("alt"))&&e.$viewer.append('<div class="'+e.options.descriptionClass+'">'+e.$el.attr("alt")+"</div>")}},function(){},function(){}))},e.changeImage=function(a,b){this.$el.attr("src",a),this.isBigImageReady=-1,this.options.bigImageSrc="string"==typeof b?b:a,e.options.preload&&((new Image).src=this.options.bigImageSrc),this.$viewer.hide().empty(),this.$handler.hide().empty(),this.$handlerArea=null},e.changeZoomSize=function(a,b){e.options.zoomSize=[a,b]},e.destroy=function(){a(document).unbind("mousemove.imagezoom"+e.callIndex),this.$el.unbind(".imagezoom"),this.$viewer.remove(),this.$handler.remove(),this.$el.removeData("imagezoom")},e.smoothMove=function(a,c){var g,h,i,j,k,d=10,f=parseInt(e.$largeImg.css("top"));return f=isNaN(f)?0:f,g=parseInt(e.$largeImg.css("left")),g=isNaN(g)?0:g,c=parseInt(c),a=parseInt(a),f==c&&g==a?(b.clearTimeout(e.animateTimer),e.animateTimer=null,void 0):(h=c-f,i=a-g,j=f+h/Math.abs(h)*Math.ceil(Math.abs(h/d)),k=g+i/Math.abs(i)*Math.ceil(Math.abs(i/d)),e.$viewer.find("img").css({top:j,left:k}),e.animateTimer=setTimeout(function(){e.smoothMove(a,c)},10),void 0)},e.init(d)},a.ImageZoom.defaults={bigImageSrc:"",preload:!0,type:"inner",smoothMove:!0,position:"right",offset:[10,0],alignTo:"",zoomSize:[100,100],descriptionClass:"zm-description",zoomViewerClass:"",zoomHandlerClass:"",showDescription:!0,onShow:function(){},onHide:function(){}},a.ImageZoom._calltimes=0,a.fn.ImageZoom=function(b){return this.each(function(){new a.ImageZoom(this,b)})}}(jQuery,window);var fastImg=function(){var a=[],b=null,c=function(){for(var b=0;b<a.length;b++)a[b].end?a.splice(b--,1):a[b]();!a.length&&d()},d=function(){clearInterval(b),b=null};return function(d,e,f,g){var h,i,j,k,l,m=new Image;return m.src=d,m.complete?(e.call(m),f&&f.call(m),void 0):(i=m.width,j=m.height,m.onerror=function(){g&&g.call(m),h.end=!0,m=m.onload=m.onerror=null},h=function(){k=m.width,l=m.height,(k!==i||l!==j||k*l>1024)&&(e.call(m),h.end=!0)},h(),m.onload=function(){!h.end&&h(),f&&f.call(m),m=m.onload=m.onerror=null},h.end||(a.push(h),null===b&&(b=setInterval(c,40))),void 0)}}();
/*! Magnific Popup - v1.0.0 - 2015-01-03
* http://dimsemenov.com/plugins/magnific-popup/
* Copyright (c) 2015 Dmitry Semenov; */
!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):a("object"==typeof exports?require("jquery"):window.jQuery||window.Zepto)}(function(a){var b,c,d,e,f,g,h="Close",i="BeforeClose",j="AfterClose",k="BeforeAppend",l="MarkupParse",m="Open",n="Change",o="mfp",p="."+o,q="mfp-ready",r="mfp-removing",s="mfp-prevent-close",t=function(){},u=!!window.jQuery,v=a(window),w=function(a,c){b.ev.on(o+a+p,c)},x=function(b,c,d,e){var f=document.createElement("div");return f.className="mfp-"+b,d&&(f.innerHTML=d),e?c&&c.appendChild(f):(f=a(f),c&&f.appendTo(c)),f},y=function(c,d){b.ev.triggerHandler(o+c,d),b.st.callbacks&&(c=c.charAt(0).toLowerCase()+c.slice(1),b.st.callbacks[c]&&b.st.callbacks[c].apply(b,a.isArray(d)?d:[d]))},z=function(c){return c===g&&b.currTemplate.closeBtn||(b.currTemplate.closeBtn=a(b.st.closeMarkup.replace("%title%",b.st.tClose)),g=c),b.currTemplate.closeBtn},A=function(){a.magnificPopup.instance||(b=new t,b.init(),a.magnificPopup.instance=b)},B=function(){var a=document.createElement("p").style,b=["ms","O","Moz","Webkit"];if(void 0!==a.transition)return!0;for(;b.length;)if(b.pop()+"Transition"in a)return!0;return!1};t.prototype={constructor:t,init:function(){var c=navigator.appVersion;b.isIE7=-1!==c.indexOf("MSIE 7."),b.isIE8=-1!==c.indexOf("MSIE 8."),b.isLowIE=b.isIE7||b.isIE8,b.isAndroid=/android/gi.test(c),b.isIOS=/iphone|ipad|ipod/gi.test(c),b.supportsTransition=B(),b.probablyMobile=b.isAndroid||b.isIOS||/(Opera Mini)|Kindle|webOS|BlackBerry|(Opera Mobi)|(Windows Phone)|IEMobile/i.test(navigator.userAgent),d=a(document),b.popupsCache={}},open:function(c){var e;if(c.isObj===!1){b.items=c.items.toArray(),b.index=0;var g,h=c.items;for(e=0;e<h.length;e++)if(g=h[e],g.parsed&&(g=g.el[0]),g===c.el[0]){b.index=e;break}}else b.items=a.isArray(c.items)?c.items:[c.items],b.index=c.index||0;if(b.isOpen)return void b.updateItemHTML();b.types=[],f="",b.ev=c.mainEl&&c.mainEl.length?c.mainEl.eq(0):d,c.key?(b.popupsCache[c.key]||(b.popupsCache[c.key]={}),b.currTemplate=b.popupsCache[c.key]):b.currTemplate={},b.st=a.extend(!0,{},a.magnificPopup.defaults,c),b.fixedContentPos="auto"===b.st.fixedContentPos?!b.probablyMobile:b.st.fixedContentPos,b.st.modal&&(b.st.closeOnContentClick=!1,b.st.closeOnBgClick=!1,b.st.showCloseBtn=!1,b.st.enableEscapeKey=!1),b.bgOverlay||(b.bgOverlay=x("bg").on("click"+p,function(){b.close()}),b.wrap=x("wrap").attr("tabindex",-1).on("click"+p,function(a){b._checkIfClose(a.target)&&b.close()}),b.container=x("container",b.wrap)),b.contentContainer=x("content"),b.st.preloader&&(b.preloader=x("preloader",b.container,b.st.tLoading));var i=a.magnificPopup.modules;for(e=0;e<i.length;e++){var j=i[e];j=j.charAt(0).toUpperCase()+j.slice(1),b["init"+j].call(b)}y("BeforeOpen"),b.st.showCloseBtn&&(b.st.closeBtnInside?(w(l,function(a,b,c,d){c.close_replaceWith=z(d.type)}),f+=" mfp-close-btn-in"):b.wrap.append(z())),b.st.alignTop&&(f+=" mfp-align-top"),b.wrap.css(b.fixedContentPos?{overflow:b.st.overflowY,overflowX:"hidden",overflowY:b.st.overflowY}:{top:v.scrollTop(),position:"absolute"}),(b.st.fixedBgPos===!1||"auto"===b.st.fixedBgPos&&!b.fixedContentPos)&&b.bgOverlay.css({height:d.height(),position:"absolute"}),b.st.enableEscapeKey&&d.on("keyup"+p,function(a){27===a.keyCode&&b.close()}),v.on("resize"+p,function(){b.updateSize()}),b.st.closeOnContentClick||(f+=" mfp-auto-cursor"),f&&b.wrap.addClass(f);var k=b.wH=v.height(),n={};if(b.fixedContentPos&&b._hasScrollBar(k)){var o=b._getScrollbarSize();o&&(n.marginRight=o)}b.fixedContentPos&&(b.isIE7?a("body, html").css("overflow","hidden"):n.overflow="hidden");var r=b.st.mainClass;return b.isIE7&&(r+=" mfp-ie7"),r&&b._addClassToMFP(r),b.updateItemHTML(),y("BuildControls"),a("html").css(n),b.bgOverlay.add(b.wrap).prependTo(b.st.prependTo||a(document.body)),b._lastFocusedEl=document.activeElement,setTimeout(function(){b.content?(b._addClassToMFP(q),b._setFocus()):b.bgOverlay.addClass(q),d.on("focusin"+p,b._onFocusIn)},16),b.isOpen=!0,b.updateSize(k),y(m),c},close:function(){b.isOpen&&(y(i),b.isOpen=!1,b.st.removalDelay&&!b.isLowIE&&b.supportsTransition?(b._addClassToMFP(r),setTimeout(function(){b._close()},b.st.removalDelay)):b._close())},_close:function(){y(h);var c=r+" "+q+" ";if(b.bgOverlay.detach(),b.wrap.detach(),b.container.empty(),b.st.mainClass&&(c+=b.st.mainClass+" "),b._removeClassFromMFP(c),b.fixedContentPos){var e={marginRight:""};b.isIE7?a("body, html").css("overflow",""):e.overflow="",a("html").css(e)}d.off("keyup"+p+" focusin"+p),b.ev.off(p),b.wrap.attr("class","mfp-wrap").removeAttr("style"),b.bgOverlay.attr("class","mfp-bg"),b.container.attr("class","mfp-container"),!b.st.showCloseBtn||b.st.closeBtnInside&&b.currTemplate[b.currItem.type]!==!0||b.currTemplate.closeBtn&&b.currTemplate.closeBtn.detach(),b._lastFocusedEl&&a(b._lastFocusedEl).focus(),b.currItem=null,b.content=null,b.currTemplate=null,b.prevHeight=0,y(j)},updateSize:function(a){if(b.isIOS){var c=document.documentElement.clientWidth/window.innerWidth,d=window.innerHeight*c;b.wrap.css("height",d),b.wH=d}else b.wH=a||v.height();b.fixedContentPos||b.wrap.css("height",b.wH),y("Resize")},updateItemHTML:function(){var c=b.items[b.index];b.contentContainer.detach(),b.content&&b.content.detach(),c.parsed||(c=b.parseEl(b.index));var d=c.type;if(y("BeforeChange",[b.currItem?b.currItem.type:"",d]),b.currItem=c,!b.currTemplate[d]){var f=b.st[d]?b.st[d].markup:!1;y("FirstMarkupParse",f),b.currTemplate[d]=f?a(f):!0}e&&e!==c.type&&b.container.removeClass("mfp-"+e+"-holder");var g=b["get"+d.charAt(0).toUpperCase()+d.slice(1)](c,b.currTemplate[d]);b.appendContent(g,d),c.preloaded=!0,y(n,c),e=c.type,b.container.prepend(b.contentContainer),y("AfterChange")},appendContent:function(a,c){b.content=a,a?b.st.showCloseBtn&&b.st.closeBtnInside&&b.currTemplate[c]===!0?b.content.find(".mfp-close").length||b.content.append(z()):b.content=a:b.content="",y(k),b.container.addClass("mfp-"+c+"-holder"),b.contentContainer.append(b.content)},parseEl:function(c){var d,e=b.items[c];if(e.tagName?e={el:a(e)}:(d=e.type,e={data:e,src:e.src}),e.el){for(var f=b.types,g=0;g<f.length;g++)if(e.el.hasClass("mfp-"+f[g])){d=f[g];break}e.src=e.el.attr("data-mfp-src"),e.src||(e.src=e.el.attr("href"))}return e.type=d||b.st.type||"inline",e.index=c,e.parsed=!0,b.items[c]=e,y("ElementParse",e),b.items[c]},addGroup:function(a,c){var d=function(d){d.mfpEl=this,b._openClick(d,a,c)};c||(c={});var e="click.magnificPopup";c.mainEl=a,c.items?(c.isObj=!0,a.off(e).on(e,d)):(c.isObj=!1,c.delegate?a.off(e).on(e,c.delegate,d):(c.items=a,a.off(e).on(e,d)))},_openClick:function(c,d,e){var f=void 0!==e.midClick?e.midClick:a.magnificPopup.defaults.midClick;if(f||2!==c.which&&!c.ctrlKey&&!c.metaKey){var g=void 0!==e.disableOn?e.disableOn:a.magnificPopup.defaults.disableOn;if(g)if(a.isFunction(g)){if(!g.call(b))return!0}else if(v.width()<g)return!0;c.type&&(c.preventDefault(),b.isOpen&&c.stopPropagation()),e.el=a(c.mfpEl),e.delegate&&(e.items=d.find(e.delegate)),b.open(e)}},updateStatus:function(a,d){if(b.preloader){c!==a&&b.container.removeClass("mfp-s-"+c),d||"loading"!==a||(d=b.st.tLoading);var e={status:a,text:d};y("UpdateStatus",e),a=e.status,d=e.text,b.preloader.html(d),b.preloader.find("a").on("click",function(a){a.stopImmediatePropagation()}),b.container.addClass("mfp-s-"+a),c=a}},_checkIfClose:function(c){if(!a(c).hasClass(s)){var d=b.st.closeOnContentClick,e=b.st.closeOnBgClick;if(d&&e)return!0;if(!b.content||a(c).hasClass("mfp-close")||b.preloader&&c===b.preloader[0])return!0;if(c===b.content[0]||a.contains(b.content[0],c)){if(d)return!0}else if(e&&a.contains(document,c))return!0;return!1}},_addClassToMFP:function(a){b.bgOverlay.addClass(a),b.wrap.addClass(a)},_removeClassFromMFP:function(a){this.bgOverlay.removeClass(a),b.wrap.removeClass(a)},_hasScrollBar:function(a){return(b.isIE7?d.height():document.body.scrollHeight)>(a||v.height())},_setFocus:function(){(b.st.focus?b.content.find(b.st.focus).eq(0):b.wrap).focus()},_onFocusIn:function(c){return c.target===b.wrap[0]||a.contains(b.wrap[0],c.target)?void 0:(b._setFocus(),!1)},_parseMarkup:function(b,c,d){var e;d.data&&(c=a.extend(d.data,c)),y(l,[b,c,d]),a.each(c,function(a,c){if(void 0===c||c===!1)return!0;if(e=a.split("_"),e.length>1){var d=b.find(p+"-"+e[0]);if(d.length>0){var f=e[1];"replaceWith"===f?d[0]!==c[0]&&d.replaceWith(c):"img"===f?d.is("img")?d.attr("src",c):d.replaceWith('<img src="'+c+'" class="'+d.attr("class")+'" />'):d.attr(e[1],c)}}else b.find(p+"-"+a).html(c)})},_getScrollbarSize:function(){if(void 0===b.scrollbarSize){var a=document.createElement("div");a.style.cssText="width: 99px; height: 99px; overflow: scroll; position: absolute; top: -9999px;",document.body.appendChild(a),b.scrollbarSize=a.offsetWidth-a.clientWidth,document.body.removeChild(a)}return b.scrollbarSize}},a.magnificPopup={instance:null,proto:t.prototype,modules:[],open:function(b,c){return A(),b=b?a.extend(!0,{},b):{},b.isObj=!0,b.index=c||0,this.instance.open(b)},close:function(){return a.magnificPopup.instance&&a.magnificPopup.instance.close()},registerModule:function(b,c){c.options&&(a.magnificPopup.defaults[b]=c.options),a.extend(this.proto,c.proto),this.modules.push(b)},defaults:{disableOn:0,key:null,midClick:!1,mainClass:"",preloader:!0,focus:"",closeOnContentClick:!1,closeOnBgClick:!0,closeBtnInside:!0,showCloseBtn:!0,enableEscapeKey:!0,modal:!1,alignTop:!1,removalDelay:0,prependTo:null,fixedContentPos:"auto",fixedBgPos:"auto",overflowY:"auto",closeMarkup:'<button title="%title%" type="button" class="mfp-close">&times;</button>',tClose:"Close (Esc)",tLoading:"Loading..."}},a.fn.magnificPopup=function(c){A();var d=a(this);if("string"==typeof c)if("open"===c){var e,f=u?d.data("magnificPopup"):d[0].magnificPopup,g=parseInt(arguments[1],10)||0;f.items?e=f.items[g]:(e=d,f.delegate&&(e=e.find(f.delegate)),e=e.eq(g)),b._openClick({mfpEl:e},d,f)}else b.isOpen&&b[c].apply(b,Array.prototype.slice.call(arguments,1));else c=a.extend(!0,{},c),u?d.data("magnificPopup",c):d[0].magnificPopup=c,b.addGroup(d,c);return d};var C,D,E,F="inline",G=function(){E&&(D.after(E.addClass(C)).detach(),E=null)};a.magnificPopup.registerModule(F,{options:{hiddenClass:"hide",markup:"",tNotFound:"Content not found"},proto:{initInline:function(){b.types.push(F),w(h+"."+F,function(){G()})},getInline:function(c,d){if(G(),c.src){var e=b.st.inline,f=a(c.src);if(f.length){var g=f[0].parentNode;g&&g.tagName&&(D||(C=e.hiddenClass,D=x(C),C="mfp-"+C),E=f.after(D).detach().removeClass(C)),b.updateStatus("ready")}else b.updateStatus("error",e.tNotFound),f=a("<div>");return c.inlineElement=f,f}return b.updateStatus("ready"),b._parseMarkup(d,{},c),d}}});var H,I="ajax",J=function(){H&&a(document.body).removeClass(H)},K=function(){J(),b.req&&b.req.abort()};a.magnificPopup.registerModule(I,{options:{settings:null,cursor:"mfp-ajax-cur",tError:'<a href="%url%">The content</a> could not be loaded.'},proto:{initAjax:function(){b.types.push(I),H=b.st.ajax.cursor,w(h+"."+I,K),w("BeforeChange."+I,K)},getAjax:function(c){H&&a(document.body).addClass(H),b.updateStatus("loading");var d=a.extend({url:c.src,success:function(d,e,f){var g={data:d,xhr:f};y("ParseAjax",g),b.appendContent(a(g.data),I),c.finished=!0,J(),b._setFocus(),setTimeout(function(){b.wrap.addClass(q)},16),b.updateStatus("ready"),y("AjaxContentAdded")},error:function(){J(),c.finished=c.loadError=!0,b.updateStatus("error",b.st.ajax.tError.replace("%url%",c.src))}},b.st.ajax.settings);return b.req=a.ajax(d),""}}});var L,M=function(c){if(c.data&&void 0!==c.data.title)return c.data.title;var d=b.st.image.titleSrc;if(d){if(a.isFunction(d))return d.call(b,c);if(c.el)return c.el.attr(d)||""}return""};a.magnificPopup.registerModule("image",{options:{markup:'<div class="mfp-figure"><div class="mfp-close"></div><figure><div class="mfp-img"></div><figcaption><div class="mfp-bottom-bar"><div class="mfp-title"></div><div class="mfp-counter"></div></div></figcaption></figure></div>',cursor:"mfp-zoom-out-cur",titleSrc:"title",verticalFit:!0,tError:'<a href="%url%">The image</a> could not be loaded.'},proto:{initImage:function(){var c=b.st.image,d=".image";b.types.push("image"),w(m+d,function(){"image"===b.currItem.type&&c.cursor&&a(document.body).addClass(c.cursor)}),w(h+d,function(){c.cursor&&a(document.body).removeClass(c.cursor),v.off("resize"+p)}),w("Resize"+d,b.resizeImage),b.isLowIE&&w("AfterChange",b.resizeImage)},resizeImage:function(){var a=b.currItem;if(a&&a.img&&b.st.image.verticalFit){var c=0;b.isLowIE&&(c=parseInt(a.img.css("padding-top"),10)+parseInt(a.img.css("padding-bottom"),10)),a.img.css("max-height",b.wH-c)}},_onImageHasSize:function(a){a.img&&(a.hasSize=!0,L&&clearInterval(L),a.isCheckingImgSize=!1,y("ImageHasSize",a),a.imgHidden&&(b.content&&b.content.removeClass("mfp-loading"),a.imgHidden=!1))},findImageSize:function(a){var c=0,d=a.img[0],e=function(f){L&&clearInterval(L),L=setInterval(function(){return d.naturalWidth>0?void b._onImageHasSize(a):(c>200&&clearInterval(L),c++,void(3===c?e(10):40===c?e(50):100===c&&e(500)))},f)};e(1)},getImage:function(c,d){var e=0,f=function(){c&&(c.img[0].complete?(c.img.off(".mfploader"),c===b.currItem&&(b._onImageHasSize(c),b.updateStatus("ready")),c.hasSize=!0,c.loaded=!0,y("ImageLoadComplete")):(e++,200>e?setTimeout(f,100):g()))},g=function(){c&&(c.img.off(".mfploader"),c===b.currItem&&(b._onImageHasSize(c),b.updateStatus("error",h.tError.replace("%url%",c.src))),c.hasSize=!0,c.loaded=!0,c.loadError=!0)},h=b.st.image,i=d.find(".mfp-img");if(i.length){var j=document.createElement("img");j.className="mfp-img",c.el&&c.el.find("img").length&&(j.alt=c.el.find("img").attr("alt")),c.img=a(j).on("load.mfploader",f).on("error.mfploader",g),j.src=c.src,i.is("img")&&(c.img=c.img.clone()),j=c.img[0],j.naturalWidth>0?c.hasSize=!0:j.width||(c.hasSize=!1)}return b._parseMarkup(d,{title:M(c),img_replaceWith:c.img},c),b.resizeImage(),c.hasSize?(L&&clearInterval(L),c.loadError?(d.addClass("mfp-loading"),b.updateStatus("error",h.tError.replace("%url%",c.src))):(d.removeClass("mfp-loading"),b.updateStatus("ready")),d):(b.updateStatus("loading"),c.loading=!0,c.hasSize||(c.imgHidden=!0,d.addClass("mfp-loading"),b.findImageSize(c)),d)}}});var N,O=function(){return void 0===N&&(N=void 0!==document.createElement("p").style.MozTransform),N};a.magnificPopup.registerModule("zoom",{options:{enabled:!1,easing:"ease-in-out",duration:300,opener:function(a){return a.is("img")?a:a.find("img")}},proto:{initZoom:function(){var a,c=b.st.zoom,d=".zoom";if(c.enabled&&b.supportsTransition){var e,f,g=c.duration,j=function(a){var b=a.clone().removeAttr("style").removeAttr("class").addClass("mfp-animated-image"),d="all "+c.duration/1e3+"s "+c.easing,e={position:"fixed",zIndex:9999,left:0,top:0,"-webkit-backface-visibility":"hidden"},f="transition";return e["-webkit-"+f]=e["-moz-"+f]=e["-o-"+f]=e[f]=d,b.css(e),b},k=function(){b.content.css("visibility","visible")};w("BuildControls"+d,function(){if(b._allowZoom()){if(clearTimeout(e),b.content.css("visibility","hidden"),a=b._getItemToZoom(),!a)return void k();f=j(a),f.css(b._getOffset()),b.wrap.append(f),e=setTimeout(function(){f.css(b._getOffset(!0)),e=setTimeout(function(){k(),setTimeout(function(){f.remove(),a=f=null,y("ZoomAnimationEnded")},16)},g)},16)}}),w(i+d,function(){if(b._allowZoom()){if(clearTimeout(e),b.st.removalDelay=g,!a){if(a=b._getItemToZoom(),!a)return;f=j(a)}f.css(b._getOffset(!0)),b.wrap.append(f),b.content.css("visibility","hidden"),setTimeout(function(){f.css(b._getOffset())},16)}}),w(h+d,function(){b._allowZoom()&&(k(),f&&f.remove(),a=null)})}},_allowZoom:function(){return"image"===b.currItem.type},_getItemToZoom:function(){return b.currItem.hasSize?b.currItem.img:!1},_getOffset:function(c){var d;d=c?b.currItem.img:b.st.zoom.opener(b.currItem.el||b.currItem);var e=d.offset(),f=parseInt(d.css("padding-top"),10),g=parseInt(d.css("padding-bottom"),10);e.top-=a(window).scrollTop()-f;var h={width:d.width(),height:(u?d.innerHeight():d[0].offsetHeight)-g-f};return O()?h["-moz-transform"]=h.transform="translate("+e.left+"px,"+e.top+"px)":(h.left=e.left,h.top=e.top),h}}});var P="iframe",Q="//about:blank",R=function(a){if(b.currTemplate[P]){var c=b.currTemplate[P].find("iframe");c.length&&(a||(c[0].src=Q),b.isIE8&&c.css("display",a?"block":"none"))}};a.magnificPopup.registerModule(P,{options:{markup:'<div class="mfp-iframe-scaler"><div class="mfp-close"></div><iframe class="mfp-iframe" src="//about:blank" frameborder="0" allowfullscreen></iframe></div>',srcAction:"iframe_src",patterns:{youtube:{index:"youtube.com",id:"v=",src:"//www.youtube.com/embed/%id%?autoplay=1"},vimeo:{index:"vimeo.com/",id:"/",src:"//player.vimeo.com/video/%id%?autoplay=1"},gmaps:{index:"//maps.google.",src:"%id%&output=embed"}}},proto:{initIframe:function(){b.types.push(P),w("BeforeChange",function(a,b,c){b!==c&&(b===P?R():c===P&&R(!0))}),w(h+"."+P,function(){R()})},getIframe:function(c,d){var e=c.src,f=b.st.iframe;a.each(f.patterns,function(){return e.indexOf(this.index)>-1?(this.id&&(e="string"==typeof this.id?e.substr(e.lastIndexOf(this.id)+this.id.length,e.length):this.id.call(this,e)),e=this.src.replace("%id%",e),!1):void 0});var g={};return f.srcAction&&(g[f.srcAction]=e),b._parseMarkup(d,g,c),b.updateStatus("ready"),d}}});var S=function(a){var c=b.items.length;return a>c-1?a-c:0>a?c+a:a},T=function(a,b,c){return a.replace(/%curr%/gi,b+1).replace(/%total%/gi,c)};a.magnificPopup.registerModule("gallery",{options:{enabled:!1,arrowMarkup:'<button title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir%"></button>',preload:[0,2],navigateByImgClick:!0,arrows:!0,tPrev:"Previous (Left arrow key)",tNext:"Next (Right arrow key)",tCounter:"%curr% of %total%"},proto:{initGallery:function(){var c=b.st.gallery,e=".mfp-gallery",g=Boolean(a.fn.mfpFastClick);return b.direction=!0,c&&c.enabled?(f+=" mfp-gallery",w(m+e,function(){c.navigateByImgClick&&b.wrap.on("click"+e,".mfp-img",function(){return b.items.length>1?(b.next(),!1):void 0}),d.on("keydown"+e,function(a){37===a.keyCode?b.prev():39===a.keyCode&&b.next()})}),w("UpdateStatus"+e,function(a,c){c.text&&(c.text=T(c.text,b.currItem.index,b.items.length))}),w(l+e,function(a,d,e,f){var g=b.items.length;e.counter=g>1?T(c.tCounter,f.index,g):""}),w("BuildControls"+e,function(){if(b.items.length>1&&c.arrows&&!b.arrowLeft){var d=c.arrowMarkup,e=b.arrowLeft=a(d.replace(/%title%/gi,c.tPrev).replace(/%dir%/gi,"left")).addClass(s),f=b.arrowRight=a(d.replace(/%title%/gi,c.tNext).replace(/%dir%/gi,"right")).addClass(s),h=g?"mfpFastClick":"click";e[h](function(){b.prev()}),f[h](function(){b.next()}),b.isIE7&&(x("b",e[0],!1,!0),x("a",e[0],!1,!0),x("b",f[0],!1,!0),x("a",f[0],!1,!0)),b.container.append(e.add(f))}}),w(n+e,function(){b._preloadTimeout&&clearTimeout(b._preloadTimeout),b._preloadTimeout=setTimeout(function(){b.preloadNearbyImages(),b._preloadTimeout=null},16)}),void w(h+e,function(){d.off(e),b.wrap.off("click"+e),b.arrowLeft&&g&&b.arrowLeft.add(b.arrowRight).destroyMfpFastClick(),b.arrowRight=b.arrowLeft=null})):!1},next:function(){b.direction=!0,b.index=S(b.index+1),b.updateItemHTML()},prev:function(){b.direction=!1,b.index=S(b.index-1),b.updateItemHTML()},goTo:function(a){b.direction=a>=b.index,b.index=a,b.updateItemHTML()},preloadNearbyImages:function(){var a,c=b.st.gallery.preload,d=Math.min(c[0],b.items.length),e=Math.min(c[1],b.items.length);for(a=1;a<=(b.direction?e:d);a++)b._preloadItem(b.index+a);for(a=1;a<=(b.direction?d:e);a++)b._preloadItem(b.index-a)},_preloadItem:function(c){if(c=S(c),!b.items[c].preloaded){var d=b.items[c];d.parsed||(d=b.parseEl(c)),y("LazyLoad",d),"image"===d.type&&(d.img=a('<img class="mfp-img" />').on("load.mfploader",function(){d.hasSize=!0}).on("error.mfploader",function(){d.hasSize=!0,d.loadError=!0,y("LazyLoadError",d)}).attr("src",d.src)),d.preloaded=!0}}}});var U="retina";a.magnificPopup.registerModule(U,{options:{replaceSrc:function(a){return a.src.replace(/\.\w+$/,function(a){return"@2x"+a})},ratio:1},proto:{initRetina:function(){if(window.devicePixelRatio>1){var a=b.st.retina,c=a.ratio;c=isNaN(c)?c():c,c>1&&(w("ImageHasSize."+U,function(a,b){b.img.css({"max-width":b.img[0].naturalWidth/c,width:"100%"})}),w("ElementParse."+U,function(b,d){d.src=a.replaceSrc(d,c)}))}}}}),function(){var b=1e3,c="ontouchstart"in window,d=function(){v.off("touchmove"+f+" touchend"+f)},e="mfpFastClick",f="."+e;a.fn.mfpFastClick=function(e){return a(this).each(function(){var g,h=a(this);if(c){var i,j,k,l,m,n;h.on("touchstart"+f,function(a){l=!1,n=1,m=a.originalEvent?a.originalEvent.touches[0]:a.touches[0],j=m.clientX,k=m.clientY,v.on("touchmove"+f,function(a){m=a.originalEvent?a.originalEvent.touches:a.touches,n=m.length,m=m[0],(Math.abs(m.clientX-j)>10||Math.abs(m.clientY-k)>10)&&(l=!0,d())}).on("touchend"+f,function(a){d(),l||n>1||(g=!0,a.preventDefault(),clearTimeout(i),i=setTimeout(function(){g=!1},b),e())})})}h.on("click"+f,function(){g||e()})})},a.fn.destroyMfpFastClick=function(){a(this).off("touchstart"+f+" click"+f),c&&v.off("touchmove"+f+" touchend"+f)}}(),A()});
jQuery(document).ready(function($) {
    
/**	=============================
    *
    * Helpers
    *
    ============================= */
    
    function jckTrueFalse(val){
        return (parseInt(val) === 1) ? true : false;
    }

/*  =============================
    Variables 
    ============================= */
   	
	var slider_class = ".jck-wt-images",
	    $slider = $(slider_class),
	    slider_wrap_class = '.jck-wt-images-wrap',
	    $slider_wrap = $(slider_wrap_class),
	    thumbnails_class = ".jck-wt-thumbnails",
	    $thumbnails = $(thumbnails_class),
	    $variations_form = $('form.variations_form'),
	    variations_json = $variations_form.attr('data-product_variations'),
	    variations = ( typeof variations_json !== "undefined" || variations_json === "false" ) ? $.parseJSON( variations_json ) : false,
		$img_container = $('.jck-wt-all-images-wrap'),
		swatches_active = ($('#swatches-and-photos-css').length > 0) ? true : false,
		thumbnails_data = false,
		slider_data = false,
		loading_class = "jck-wt-loading",
		thumbnails_active_class = "jck-wt-thumbnails__slide--active",
		images_active_class = "jck-wt-images__slide--active",	
		is_touch_device = !!('ontouchstart' in window),
		zoom_enabled = ( jckTrueFalse(ink_pd_vars.options.enableZoom) && !is_touch_device ) ? true : false,
		fullscreen_button = '<a href="javascript: void(0);" class="jck-wt-fullscreen"><i class="jck-wt-icon-fullscreen"></i></a>';

/*  =============================
    Main Slider Parameters 
    ============================= */
    
    function main_slider_args() {
        
        return  {
                	mode: ink_pd_vars.options.slideMode,
                    speed: parseInt(ink_pd_vars.options.slideSpeed),
                    controls: ( $slider.children().length > 1 ) ? jckTrueFalse(ink_pd_vars.options.enableArrows) : false,
                    infiniteLoop: false,
                    adaptiveHeight: true,
                    pager: ( ink_pd_vars.options.navigationType === "bullets" ) ? true : false,
                    prevText: '<i class="jck-wt-icon-prev"></i>',
                    nextText: '<i class="jck-wt-icon-next"></i>',
                    onSliderLoad: function(){
                        
                        $slider.css({ opacity:1, height: 'auto' });
                        
                        trigger_zoom();
                        setup_slider_controls();
                        
                        if( jckTrueFalse(ink_pd_vars.options.enableLightbox) && $('.jck-wt-fullscreen').length === 0 ) {
                            $('.jck-wt-images-wrap').append(fullscreen_button);
                        }
                        
                        if(is_touch_device) {
                            $('.bx-controls').hide();
                        }
                        
                    },
                    onSlideBefore: function($slide_element, old_index, new_index){
                        
                        destroy_zoom();
                        
                        // add active class
                        $('.'+images_active_class).removeClass(images_active_class);
                        $slide_element.addClass(images_active_class);
                        
                        // goto thumbnail
                        goto_thumbnail( new_index );
                        
                    },
                    onSlideAfter: function($slide_element, old_index, new_index){
                        
                        // added a delay here as the first slide triggers straight away, and 
                        // not onslideafter
                        
                        function slide_after_actions(){
                        
                            trigger_zoom();
                        
                        }
                        
                        if(new_index === 0){
                            
                            setTimeout(function(){
                                
                                slide_after_actions();
                                
                            }, parseInt(ink_pd_vars.options.slideSpeed));
                            
                        } else {
                            
                            slide_after_actions();
                            
                        }
                        
                    }
                };
        
    }
	
/*  =============================
    Found Variation 
    ============================= */
    
    function setup_variation_triggers(){
   	
        // Added this to trigger show_variation in the virtue theme,
        // but it may come in use for other themes too...
	   	
        $variations_form.find('input[name=variation_id]:first').on('change', function(){
            var $single_variation_input = $(this);
            
            if(!$single_variation_input.hasClass(loading_class) && $single_variation_input.val() !== ""){
                
                $single_variation_input.addClass(loading_class);
                
                $variations_form.trigger( 'show_variation');
                
                setTimeout(function(){
                    $single_variation_input.removeClass(loading_class);
                }, 500);
                
            }
            
        });
	   	
        // This triggers a show_variations event, if it hasn't happened already and a default var is set for the product
	
		$variations_form.on( 'show_variation', function( event, variation ) {
		
			if( !$img_container.hasClass(loading_class) && typeof variation !== "undefined" ){
				
				$img_container.addClass(loading_class);
				$img_container.removeClass('reset');
				
				// If swatches plugin is installed.
				if(swatches_active || typeof variation === undefined){
					$var_id = $('input[name=variation_id]').val();
				} else {
					$var_id = variation.variation_id;
				}
				
				args = {};			
				args.var_id = $var_id;
				args.variation = variation;
			
				load_new_images(args);
				
			}
		   
		});
	
	}

/**	=============================
    *
    * Trigger new images on single attribute selection
    *
    * WIP
    *
    ============================= */
	
	function setup_attribute_triggers(){
    	
    	$variations_form.on('change', 'select', function(){
        	
        	var $select = $(this),
        	    selected_attribute_value = $select.val(),
                selected_attribute_name = $select.attr('name');
            
            $.each(variations, function( index, variation_data ){
                
                $.each(variation_data.attributes, function(attribute_name, attribute_value){
                    
                    if( selected_attribute_name === attribute_name && ( selected_attribute_value === attribute_value || attribute_value === "" ) ) {
                        console.log('this one');
                    }
                    
                });
                
            });
        	
    	});
    	
	}
	
/* 	=============================
   	Reset Images 
   	============================= */
   	
   	function setup_image_resets(){
   	
	   	$variations_form.on('reset_image', function(){
			jck_wt_reset_imgs();
		});
		
		if(swatches_active){
			$variations_form.on('change', function(){
				$var_id = $('input[name=variation_id]').val();
				if($var_id === ""){
					jck_wt_reset_imgs();
				}
			});
		}
		
	}
	
	function jck_wt_reset_imgs() {
    	
		if(!$img_container.hasClass('reset') && !$img_container.hasClass(loading_class)){		
		
			$img_container.addClass(loading_class);	
			$img_container.addClass('reset');
				
			args = {};			
			args.var_id = "default";		
			
			load_new_images(args);
		}
		
	}
		
/*  =============================
    Functions 
    ============================= */
    
    function trigger_slider() {
        
        $slider.imagesLoaded( function() {
        
            slider_data = $slider.bxSlider( main_slider_args() );
        
        });
        
        if( $thumbnails.length > 0 && ink_pd_vars.options.navigationType === "sliding" ) {
            
            $thumbnails.imagesLoaded( function() {
            
                var mode = ( ink_pd_vars.options.thumbnailLayout === "above" || ink_pd_vars.options.thumbnailLayout === "below" ) ? "horizontal" : "vertical";
                
                thumbnails_data = $thumbnails.bxSlider({
                    mode: mode,
                    infiniteLoop: false,
                    speed: parseInt(ink_pd_vars.options.thumbnailSpeed),
                    minSlides: parseInt(ink_pd_vars.options.thumbnailCount),
                    maxSlides: parseInt(ink_pd_vars.options.thumbnailCount),
                    slideWidth: 800,
                    moveSlides: 1,
                    pager: false,
                    controls: false,
                    slideMargin: parseInt(ink_pd_vars.options.thumbnailSpacing),
                    onSliderLoad: function(){
                        
                        $thumbnails.css({ opacity:1, height: 'auto' });
                        $('.bx-clone').removeClass(thumbnails_active_class);
                    
                    }
                });
            
            });
            
        }
        
        $("body").on('click', ".jck-wt-thumbnails__slide", function(){
                
            if( !$img_container.hasClass(loading_class) ) {
            
                var new_index = parseInt($(this).attr('data-index'));
                  
                // add active class  
                $(".jck-wt-thumbnails__slide").removeClass(thumbnails_active_class);   
                $(this).addClass(thumbnails_active_class);
                
                // change main slide                
                slider_data.goToSlide(new_index);
            
            }
            
        });
        
    }
    
    function setup_slider_controls() {
        
        if( zoom_enabled ) {
        
            $(document).on('click', '.jck-wt-zoom-prev', function(){
                slider_data.goToPrevSlide();
            });
            
            $(document).on('click', '.jck-wt-zoom-next', function(){
                slider_data.goToNextSlide();
            });
        
        }
        
    }
    
    function get_thumbnail_index( index ){
        
        if( parseInt(ink_pd_vars.options.thumbnailCount) === 1 ) {
            return index;
        }
        
        var thumbnail_count = thumbnails_data.getSlideCount(),
            last_slide_index = thumbnail_count - ( ink_pd_vars.options.thumbnailCount - 1 ),
            new_thumbnail_index = ( index >= last_slide_index ) ? parseInt(last_slide_index) : parseInt(index);
            
            if( new_thumbnail_index !== 0 ) {
                    
                return new_thumbnail_index - 1;
            
            }
            
            return index;
        
    }
    
    function goto_thumbnail( index ) {
        
        // goto slide
        if(thumbnails_data) {
            var thumbnail_index = get_thumbnail_index( index );
            thumbnails_data.goToSlide( thumbnail_index );
        }
        
        // add active class  
        $(".jck-wt-thumbnails__slide").removeClass(thumbnails_active_class);   
        $(".jck-wt-thumbnails__slide[data-index="+index+"]").addClass(thumbnails_active_class);
        
    }
   	
   	// ! Zoom Functions
   	
    function init_zoom( $image ){
        
        var large_image = $image.attr('data-large-image');
       	
        $image.addClass('currZoom').ImageZoom({
            type: ink_pd_vars.options.zoomType, 
            bigImageSrc: large_image, 
            zoomSize: [ink_pd_vars.options.zoomDimensions.width,ink_pd_vars.options.zoomDimensions.height],
            zoomViewerClass: ( ink_pd_vars.options.zoomType === "follow" ) ? 'shape'+ink_pd_vars.options.innerShape : "shapesquare",
            position: ink_pd_vars.options.zoomPosition,
            showDescription: false,
            onShow: function(){
                if( $('.zm-viewer .jck-wt-zoom-controls').length <= 0 && ink_pd_vars.options.zoomType === "inner" ) {
                    
                    $('.zm-viewer').append('<div class="jck-wt-zoom-controls"></div>');
                    
                    if( jckTrueFalse(ink_pd_vars.options.enableLightbox) ) {
                        $('.jck-wt-zoom-controls').append(fullscreen_button);
                    }
                    
                    if( jckTrueFalse(ink_pd_vars.options.enableArrows) && slider_data.getSlideCount() > 1 ) {
                        $('.jck-wt-zoom-controls').append('<a class="jck-wt-zoom-prev" href="javascript: void(0);"><i class="jck-wt-icon-prev"></i></a><a class="jck-wt-zoom-next" href="javascript: void(0);"><i class="jck-wt-icon-next"></i></a>');
                    }
                    
                    if( ink_pd_vars.options.navigationType === "bullets" ) {
                        
                        var $bullets_clone = $('.jck-wt-all-images-wrap .bx-pager').clone();
                        
                        $bullets_clone.appendTo( ".jck-wt-zoom-controls" ).wrap( "<div class='jck-wt-zoom-bullets'></div>" );
                        
                        $('.jck-wt-zoom-bullets a').on('click', function(){
                            
                            var selected_index = parseInt($(this).attr('data-slide-index'));
                            
                            // change main slide                
                            slider_data.goToSlide( selected_index );
                            
                            return false;
                            
                        });
                    }
                    
                }
            },
            onHide: function(){
                $('.bx-controls--hidden').removeClass('bx-controls--hidden').show();
            }
        });

    }
   	
    function destroy_zoom(){
        
        var $zoom = $('.currZoom').data('imagezoom');
        
        if( $zoom && typeof $zoom !== "undefined" ){
            
            $('.currZoom').removeClass('currZoom');
            $zoom.destroy();
            
        }
        
        $('.zm-viewer').remove();
        $('.zm-handler').remove();
        
    }
   	
    function trigger_zoom(){
        
        if( zoom_enabled ) {
    
            var $current_slide = $('.'+images_active_class),
                $slide_image = $current_slide.find('img');
                
            destroy_zoom();
            init_zoom( $slide_image );
        
        }
    
    }
   	
    // ! Fullscreen Functions
    
    function setup_fullscreen(){
        
        var fullscreen_trigger = ( ink_pd_vars.options.clickAnywhere === "1" ) ? ".jck-wt-images__image, .jck-wt-fullscreen, .zm-viewer img, .zm-handler" : ".jck-wt-fullscreen";
        
        $('body').on('click', fullscreen_trigger, function(){
            
            var $active_image = $("."+images_active_class),
                large_image_src = $active_image.find('img').attr('data-large-image'),
                $slides = $slider.children(),
                items = [],
                index = $active_image.index();
            
            if( $slides.length > 0 ) {
                $slides.each(function( i, el ){
                    
                    var large_image_src = $(el).find('img').attr('data-large-image'),
                        item = {
                            src: large_image_src
                        };
                        
                    items.push( item );
                    
                });
            }
            
            $.magnificPopup.open({
                items: items,
                type: 'image',
                image: {
                    verticalFit: false
                },
                gallery: {
                    enabled: true
                },
            }, index);
            
        });
    
    }
    


/**	=============================
    *
    * Get Variation Data
    *
    * Grabs the current selected variation data from the form's
    * data attribute
    *
    * @param int/str var_id
    * @return mixed bool or variation data, if found
    *
    ============================= */
   	
    function get_variation_data( args ) {
        
        if( typeof args.variation !== "undefined" ) {
            return args.variation;
        }
        
        var variation_data = false;
    
        $.each( variations, function( index, variation ){
            
            if( parseInt(variation.variation_id) === parseInt(args.var_id) ) {
                
                variation_data = variation;
            
            }
                
        } );
        
        return variation_data;
    
    }

/**	=============================
    *
    * Image Loader
    *
    * Loads the correct images for selected variation
    *
    * @param mixed args usually contains the var_id
    *
    ============================= */
    
    function load_new_images(args, callback){
        
        $('body').imagesLoaded( function() {

            var currShowing = $img_container.attr('data-showing');
    
            if( currShowing !== args.var_id ){
                
                var variation_data = ( args.var_id !== "default" ) ? get_variation_data(args) : false,
                    images = ( variation_data ) ? variation_data.additional_images : $.parseJSON( $img_container.attr('data-default') ),
                    slide_template = "<div class='jck-wt-images__slide'><img class='jck-wt-images__image' src='{{image_src}}' data-large-image='{{large_image_src}}' data-large-image-width='{{large_image_width}}' width='{{image_width}}' height='{{image_height}}' title='{{title}}' alt='{{alt}}'></div>",
                    thumbnail_template = "<div class='jck-wt-thumbnails__slide' data-index='{{index}}'><img class='jck-wt-thumbnails__image' src='{{image_src}}' title='{{title}}' alt='{{alt}}' width='{{image_width}}' height='{{image_height}}'></div>";
                
                if( images ) {
                    
                    $('body').append('<div class="jck-wt-temp"><div class="jck-wt-temp__images"/><div class="jck-wt-temp__thumbnails"/></div>');
                    
                    var image_count = images.length,
                        $temp_container = $('.jck-wt-temp'),
                        $temp_images = $('.jck-wt-temp__images'),
                        $temp_thumbnails = $('.jck-wt-temp__thumbnails');
                    
                    // add additional images
                    
                    $.each( images, function( index, image_data ){
                        
                        var slide_html     = slide_template
                                             .replace( "{{image_src}}", image_data.single[0] )
                                             .replace( "{{large_image_src}}", image_data.large[0] )
                                             .replace( "{{large_image_width}}", image_data.large[1] )
                                             .replace( "{{image_width}}", image_data.single[1] )
                                             .replace( "{{image_height}}", image_data.single[2] )
                                             .replace( "{{alt}}", image_data.alt )
                                             .replace( "{{title}}", image_data.title ),
                            thumbnail_html = ( image_count > 1 ) ? thumbnail_template
                                                                   .replace( "{{image_src}}", image_data.thumb[0] )
                                                                   .replace( "{{index}}", index )
                                                                   .replace( "{{image_width}}", image_data.thumb[1] )
                                                                   .replace( "{{image_height}}", image_data.thumb[2] )
                                                                   .replace( "{{alt}}", image_data.alt )
                                                                   .replace( "{{title}}", image_data.title )
                                                                 : "";
                        
                        $temp_images.append( slide_html );
                        $temp_thumbnails.append( thumbnail_html );
                    
                    } );
                    
                    // pad out the thumbnails if there is less than the 
                    // amount that are meant to be displayed.
                    
                    if( image_count < ink_pd_vars.options.thumbnailCount ) {
                        
                        var empty_count = ink_pd_vars.options.thumbnailCount - image_count;
                        
                        i = 0; while( i < empty_count ) {
                            
                            $temp_thumbnails.append( '<div/>' );
                            
                            i++;
                            
                        }
                        
                    }
                    
                    // add active classes to new images
                    
                    $temp_images.find('div:first').addClass( images_active_class );
                    $temp_thumbnails.find('div:first').addClass( thumbnails_active_class );
                    //$slider.find('div:first').addClass( images_active_class );
                    //$thumbnails.find('div:first').addClass( thumbnails_active_class );
                    
                    $temp_container.imagesLoaded( function() {
                        
                        // add new slides
                        $slider.html( $temp_images.html() );
                        $thumbnails.html( $temp_thumbnails.html() );
                        
                        // remove temp images                    
                        $temp_container.remove();
                        
                        // reload the sliders           
                        slider_data.reloadSlider( main_slider_args() );
                        if(thumbnails_data) {
                            thumbnails_data.reloadSlider();
                        }
                        
                        // hide controls if only 1 image                
                        if( image_count <= 1 ) {
                            $('.jck-wt-images-wrap .bx-controls').hide();
                        }
                        
                        $img_container.removeClass(loading_class);
                        
                        // Run a callback, if required
            		
                		if(callback !== undefined) {
                			callback(data);
                		}
                        
                    });
    			
    			} else {
                
                    // Run a callback, if required
            		
            		if(callback !== undefined) {
            			callback(data);
            		}
    			
    			}
    			
    			// Change the "showing" var ID
        			
    			$img_container.attr('data-showing', args.var_id);
    		
    		} else {
        		
    			$img_container.removeClass(loading_class);
    			
    		}
		
		});
		
    }
   	
/*  =============================
    On Doc Ready 
    ============================= */

	if($img_container.length > 0) {
    	
		trigger_slider();
		setup_variation_triggers();
		setup_image_resets();
		setup_fullscreen();
	
	}

});