
	/* This extension is intended for CONTENTdm 6.1	addon_cooliris.js */
		
	var queryval = $("#cdm_resultquery").val(); //gets query that describes results for call to API
	if (undefined !== queryval)
	{
		
		var host = $("#cdm_host").val(); 
		var $api = queryval.replace('<!--', '');
		var $apistring = $api.replace('-->', '');		
		var parts = $apistring.split("/"); 		
		
		var num_results = 20; //<-- Maximum number of items that will be parsed for display in image wall (up to 1024)
		var windowheight = 616; //<-- Height of Cool Iris window
		var windowwidth = 1216; //<-- Width of Cool Iris window
		
		parts[5] = num_results;
		//parts[6] = 1; //Uncomment if you want results display beginning with first page of search results
		var newstring = parts.join("/");
		var $urlval = newstring.replace(/\s/g, ""); //Removes spaces

		//Path to custom pages, this is where your files will be when you upload them under "Custom Pages" on the website config tool. 
		var coolirispath = '/ui/custom/default/collection/default/resources/custompages/cooliris/';
		var feedurl = coolirispath + "addon_coolirisrss_suppress.php?urlval=" + $urlval; 
		var coolirishtml = coolirispath + "addon_cooliriswindow.html";
		
		function openIris(){
			window.open('http://' + host + coolirishtml + '?urlval=' + feedurl, '_blank', 'width=' + windowwidth +',height=' + windowheight);
		}
		//Enter path to icon next to link below
		var iconpath = coolirispath + 'wall_icon.gif';
		var linktext = 'View image wall'; //<--Change what the link to CoolIris window says
		var color =	$(".action_link_10").css("color");
		var linkstyle = 'color:' + color;
		
		//Button		
		var cooliris = '<img class="link_bar_sep" src="/ui/cdm/default/collection/default/images/univ_pg_control_sep.png" alt=""><div id="my_map" class="link_bar_link" style="padding: 4px 12px 5px 10px;"><a href="#" class="action_link_10" onclick="openIris()" style="' + linkstyle + '"><img src="' + iconpath + '" border=0 width=11 height=10 style="padding-right:2px">' + linktext + '</a>';

		$("#display_options_link").after(cooliris);
	}

		
				
		
	
		
		
		
		
		