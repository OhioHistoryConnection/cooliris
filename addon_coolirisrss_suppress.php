<?php
	/* This code is built for CONTENTdm 6.1 
	** If you are using a future version of CONTENTdm the links[] array may not point to the right location 
	** Comments below point to the areas you may wish to customize features for your site.
	** You MUST enter your server and port next to the $server variable
	*/
	
	header("Content-type: text/xml");
	
	$turl = preg_replace("@<script[^>]*>.+</script[^>]*>@i", "", $_GET['urlval']);
	$host = "cdm16007.contentdm.oclc.org";
	$url = str_replace(" ", "+", $turl);
	
	/* Make sure to change the following line! */
	$server = 'server16007.contentdm.oclc.org'; //<--Your server and port go here
	$url = "https://".$server."/dmwebservices/index.php?q=" . $url;

	// Max lengh of descriptions overlaying images	
	$max_length = 95; 
	// Scale at which images are used for display in CDM CoolIris. 
	// Will probably be overridden to get a DMSCALE better suited for CDM default CoolIris window size.
	$scale = 50; 
	$optimal_width = 744;
	$optimal_height = 524;

	$rssfeed = "<rss version=\"2.0\"  xmlns:media=\"http://search.yahoo.com/mrss/\"  xmlns:atom=\"http://www.w3.org/2005/Atom\"><channel>";
	$xmldata = doCurl($url);

	$sXML = new SimpleXMLElement($xmldata);
	$records = $sXML->records;
	
	$collections = array();
	$titles = array();
	$ids = array();
	$descriptions = array();
	$widths = array();	
	$heights = array();
	$links = array(); //Array of links to each individual item, might need altering in later versions
	$monids = array();
	$montitles = array();
	$imagelocs = array();
	$i=0;
	$c=1; //record number	
	
	/* Parses through each record found in search results
	** Selects only jp2, tiff, jpg, and png files
	** Goes through each compound object and selects jp2 images within cpd */
	foreach ($records->record as $record)
	{
		$filetype = $record->filetype;
		if ($filetype == 'jp2' || $filetype == 'tiff' || $filetype == 'jpg' || $filetype == 'png' || $filetype == 'cpd') {
			$collections[$i] = $record->collection;
			$ids[$i] = $record->pointer;
			$titles[$i] = $record->title;
			$descriptions[$i] = $record->descri;
			$thumbloc = '"http://'.$host.'/utils/getthumbnail/collection'.$collections[$i].'/id/'.$ids[$i].'"';
			//Cuts the length of the description for an item ~max_length characters
			if(strlen($descriptions[$i]) > $max_length)
			{
				$bool = true;
				$n = $max_length-5;
				while($bool == true && $n < strlen($descriptions[$i]))
				{
					if(substr($descriptions[$i],$n, 1) == "." || substr($descriptions[$i],$n,1) == " " || substr($descriptions[$i],$n,1) == "!" || substr($descriptions[$i],$n,1) == "," || substr($descriptions[$i],$n,1) == ":" || substr($descriptions[$i],$n,1) == "?" || substr($descriptions[$i],$n,1) == ";")
					{
						$descriptions[$i] = substr($descriptions[$i], 0, $n) . "...";
						$bool = false;
					}
					$n++;
				}
			}
			
			// If compound, need the first page pointer and image info
			if ($filetype == 'cpd') {
				$url = "https://".$server.'/dmwebservices/index.php?q=dmGetCompoundObjectInfo'.$collections[$i].'/'.$ids[$i].'/xml';
				$cpddata = doCurl($url);
				$simpleXML = new SimpleXMLElement($cpddata);				
				if ($simpleXML->type == 'Document') {
					$pagefile = $simpleXML->page[0]->pagefile;
					$temptype = explode(".", $pagefile);
					if($temptype[1] == 'jp2'  || $temptype[1] == 'tiff' || $temptype[1] == 'jpg' || $temptype[1] == 'png' ) {
						$page_ptr = $simpleXML->page[0]->pageptr;
						$links[$i] = "http://".$host."/cdm/compoundobject/collection".$collections[$i]."/id/".$ids[$i]."/show/".$page_ptr."/rec/".$c;				
						$url = "https://".$server.'/dmwebservices/index.php?q=dmGetImageInfo'.$collections[$i].'/'.$page_ptr.'/xml';
						$imageinfo = doCurl($url);
						$xml = new SimpleXMLElement($imageinfo);
						$widths[$i] = (integer)$xml->width;
						$heights[$i] = (integer)$xml->height;
						if (!isset($widths[$i])) {
							$widths[$i] = 0;
							$heights[$i] = 0;
						} else {
							// try to get a DMSCALE value closer to the default CDM CoolIris window size parameters
							$scale = doScale($widths[$i], $heights[$i]);
						}
						$imagelocs[$i]='"http://'.$host.'/utils/ajaxhelper/?CISOROOT='.substr($collections[$i], 1).'&CISOPTR='.$page_ptr.'&action=2&DMSCALE='.$scale.'&DMWIDTH='.$widths[$i].'&DMHEIGHT='.$heights[$i].'&DMX=0&DMY=0&DMTEXT=&DMROTATE=0"';	
					}
				}
			} else {
				$links[$i] = "http://".$host."/cdm/singleitem/collection".$collections[$i]."/id/".$ids[$i]."/rec/".$c;				
				$url = "https://".$server.'/dmwebservices/index.php?q=dmGetImageInfo'.$collections[$i].'/'.$ids[$i].'/xml';
				$imageinfo = doCurl($url);
				$xml = new SimpleXMLElement($imageinfo);
				$widths[$i] = (integer)$xml->width;
				$heights[$i] = (integer)$xml->height;				
				if (!isset($widths[$i])) {
					$widths[$i] = 0;
					$heights[$i] = 0;
				} else {
					// try to get a DMSCALE value closer to the default CDM CoolIris window size parameters
					$scale = doScale($widths[$i], $heights[$i]);
				}
				$imagelocs[$i]='"http://'.$host.'/utils/ajaxhelper/?CISOROOT='.substr($collections[$i], 1).'&CISOPTR='.$ids[$i].'&action=2&DMSCALE='.$scale.'&DMWIDTH='.$widths[$i].'&DMHEIGHT='.$heights[$i].'&DMX=0&DMY=0&DMTEXT=&DMROTATE=0"';
			}
			
			// Create CoolIris feed items
			$rssfeed .=	'
			<item>
				<title><![CDATA['.html_entity_decode($titles[$i]).']]></title>	
				<media:description><![CDATA['.html_entity_decode($descriptions[$i]).']]></media:description>
				<link>'.$links[$i].'</link>
				<media:thumbnail url='.$thumbloc.' />
				<media:content url='.$imagelocs[$i].' />
			</item>';
			
			$i++; 
			$c++; 
		}
		else {
			$c++;
		}
	}
	
	$rssfeed .= '
		</channel>
	</rss>';

	echo $rssfeed;
	
	function doCurl($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$xmldata = curl_exec($ch);
		curl_close($ch);
		$encoded = str_replace("&", "&amp;", $xmldata); //& showing up in XML and breaking it?
		return $encoded;
	}
	
	function doScale($imgwidth, $imgheight) {
		if ($imgwidth > $imgheight) {
			// only need approx. 742-750 to render well vertically in CDM default CoolIris window size
			// Remember, however, user can switch to full screen!
      		$init_scale = round(($optimal_width/$imgwidth), 2); 
    	}
	    if ($imgheight > $imgwidth) {
	    	// only need approx. 522-530 to render well horizontally in CDM default CoolIris window size
	    	// Remember, however, user can switch to full screen!
	      	$init_scale = round(($optimal_height/$imgheight), 2); 
	    }
    	$formatted_scale = sprintf("%01.2f", $init_scale);
    	$trimmed_scale = substr($formatted_scale, 2);
    	// if full res image happens to be smaller than window dimensions, set to full scale 
	    if ($init_scale > 1) {
	      $trimmed_scale = "100";
	    }
    	return $trimmed_scale;
	}


?>



