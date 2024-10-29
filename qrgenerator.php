<?php
/*
The main function for the Android Market QR Codes WP Plugin
http://www.techcredo.com/random-tech/android-market-qr-codes-wordpress-plugin
*/
class qrgenerator {
	
// check if the page is viewed using an Android device,
// since we don't want to link to the Web Market on Androids
const ANDROID_USER_AGENT = 'android';
private function is_android() { 
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	return preg_match("/".self::ANDROID_USER_AGENT."/i", $user_agent);
}

// the function that generates the actual QR code
public function create_qr_code($url) {
	
	// retrieve the options from the database
	$qrcalign = get_option( "qrcalign");
	$qrcsize = get_option( "qrcsize");
	$qrcborderc = get_option( "qrcborderc");
	$qrctitle = get_option( "qrctitle");
	$qrcandroidimg = get_option( "qrcandroidimg");
	$qrcshowmarketinfo = get_option( "qrcshowmarketinfo");
	$qrcappboxcss = get_option( "qrcappboxcss");
	$qrcappratingcss = get_option( "qrcappratingcss");
	
	// check what type of link we have
	//// it's a Web Market link...
	if (strpos($url, "market.android.com") !== false) { 

		// what to find in the string, in order to extract what we want
		$findthis = "?id="; 
		// check where in the link "?id=" appears. we need the name of the app package.
		// to find the position of the start of the last occurence of a string, the idea is to reverse both $needle and $haystack, use strpos to 
		// find the first occurence of $needle in $haystack, then count backwards by the length of $needle. finally, subtract $pos from length of $haystack.
		$packagepos = strlen($url) - (strpos(strrev($url), strrev($findthis)) + strlen($findthis)); 
		$packagename = substr($url,$packagepos+4); // extract the part of the link after "?id=" to the end of the string, that's the package name
		// check where/if the ampersand is that the web Market sometimes adds, so we can clean up the URL
		$ampersandpos = strpos($packagename, "&");
		// if the ampersand's position isn't bigger than 0, it doesn't exist, so ignore it
		if ($ampersandpos >0) { 
			// extract the package name
			$packagename = substr($packagename, 0, $ampersandpos); 
			// create a new Web Market URL, without the redundant info after the ampersand
			$url = substr($url, 0, strpos($url, "&"));
		}
		// create the Android Market search string for the QR code
		$qrURL = "market://search?q=pname:".$packagename; 
		
	// it's an AppBrain link, obviously
	} else if (strpos($url, "www.appbrain.com") !== false) { 

		// what to find: a slash
		$findthis = "/"; 
		$packagepos = strlen($url) - (strpos(strrev($url), strrev($findthis)) + strlen($findthis)); 
		$packagename = substr($url,$packagepos+1);
		$qrURL = "market://search?q=pname:".$packagename; 
		// save this info for later
		$isappbrain = true;
	
	// it's just a name
	} else { 
				
		// add hyphens to the app title for the Web Market search
		// code for replacing hyphens with spaces
	    $apptitle = $url; // we need the title for later, in case the "show title" option is activated
		$appsearchstring = strtolower($url);
	    $appsearchstring = trim(preg_replace("/[\s-]+/", " ", $appsearchstring));
	    $appsearchstring = trim(substr($appsearchstring, 0, 45));
	    $appsearchstring = preg_replace("/\s/", "-", $appsearchstring);
		// create a search string
		$qrURL = "market://search?q=".$appsearchstring;
		$url = "https://market.android.com/search?q=".$appsearchstring."&c=apps";
		// save this info for later
		$istitleonly = true;
	}	
	
	// pull the app info from the Web Market - but only if that options is turned on, 
	// we've actually found an icon, the link is not an appbrain link, and that not just a title has been supplied
	if ($qrcshowmarketinfo == "yes" and $isappbrain == false and $istitleonly == false){
	
		// try to extract the name of the app by grabbing the <title> tag from the Web Market or AppBrain page
		$pagecode = file_get_contents($url); // grab the code for the entire page
		// extract the full title of the page, i.e. the name of the app
		if (preg_match('/<title>(.*?)<\/title>/is',$pagecode,$foundit)) {
			$apptitle = $foundit[1];
			// check where in the page title the hyphen is
			$hyphenpos = strpos($apptitle, "-");
			// extract the part before the hyphen (i.e. the app name)
			$apptitle = substr($apptitle,0, $hyphenpos);
			// cut down the title a bit if it's too long
			if (strlen ($apptitle) > 29) {$apptitle = substr ($apptitle, 0,25); $apptitle = $apptitle.'...';}
		} else {
			$apptitle = "";
		}

		// extract the freakin' image: 
		// <div class="doc-banner-icon"><img src="https://ssl.gstatic.com/android/market/com.OrangeAgenda.StellarEscape/hi-256-1-db9a59190f4ef8c7e7f46df157c557fc320ef3b6" /></div>
		if (preg_match('/<div class="doc-banner-icon">(.*?)<\/div>/is',$pagecode,$foundit2)) {
			$appiconurl = $foundit2[1];
			$appiconurl = substr($appiconurl, 0,strlen($appiconurl) -2); // grab that image, except the closing "/>"
		// no icon to be found
		} else {
			$appiconurl = false;
		}

		// extract the rating: 
		// <span class="doc-banner-ratings-price"><span class="ratings" title="Rating: 4,4 stars (Above avarage)">
		if (preg_match('/<span class="doc-banner-ratings-price"><span class="ratings" title="(.*?)">/is',$pagecode,$foundit3)) {
			$apprating = $foundit3[1];
			// remove the "Rating: " part
			$apprating = substr($apprating, strpos($apprating, ":") +1);
			// remove the parenthesis as well, we don't want it
			$parpos = strpos($apprating, "(");
			$apprating = substr($apprating, 0, $parpos);

		// we couldn't find a rating
		} else {
			$apprating = false;
		}

		// finally, extract the size of the QR codes, and add some pixels for the box
		$xpos = strpos($qrcsize, "x");$pxlsize = substr($qrcsize,0, $xpos); $boxwidth = ($pxlsize + 10)*2;
		$boxheight = $pxlsize + 20;
	}
			
	// now that we have extracted and decided all the important stuff, let's print out the actual QR code...
	// if the page is viewed from computer, do this:
	if (!$this->is_android()) {

		// the clickable link should point to the web, since we're on a computer
		$thelinkurl = $url;

	// the page is viewed with an android device, and we want to link 
	// directly to the mobile Market instead
	} else {

		// point the link to the phone Market instead
		$thelinkurl = $qrURL;
		$qronandroid = true;
	}

	// finally, if there is no app icon URL, it means that either the icon couldn't be extracted
	// or that the setting is turned off - but if it's true, let's rock.
	if	($appiconurl == !false){

		// build the seemingly gigantic return string
		$returnstring = '<div style="height:'.$boxheight.'px;width:'.$boxwidth.'px;float:'.$qrcalign.';'.$qrcappboxcss.'"><span style="'.$qrcappratingcss.'">'.$apprating.'</span><a href="'.$thelinkurl.'" target="_blank">'.$apptitle.'<br /><img src="http://chart.apis.google.com/chart?cht=qr&chs='.$qrcsize.'&chld=L|1&chl='.$qrURL.'" style="float:left;" border:'.$qrcborderc.'" title="'.$qrctitle.'" />'.$appiconurl.' width="'.$pxlsize.'px" height="'.$pxlsize.'px" style="float:right;" title="'.$qrctitle.'" /></a></div>';

	// skip the app info from the Market and just show the QR code
	} else {

		$returnstring = '<a href="'.$thelinkurl.'" target="_blank"><img src="http://chart.apis.google.com/chart?cht=qr&chs='.$qrcsize.'&chld=L|1&chl='.$qrURL.'" style="float:'.$qrcalign.'; border:'.$qrcborderc.'" title="'.$qrctitle.'" /></a>';
	}
	
	// final thing, a little workaround: if we're on android, and the custom icon option is on, we need to change the return string
	if ($qronandroid and (strlen($qrcandroidimg))){$returnstring = '<a href="'.$thelinkurl.'" target="_blank"><img src="'.$qrcandroidimg.'" style="float:'.$qrcalign.'; title="'.$qrctitle.'"></a>';}
	
	// send away...
	return $returnstring;
		
	}
}
?>