function getParameterByName(name) {

    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");

    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),

        results = regex.exec(location.search);



    return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));

}

function updateQueryStringParameter(uri, key, value) {

  var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");

  var separator = uri.indexOf('?') !== -1 ? "&" : "?";

  if (uri.match(re)) {

    return uri.replace(re, '$1' + key + "=" + value + '$2');

  }

  else {

    return uri + separator + key + "=" + value;

  }

}
jQuery(document).ready(function(){
			var ul='';
			var currentUrl = document.URL;
			currentUrl = currentUrl.split("product-designer");
			ul = currentUrl[0];
			
		try {
			var pid = getParameterByName('id'); //for pid

			var pvid = getParameterByName('simplePdctId'); //for pvid

			var sid = getParameterByName('ref_id'); //ref_id for sid

			var ptypes = getParameterByName('ptypes'); // for ptypes

			var revision_no = getParameterByName('rvn'); // for ptypes



			var sid0 = getParameterByName('sid');

			if(sid0!="")

			{

				sid = sid0;

					

				var pid0=getParameterByName('pid');

				if(pid0!="")

				 pid=pid0;

				var pvid0=getParameterByName('pvid');

				if(pvid0!="")

				 pvid=pvid0;

			}



			var ptypes0=getParameterByName('pt');

			  if(ptypes0!="")

				ptypes=ptypes0;





			var customer = getParameterByName('customer');

			//var quoteId = getParameterByName('quoteId');

			var tid=getParameterByName('tid');

                    var sesid=0;

                    if(document.getElementById('my-designer-ses-id')!=undefined)

                    {

                            sesid=document.getElementById('my-designer-ses-id').value;

                    }

					

                    var  url= ul+'xetool/index.html?customer='+customer;
					//console.log("url:"+url);

                    if(sid!="")

                    {

                     	url=url+'&sid='+sid;

                    }

                    else if(tid=="")

                    {

                     	 /*if(pid!="")

                     	  url=url+'&pid='+pid;

                     	 if(pvid!="")

                     	  url=url+'&pvid='+pvid;*/

                        

                      if(pvid!="")

                        url=url+'&pid='+pvid;

                      else if(pid!="")

                        url=url+'&pid='+pid;



                    }

                    if(tid!="")

                    {

                           url=url+'&tid='+tid;     

                    }



                    if(ptypes!="")

                        url=url+'&pt='+ptypes;

                    if(revision_no!="")

                      url=url+'&rvn='+revision_no;  
					//console.log( "JSON Data: " + document.URL );

/*                     if(quoteId!="")

                        url=url+'&quoteId='+quoteId;

                    url=url+'&sesid='+sesid;  */ 
					//console.log("url:"+url);

                   /*  if(pid!="" || pvid!="" || sid!="" || quoteId!="" || customer!="")

                        document.getElementById('tshirtIFrame').src=url; */
						
						jQuery('#tshirtIFrame').attr('src',url); 
						jQuery('#main').css('width','1040px'); 



                }

                catch(e) {

               		console.log(e);

					alert("view.phtml Error:" + e.message);			

			}

    });            



      

           

         