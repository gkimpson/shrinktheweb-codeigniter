<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter ShrinkTheWeb
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Academic Free License version 3.0
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * http://opensource.org/licenses/AFL-3.0
 *
 * @package     CodeIgniter
 * @author      Gavin Kimpson
 * @copyright   Copyright (c) Gavin Kimpson (http://www.gavk.co.uk)
 * @license     http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
 * @version  	0.1
 */
// ------------------------------------------------------------------------

/**
 * ShrinkTheWeb library
 *
 * Unofficial library to be used in conjunction with a valid ShrinkTheWeb account.
 * Get your ShrinkTheWeb account here http://ShrinkTheWeb.com - based on the original code by Andreas Pachler & Brandon Elliott
 *
 * I DO NOT have any affiliation with ShrinkTheWeb.
 * Please DO NOT contact me seeking support. :)
 *
 * See: http://ShrinkTheWeb.com & http://support.shrinktheweb.com
 *
 */
class ShrinkTheWeb
{
	/**
	 * The URL for ShrinkTheWeb API
	 * @var string
	 */
	public $ShrinkTheWeb_url = 'http://images.shrinktheweb.com/xino.php';

	/**
	 * Access Key
	 * @var string
	 */
	public $access_key = 'e81e401032dac9d';

	/**
	 * Secret 'key'
	 * @var string
	 */
	public $secret_key = '209d7';

	public $thumbnail_uri = 'http://www.yourdomain.com/stw_thumbs/';
	public $thumbnail_dir = 'stw_thumbs/';
	public $inside_pages = false;
	public $custom_msg_url = '';
	public $cache_days = 3;
	public $ver = '2.0.5';
	public $quota_image = 'quota.jpg';
	public $bandwidth_image = 'bandwidth.jpg';
	public $no_response_image = 'no_response_image.jpg';
	public $maintenance = 'ShrinkTheWeb is temporarily offline for maintenance';


	// ------------------------------------------------------------------------

	/**
	 * Setup all vars
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct($config = array())
	{
		// Get CI Instance
		$this->CI = &get_instance();

		$this->set_config($config);
		log_message('debug', 'ShrinkTheWeb Class Initialized');
	}

	// ------------------------------------------------------------------------

	/**
	 * Manually Set Config
	 *
	 * Pass an array of config vars to override previous setup
	 *
	 * @param   array
	 * @return  void
	 */
	public function set_config($config = array())
	{
		if ( ! empty($config))
		{
			foreach ($config as $key => $value)
			{
				$this->{$key} = $value;
			}
		}
	}

    /**
     * Gets the thumbnail for the specified website, stores it in the cache, and then returns the HTML for loading the image.
     */
    function getThumbnailHTML($sUrl, $aOptions = array(), $sAttribAlt = false, $sAttribClass = false, $sAttribStyle = false, $isLink = false) {
  	    $sImageTag = false;
        $aOptions = $this->_generateOptions($aOptions);

        $sImageTag = $this->_getThumbnailAdvanced($sUrl, $aOptions, $sAttribAlt, $sAttribClass, $sAttribStyle, $isLink);

        return $sImageTag;
    }

    /**
     * Delete thumbnail
     */
    function deleteThumbnail($sUrl, $aOptions = array()) {
        $aOptions = $this->_generateOptions($aOptions);
        $aArgs = $this->_generateRequestArgs($aOptions);
        $aArgs['stwurl'] = $sUrl;

        $sFilename = $this->_generateHash($aArgs).'.jpg';
	    $sFile = $this->thumbnail_dir . $sFilename;

       	if (file_exists($sFile)) {
    		@unlink($sFile);
    	}
    }

    /**
     * refresh a thumbnail for a url with specified options
     * first delete it and then do a new request and return the HTML for image loading
     */
    function refreshThumbnail($sUrl, $aOptions = array(), $sAttribAlt = false, $sAttribClass = false, $sAttribStyle = false, $isLink = false) {
        $aOptions = $this->_generateOptions($aOptions);
        $aOptions['RefreshOnDemand'] = true;

        deleteThumbnail($sUrl, $aOptions);
        $sImageTag = getThumbnailHTML($sUrl, $aOptions, $sAttribAlt, $sAttribClass, $sAttribStyle, $isLink);

        return $sImageTag;
    }

    /**********************
    *  PRIVATE FUNCTIONS  *
    **********************/

    // getting the thumbnal with advanced api
    function _getThumbnailAdvanced($sUrl, $aOptions, $sAttribAlt, $sAttribClass, $sAttribStyle, $isLink) {
        $sImageUrl = $this->_getThumbnail($sUrl, $aOptions);

        // if WAY OVER the limits (i.e. request is ignored by STW), grab an "Account Problem" image and store it as NO_RESPONSE_IMAGE
        if ($sImageUrl == 'no_response') {
            $sImageUrl = $this->_getNoResponseImage($sUrl, $aOptions);
        }

        // add attributes if set
        $sTags = false;
        if ($sAttribStyle) {
            $sTags .= ' style="' . $sAttribStyle . '"';
        }
        if ($sAttribAlt) {
            $sTags .= ' alt="' . $sAttribAlt . '"';
        }
        if ($sAttribClass) {
            $sTags .= ' class="' . $sAttribClass . '"';
        }

        // add link?
        if ($isLink) {
            $sImageHTML = $sImageUrl ? '<a href="' . $sUrl . '" target="_blank"><img src="' . $sImageUrl . '"'.$sTags.'/></a>' : false;
        } else {
            $sImageHTML = $sImageUrl ? '<img src="' . $sImageUrl . '"'.$sTags.'/>' : false;
        }

        return $sImageHTML;
    }

    /**
     * Gets the thumbnail for the specified website, stores it in the cache, and then returns the
     * relative path to the cached image.
     */
    function _getThumbnail($sUrl, $aOptions) {
        // create cache directory if it doesn't exist
        $this->_createCacheDirectory();

        $aArgs = $this->_generateRequestArgs($aOptions);

        // Try to grab the thumbnail
        $iCacheDays = $this->cache_days + 0;
        if ($iCacheDays >= 0 && isset($aOptions['Embedded']) && $aOptions['Embedded'] != 1) {
            $aArgs['stwurl'] = $sUrl;
            $sImageUrl = $this->_getCachedThumbnail($aArgs);
        } else {
            // Get raw image data
            unset($aArgs['stwu']); // ONLY on "Advanced" method requests!! (not allowed on embedded)
            $aArgs['stwembed'] = 1;
            $aArgs['stwurl'] = $sUrl;
            $sImageUrl = urldecode("http://images.shrinktheweb.com/xino.php?".http_build_query($aArgs,'','&'));
        }

        return $sImageUrl;
    }

    /**
     * generate options
     */
    function _generateOptions($aOptions) {
        // check if there are options set, otherwise set it to default or false
        $aOptions['Size'] = isset($aOptions['Size']) ? $aOptions['Size'] : 'lg';
        $aOptions['SizeCustom'] = isset($aOptions['SizeCustom']) ? $aOptions['SizeCustom'] : false;
        $aOptions['FullSizeCapture'] = isset($aOptions['FullSizeCapture']) ? $aOptions['FullSizeCapture'] : false;
        $aOptions['MaxHeight'] = isset($aOptions['MaxHeight']) ? $aOptions['MaxHeight'] : false;
        $aOptions['NativeResolution'] = isset($aOptions['NativeResolution']) ? $aOptions['NativeResolution'] : false;
        $aOptions['WidescreenY'] = isset($aOptions['WidescreenY']) ? $aOptions['WidescreenY'] : false;
        $aOptions['RefreshOnDemand'] = isset($aOptions['RefreshOnDemand']) ? $aOptions['RefreshOnDemand'] : false;
        $aOptions['Delay'] = isset($aOptions['Delay']) ? $aOptions['Delay'] : false;
        $aOptions['Quality'] = isset($aOptions['Quality']) ? $aOptions['Quality'] : false;

        return $aOptions;
    }

    /**
     * generate the request arguments
     */
    function _generateRequestArgs($aOptions) {
        $aArgs['stwaccesskeyid'] = $this->access_key;
        $aArgs['stwu'] = $this->secret_key;
        $aArgs['stwver'] = $this->ver;

        // allowing internal links?
        if ($this->inside_pages) {
            $aArgs['stwinside'] = 1;
        }

        // If SizeCustom is specified and widescreen capturing is not activated,
        // then use that size rather than the size stored in the settings
        if (!$aOptions['FullSizeCapture'] && !$aOptions['WidescreenY']) {
            // Do we have a custom size?
            if ($aOptions['SizeCustom']) {
                $aArgs['stwxmax'] = $aOptions['SizeCustom'];
            } else {
                $aArgs['stwsize'] = $aOptions['Size'];
            }
        }

        // Use fullsize capturing?
        if ($aOptions['FullSizeCapture']) {
            $aArgs['stwfull'] = 1;
            if ($aOptions['SizeCustom']) {
                $aArgs['stwxmax'] = $aOptions['SizeCustom'];
            } else {
                $aArgs['stwxmax'] = 120;
            }
            if ($aOptions['MaxHeight']) {
                $aArgs['stwymax'] = $aOptions['MaxHeight'];
            }
        }

        // Change native resolution?
        if ($aOptions['NativeResolution']) {
            $aArgs['stwnrx'] = $aOptions['NativeResolution'];
            if ($aOptions['WidescreenY']) {
                $aArgs['stwnry'] = $aOptions['WidescreenY'];
                if ($aOptions['SizeCustom']) {
                    $aArgs['stwxmax'] = $aOptions['SizeCustom'];
                } else {
                    $aArgs['stwxmax'] = 120;
                }
            }
        }

        // Wait after page load in seconds
        if ($aOptions['Delay']) {
            $aArgs['stwdelay'] = intval($aOptions['Delay']) <= 45 ? intval($aOptions['Delay']) : 45;
        }

        // Use Refresh On-Demand?
        if ($aOptions['RefreshOnDemand']) {
            $aArgs['stwredo'] = 1;
        }

        // Use another image quality in percent
        if ($aOptions['Quality']) {
            $aArgs['stwq'] = intval($aOptions['Quality']);
        }

        // Use custom messages?
        if ($this->custom_msg_url) {
            $aArgs['stwrpath'] = $this->custom_msg_url;
        }

        return $aArgs;
    }
    
    /**
     * Get a thumbnail, caching it first if possible
     */
    function _getCachedThumbnail($aArgs = null) {
        $aArgs = is_array($aArgs) ? $aArgs : array();

        // Use arguments to work out the target filename
        $sFilename = $this->_generateHash($aArgs).'.jpg';
        $sFile = $this->thumbnail_dir . $sFilename;

        $sReturnName = false;
        // Work out if we need to update the cached thumbnail
        $iForceUpdate = $aArgs['stwredo'] ? true : false;
        if ($iForceUpdate || $this->_cacheFileExpired($sFile)) {
            // if bandwidth limit has reached return the BANDWIDTH_IMAGE
            if ($this->_checkLimitReached($this->thumbnail_dir . $this->bandwidth_image)) {
                $sFilename = $this->bandwidth_image;
            // if quota limit has reached return the QUOTA_IMAGE
            } else if ($this->_checkLimitReached($this->thumbnail_dir . $this->quota_image)) {
                $sFilename = $this->quota_image;
			// if WAY OVER the limits (i.e. request is ignored by STW) return the NO_RESPONSE_IMAGE
            } else if ($this->_checkLimitReached($this->thumbnail_dir . $this->no_response_image)) {
                $sFilename = $this->no_response_image;
            } else {
                // check if the thumbnail was captured
                $aImage = $this->_checkWebsiteThumbnailCaptured($aArgs);
                switch ($aImage['status']) {
                    case 'save': // download the image to local path
                        $this->_downloadRemoteImageToLocalPath($aImage['url'], $sFile);
                    break;

                    case 'nosave': // dont save the image but return the url
                        return $aImage['url'];
                    break;

                    case 'quota_exceed': // download the image to local path for locking requests
                        $sFilename = $this->quota_image;
                        $sFile = $this->thumbnail_dir . $sFilename;
                        $this->_downloadRemoteImageToLocalPath($aImage['url'], $sFile);
                    break;

                    case 'bandwidth_exceed': // download the image to local path for locking requests
                        $sFilename = $this->bandwidth_image;
                        $sFile = $this->thumbnail_dir . $sFilename;
                        $this->_downloadRemoteImageToLocalPath($aImage['url'], $sFile);
                    break;

                    default: // otherwise return the status
                        return $aImage['status'];
                }
            }
        }

        $sFile = $this->thumbnail_dir . $sFilename;
        // Check if file exists
        if (file_exists($sFile)) {
            $sReturnName = THUMBNAIL_URI . $sFilename;
        }

        return $sReturnName;
    }

    /**
     * Method that checks if the thumbnail for the specified website exists
     */
    function _checkWebsiteThumbnailCaptured($aArgs) {
        $sRequestUrl = 'http://images.shrinktheweb.com/xino.php';
        $sRemoteData = $this->_fileGetContent($sRequestUrl, $aArgs);

        // check if we get no response or the maintenance string
        if ($sRemoteData == '' || $sRemoteData == 'offline') {
            $aImage = array('status' => 'no_response');
            if ($sRemoteData != '') {
                 $aImage['message'] = $this->maintenance;
            }
        } else {
            $aResponse = $this->_getXMLResponse($sRemoteData);
            // thumbnail is existing, download it
            if ($aResponse['exists'] && $aResponse['thumbnail'] != '') {
                $aImage = array('status' => 'save', 'url' => $aResponse['thumbnail']);
            // bandwidth limit has reached, grab embedded image and store it as BANDWIDTH_IMAGE
            } else if ($aResponse['stw_bandwidth_remaining'] == 0 && !$aResponse['locked'] && !$aResponse['invalid'] && !$aResponse['exists'] && !$aResponse['problem']) {
                $aImage = array('status' => 'bandwidth_exceed', 'url' => $aResponse['thumbnail']);
            // quota limit has reached, grab embedded image and store it as QUOTA_IMAGE
            } else if ($aResponse['stw_quota_remaining'] == 0 && !$aResponse['locked'] && !$aResponse['invalid'] && !$aResponse['exists'] && !$aResponse['problem']) {
                $aImage = array('status' => 'quota_exceed', 'url' => $aResponse['thumbnail']);
            // an error has occured, return the url but dont save the image
            } else if (!$aResponse['exists'] && $aResponse['thumbnail'] != '') {
                $aImage = array('status' => 'nosave', 'url' => $aResponse['thumbnail']);
            // otherwise return error because we dont know the situation
            } else {
                $aImage = array('status' => 'error');
            }

            // add the request to the database if debug is enabled
            if (DEBUG && DATABASE_HOST != '' && DATABASE_NAME != '' && DATABASE_USER != '') {
                if ($this->_DBConnect()) {
                    $this->_addRequestToDB($aArgs, $aResponse, $this->_generateHash($aArgs));
                    $this->_DBDisconnect();
                }
            }
        }

        return $aImage;
    }

    /**
     * Method to get image at the specified remote Url and attempt to save it to the specifed local path
     */
    function _downloadRemoteImageToLocalPath($sRemoteUrl, $sFile) {
        $sRemoteData = $this->_fileGetContent($sRemoteUrl, array());

        // Only save data if we managed to get the file content
        if ($sRemoteData) {
            $rFile = fopen($sFile, "w+");
            fputs($rFile, $sRemoteData);
            fclose($rFile);
        } else {
            // Try to delete file if download failed
            if (file_exists($sFile)) {
                @unlink($sFile);
            }

            return false;
        }

        return true;
    }

    /**
     * Gets the account problem image and returns the relative path to the cached image
     */
    function _getNoResponseImage($sUrl, $aOptions) {
        // create cache directory if it doesn't exist
        $this->_createCacheDirectory();

        $aOptions = $this->_generateOptions($aOptions);
        
        $aArgs['stwaccesskeyid'] = 'accountproblem';

        if ($aOptions['SizeCustom']) {
            $aArgs['stwxmax'] = $aOptions['SizeCustom'];
        } else {
            $aArgs['stwsize'] = $aOptions['Size'];
        }

        $sRequestUrl = 'http://images.shrinktheweb.com/xino.php';
        $sRemoteData = $this->_fileGetContent($sRequestUrl, $aArgs);

        if ($sRemoteData != '') {
            $aResponse = $this->_getXMLResponse($sRemoteData);

            if (!$aResponse['exists'] && $aResponse['thumbnail'] != '') {
                $sImageUrl = $aResponse['thumbnail'];

                $sFilename = $this->no_response_image;
                $sFile = $this->thumbnail_dir . $sFilename;
                $isDownloaded = $this->_downloadRemoteImageToLocalPath($sImageUrl, $sFile);

                if ($isDownloaded == true) {
                    return THUMBNAIL_URI . $sFilename;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if the limit reached image is existing, if so return true
     * return false if there is no image existing or the limit reached file is
     * older then 6 hours
     */
    function _checkLimitReached($sFile) {
        // file is not existing
        if (!file_exists($sFile)) {
            return false;
        }

        // is file older then 6 hours?
        $iCutoff = time() - (3600 * 6);
        if (filemtime($sFile) <= $iCutoff) {
            @unlink($sFile);
            return false;
        }

        // file is existing and not expired!
        return true;
    }

    /**
     * Create cache directory if it doesnt exist
     */
    function _createCacheDirectory() {
        // Create cache directory if it doesnt exist
        if (!file_exists($this->thumbnail_dir)) {
            @mkdir($this->thumbnail_dir, 0777, true);
        } else {
            // Try to make the directory writable
            @chmod($this->thumbnail_dir, 0777);
        }
    }

    /**
     * Generate the hash for the thumbnail, this is used as filename also
     */
    function _generateHash($aArgs) {
        $sPrehash = $aArgs['stwfull'] ? 'a' : 'c';
        $sPrehash .= $aArgs['stwxmax'].'x'.$aArgs['stwymax'];
        if ($aArgs['stwnrx']) {
            $sPrehash .= 'b'.$aArgs['stwnrx'].'x'.$aArgs['stwnry'];
        }
        $sPrehash .= $aArgs['stwinside'];

        $aReplace = array('http', 'https', 'ftp', '://');
        $sUrl = str_replace($aReplace, '', $aArgs['stwurl']);

        return md5($sPrehash.$aArgs['stwsize'].$aArgs['stwq'].$sUrl);
    }

    /**
     * store the XML response in an array and generate status bits
     */
    function _getXMLResponse($sResponse) {
        if (extension_loaded('simplexml')) { // If simplexml is available, we can do more stuff!
            $oDOM = new DOMDocument;
            $oDOM->loadXML($sResponse);
            $sXML = simplexml_import_dom($oDOM);
            $sXMLLayout = 'http://www.shrinktheweb.com/doc/stwresponse.xsd';

            // Pull response codes from XML feed
            $aThumbnail = (array)$sXML->children($sXMLLayout)->Response->ThumbnailResult->Thumbnail;
            $aResponse['thumbnail'] = $aThumbnail[0];
            $aResponse['stw_action'] = $aThumbnail[1];
            $aResponse['stw_response_status'] = $sXML->children($sXMLLayout)->Response->ResponseStatus->StatusCode; // HTTP Response Code
            $aResponse['stw_response_code'] = $sXML->children($sXMLLayout)->Response->ResponseCode->StatusCode; // STW Error Response
            $aResponse['stw_last_captured'] = $sXML->children($sXMLLayout)->Response->ResponseTimestamp->StatusCode; // Last Captured
            $aResponse['stw_quota_remaining'] = $sXML->children($sXMLLayout)->Response->Quota_Remaining->StatusCode; // New Reqs left for today
            $aResponse['stw_bandwidth_remaining'] = $sXML->children($sXMLLayout)->Response->Bandwidth_Remaining->StatusCode; // New Reqs left for today
            $aResponse['stw_category_code'] = $sXML->children($sXMLLayout)->Response->CategoryCode->StatusCode; // Not yet implemented
        } else {
	        // LEGACY SUPPPORT
            $aResponse['stw_response_status'] = _getLegacyResponse('ResponseStatus', $sRemoteData);
            $aResponse['stw_response_code'] = _getLegacyResponse('ResponseCode', $sRemoteData);

            // check remaining quota
            $aResponse['stw_quota_remaining'] = _getLegacyResponse('Quota_Remaining', $sRemoteData);
            // check remaining bandwidth
            $aResponse['stw_bandwidth_remaining'] = _getLegacyResponse('Bandwidth_Remaining', $sRemoteData);

            // get thumbnail and status
            $aThumbnail = $this->_getThumbnailStatus($sRemoteData);
            $aResponse = array_merge($aResponse, $aThumbnail);
        }
        
        if ($aResponse['stw_action'] == 'delivered') {
            $aResponse['exists'] = true;
        } else {
            $aResponse['exists'] = false;
        }

        if ($aResponse['stw_action'] == 'fix_and_retry') {
            $aResponse['problem'] = true;
        } else {
            $aResponse['problem'] = false;
        }

        if ($aResponse['stw_action'] == 'noretry') {
            $aResponse['error'] = true;
        } else {
            $aResponse['error'] = false;
        }

        // if we use the advanced api for free account we get an invalid request
        if ($aResponse['stw_response_code'] == 'INVALID_REQUEST') {
            $aResponse['invalid'] = true;
        } else {
            $aResponse['invalid'] = false;
        }
		
		// if our domain or IP is not listed in the account's "Allowed Referrers" AND "Lock to Account" is enabled, then we get this error
        if ($aResponse['stw_response_code'] == 'LOCK_TO_ACCOUNT') {
            $aResponse['locked'] = true;
        } else {
            $aResponse['locked'] = false;
        }

        return $aResponse;
    }

    function _getLegacyResponse($sSearch, $s) {
	    $sRegex = '/<[^:]*:' . $sSearch . '[^>]*>[^<]*<[^:]*:StatusCode[^>]*>([^<]*)<\//';
	    if (preg_match($sRegex, $s, $sMatches)) {
	    	return $sMatches[1];
	    }
        return false;
    }

    function _getThumbnailStatus($s) {
        $sRegex = '/<[^:]*:ThumbnailResult?[^>]*>[^<]*<[^:]*:Thumbnail\s*(?:Exists=\"((?:true)|(?:false))\")+[^>]*>([^<]*)<\//';
        if (preg_match($sRegex, $s, $sMatches)) {
            return array('stw_action' => $sMatches[1],
                         'thumbnail' => $sMatches[2]);
        }
        return false;
    }

    /**
     * Determine if specified file has expired from the cache
     */
    function _cacheFileExpired($sFile) {
        // Use setting to check age of files.
        $iCacheDays = $this->cache_days + 0;

        // dont update image once it is cached
        if ($iCacheDays == 0 && file_exists($sFile)) {
            return false;
        // check age of file and if file exists return false, otherwise recache the file
        } else {
            $iCutoff = time() - (3600 * 24 * $iCacheDays);
            return (!file_exists($sFile) || filemtime($sFile) <= $iCutoff);
        }
    }

    /**
     * Safe method to get the value from an array using the specified key
     */
    function _getArrayValue($aArray, $sKey, $isReturnSpace = false) {
        if ($aArray && isset($aArray[$sKey])) {
            return $aArray[$sKey];
        }

        // If returnSpace is true, then return a space rather than nothing at all
        if ($isReturnSpace) {
            return '&nbsp;';
        } else {
            return false;
        }
    }

    /**
    * Gets file content by URL
    */
    function _fileGetContent($sFileUrl, $aParams = array()) {
        $sParams = '?';
        foreach($aParams as $sKey => $sValue)
            $sParams .= $sKey . '=' . $sValue . '&';
        $sParams = substr($sParams, 0, -1);

        $sResult = '';
        if(function_exists('curl_init')) {
            $rConnect = curl_init();

            curl_setopt($rConnect, CURLOPT_URL, $sFileUrl . $sParams);
            curl_setopt($rConnect, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($rConnect, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($rConnect, CURLOPT_HEADER, 0); // must be 0 or else headers will break SimpleXML parsing

            $sAllCookies = '';
            foreach($_COOKIE as $sKey=>$sValue){
                $sAllCookies .= $sKey."=".$sValue.";";
            }
            curl_setopt($rConnect, CURLOPT_COOKIE, $sAllCookies);

            $sResult = curl_exec($rConnect);
            curl_close($rConnect);
        }
        else
            $sResult = @file_get_contents($sFileUrl . $sParams);

        return $sResult;
    }

} // end class