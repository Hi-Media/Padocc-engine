<?php	// $Id: TWMC.php,v 1.358 2011/04/20 16:44:24 lmacret Exp $

require_once('TwengaMemoryCache.php');

class TWMC
{

	private $sRelease;
	private $sGeoZoneCode;
	private $sReleaseGeoZone;
	private $iLanguageId;

	static $oInstance;
	static $aRegisteredInstance = array();
	static $aCacheConfig = array();

	/**
	 * Constructor
	 */
	function __construct(){}

	public function initWebCaches($sGeoZoneCode, $iLanguageId)
	{
		$this->sGeoZoneCode = $sGeoZoneCode;
		$this->iLanguageId = $iLanguageId;

		$this->sRelease = $this->getGlobalRelease();
		$this->sReleaseGeoZone = $this->sRelease.$this->sGeoZoneCode;

		$sKey = $this->sGeoZoneCode.$this->iLanguageId;

		return self::$aCacheConfig = array_merge(
			$this->getGlobalCacheConfig($this->sRelease),
			$this->getGeozoneCacheConfig($this->sGeoZoneCode),
			$this->getGeozoneCacheL2Config($this->sGeoZoneCode, $this->sReleaseGeoZone),
			$this->getAllGzGeozoneCacheConfig(),
			$this->getLGzCacheConfig($this->sGeoZoneCode, $this->iLanguageId),
			$this->getTravelGeozoneCacheConfig($this->sGeoZoneCode,$this->iLanguageId)
		);
	}

	// only called from web
	// Todo recoder toute la class pour supporter l'admin
	public function initAdminCaches($sGeoZoneCode, $iLanguageId)
	{
		global $aTWMC;

		$this->sGeoZoneCode = $sGeoZoneCode;
		$this->iLanguageId = $iLanguageId;

		$this->sRelease = $this->getGlobalRelease();
		$this->sReleaseGeoZone = $this->sRelease.$this->sGeoZoneCode;

		$sKey = $sGeoZoneCode."_".$iLanguageId;
		$aTWMC[$sKey] = array_merge(
			$this->getGlobalCacheConfig($this->sRelease),
			$this->getGeozoneCacheConfig($this->sGeoZoneCode),
			$this->getGeozoneCacheL2Config($this->sGeoZoneCode, $this->sReleaseGeoZone),
			$this->getAllGzGeozoneCacheConfig(),
			$this->getLGzCacheConfig($this->sGeoZoneCode, $this->iLanguageId)
		);

		foreach($aTWMC[$sKey] as $k => $v)
		{
			$sName = $k;
			$aTWMC[$sKey][$k] = new TwengaMemoryCache($aTWMC[$sKey][$k]);
			$aTWMC[$sKey][$k]->cache_name = $sName;
		}
	}

	public function getGlobalRelease()
	{
		$local_db = ADONewConnection(DB_DBMS);
		$local_db->Connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DB);
		$local_db->SetFetchMode(ADODB_FETCH_ASSOC);

		$rs=$local_db->Execute("SELECT NOW() as 'RELEASE'");
		$sRelease = $rs->fields["RELEASE"];

		return str_pad(dechex(crc32($sRelease)), 8, '0', STR_PAD_LEFT);
	}


// only called from fs3
	/**
	* Gen a cachefile from a cache type
	* @param string $sType			Cache type like "TWMC_GLOBAL"
	* @param array $aOnlyRefresh	Specific cache name
	* @access						public
	* @return array					Array of info
	* // TODO Mettre dans une class extend
	**/
	public function initGenCaches($sGeoZoneCode, $sType, $aOnlyRefresh = NULL)
	{
	//	MMCACHE_WEBLOG
		$oDb = ADONewConnection(DB_DBMS);
		$oDb->Connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DB);
		$aTWMC = array();
		if($sType == TWMC_GLOBAL)
		{
			$aTWMC = $this->getGlobalCacheConfig($sRelease);
			return $this->writeCache($aTWMC, $aOnlyRefresh);
		}
		else if($sType == TWMC_LOCAL)
		{
			$aTWMC = $this->getGeozoneCacheConfig($sGeoZoneCode);

			$oRs=$oDb->Execute("SELECT LANG_ID FROM GEOZONE WHERE GEOZONE_CODE=$sGeoZoneCode");
			$aLang=$oRs->getArray();
			foreach ($aLang as $v)
				$aTWMC += $this->getLGzCacheConfig($sGeoZoneCode, $v["LANG_ID"]);

			return $this->writeCache($aTWMC, $aOnlyRefresh);
		}
		else if($sType == TWMC_LOCAL_GLOBAL) // Alias all_gz
		{
			$aTWMC = $this->getAllGzGeozoneCacheConfig();
			return $this->writeCache($aTWMC, $aOnlyRefresh);
		}
		else if($sType == TWMC_LOCAL_L2)
		{
			$aTWMC = $this->getGeozoneCacheL2Config($sGeoZoneCode, $this->sReleaseGeoZone);
			return $this->writeCache($aTWMC, $aOnlyRefresh);
		}
		else if($sType == TWMC_TRAVEL)
		{
			$oRs=$oDb->Execute("SELECT LANG_ID,GEOZONE_CODE FROM LANG WHERE ACTIVE = '1'");
			$aLang=$oRs->getArray();

			foreach ($aLang as $v)
				$aTWMC += $this->getTravelGeozoneCacheConfig($v["GEOZONE_CODE"],$v["LANG_ID"]);

			return $this->writeCache($aTWMC, $aOnlyRefresh);
		}
	}

	// Called by fs3 for gen cache
	private function writeCache(&$aTWMC, $aOnlyRefresh = NULL)
	{
		$iAllCacheSize = $iAllCompressCacheSize = 0;
		$iIntegrity = true;
		//$aTWMCOutput = array();

		if(isset($aTWMC) && count($aTWMC))
		{
			// Just for display log
			ksort($aTWMC);

			echo _echo("Building ".$aTWMC[key($aTWMC)]['CACHE_TYPE']."...", 35)."\n";
			foreach($aTWMC as $k=>$v)
			{
				if($aTWMC[$k]['CACHE_TYPE'] == TWMC_LOCAL_FAST) continue;
				if($aOnlyRefresh != NULL && !in_array($k, $aOnlyRefresh)) continue;
				$aTWMC[$k] = new TwengaMemoryCache($aTWMC[$k]);

				$aTWMC[$k]->cache_name = $k;
				$aTWMC[$k]->refreshData();
				$iAllCacheSize += $aTWMC[$k]->iCacheSize;
				$iAllCompressCacheSize+=$aTWMC[$k]->iCacheSizeSave;
				$iIntegrity = !empty($aTWMC[$k]->sLastError) ? false : $iIntegrity;

				//$aTWMCOutput[$k] = &$aTWMC[$k];
				$sLastKnowFileName = $aTWMC[$k]->sMMCacheFile;
				$this->outPutTwmcInfo($aTWMC[$k]);
			}

			if($iAllCacheSize != 0)
			{
				$a = number_format($iAllCacheSize,0,' ',' ');
				$b = "-".number_format($iAllCompressCacheSize,0,' ',' ');
				$c = filesize($sLastKnowFileName);

				echo str_repeat(" ", 36).str_repeat("-", 36)."\n";
				echo str_repeat(" ", 36).str_repeat(" ", 14 - strlen($a)).($a);
				echo str_repeat(" ", 20 - strlen($b)).($b)."\n";
				echo "Check file size: ".number_format($c,0,' ',' ');
				echo ($c == $iAllCacheSize - $iAllCompressCacheSize)? _echo(" [OK]", 32)."\n" : _echo(" [FAIL]", 31)."\n";
				echo "Check integrity: ";
				echo ($iIntegrity)? _echo(" [OK]", 32) : _echo(" [FAIL]", 31);
				echo "\n\n\n";
			}else $c = 0;
			$aReturn = array("STATUS"=>($c == $iAllCacheSize - $iAllCompressCacheSize)?true:false, "REAL_FILE_SIZE"=>$c, "WAITING_FILE_SIZE"=>($iAllCacheSize - $iAllCompressCacheSize), "TWMC"=>$aTWMC);

			$aTWMC = array();

			return $aReturn;
		}
	}

	private function outPutTwmcInfo(&$oTWMC)
	{
		// Prepare Output
		$iCacheSize = number_format($oTWMC->iCacheSize,0,' ',' ');
		$sCacheId = "[".$oTWMC->cacheid."]";
		$sCacheName = "[".$oTWMC->cache_name."]";

		echo _echo($sCacheName, 32).str_repeat(" ", 35 - strlen($sCacheName)).str_repeat(" ", 15 - strlen($iCacheSize)).($iCacheSize);
		if($oTWMC->iCacheSizeSave != 0)
		{
			$bb = "-".number_format($oTWMC->iCacheSizeSave,0,' ',' ');
			echo str_repeat(" ", 20 - strlen($bb)).$bb;
			echo str_repeat(" ", 20 - strlen($sCacheId));
			echo _echo($sCacheId, 32);

		}
		else
		{
			echo str_repeat(" ", 40 - strlen($sCacheId));
			echo _echo($sCacheId, 32);
		}
		echo "\n";

		if(!empty($oTWMC->sLastError))
		{
			echo _echo($sCacheName." ".$oTWMC->sLastError." in query:", 31);
			echo "\n";
			//echo preg_replace('!([a-z ,_-]{90})!i', "$1\n", $oTWMC->sql);
			echo $oTWMC->sql;
			echo "\n";
		}
	}


	// From master database
	//Absolute caches - no dependency to language or geozone
	public function getGlobalCacheConfig($sRelease)
	{
		// Store actuel version of qanda cache
		$aCacheConfig['GLOBAL_RELEASE'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT "'.$sRelease.'" as "RELEASE"',
		'CACHE_ID' => 'GLOBAL_RELEASE',
		'RELEASE' => $sRelease
		);

		// Used by Monitor.php & load mmcache
		$aCacheConfig['zMONITOR'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT "OK" as "OK"',
		'CACHE_ID' => 'MONITOR'
		);

		$aCacheConfig['GEOZONE'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT GEOZONE_CODE FROM GEOZONE',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['STOPWORDS'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT TYPE, GEOZONE_CODE, KW, IS_NEW FROM STOPWORD_TM ORDER BY TYPE, GEOZONE_CODE',
		'TRAVERSAL_KEY' => array('TYPE', 'GEOZONE_CODE'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['COLORS'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT COLOR_ID, COLOR_ROOT_ID, COLOR, COLOR_NAME FROM COLOR_PICKER ORDER BY COLOR_ID',
		'TRAVERSAL_KEY' => array('COLOR_ID'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['BRAND_URL'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT BRAND_ID, GEOZONE_CODE, URL FROM BRAND_URL_TM',
		'TRAVERSAL_KEY' => array('BRAND_ID','GEOZONE_CODE'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['TRACKERURL_REGEXP'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT SITE_ID, TRACKERURL_REGEXP, TRACKERURL_REP, TRACKERURL_SEQ FROM TRACKERURL_REGEXP ORDER BY SITE_ID,TRACKERURL_SEQ ',
		'TRAVERSAL_KEY' => 'SITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['PLATFORM_SUB_ID'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT * FROM AFFILIATE_PLATFORM_SUBID_TM ORDER BY AFFILIATE_ID ASC, LENGTH(BEFORE_TAG) DESC',
		'TRAVERSAL_KEY' => 'AFFILIATE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['MASTERSITES'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT DISTINCT SITE_NAME,SITE_NAME_ENCODED, MASTER_SITE.SITE_ID,MASTERSITE_ID,
			STORE_TYPE, SITE.HAS_LOGO,SITE.SITE_SC_URL,SITE_URL,LOGO_URL,SITE_DOMAIN,MASTERSITE_EXTRA_INFO.COUNTRY_RANK
			FROM SITE
			INNER JOIN MASTER_SITE USING(SITE_ID)
			INNER JOIN MASTERSITE_INFO ON MASTERSITE_INFO.SITE_ID = MASTER_SITE.MASTERSITE_ID
			LEFT JOIN MASTERSITE_EXTRA_INFO USING(MASTERSITE_ID)
			GROUP BY MASTERSITE_ID
			ORDER BY MASTERSITE_ID ASC',
		'TRAVERSAL_KEY' => 'MASTERSITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['SITE_TRUSTED'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT	SITE_ID AS SITEID, SITE_TRUSTED_TOOL_NAME AS TOOLNAME, SITE_TRUSTED_URL AS PROFILE, SITE_TRUSTED_MARK AS MARK, SITE_TRUSTED_NB_REVIEW AS REVIEW
					FROM SITE_TRUSTED
					WHERE SITE_ID IS NOT NULL GROUP BY SITE_ID ORDER BY SITEID ASC',
		'TRAVERSAL_KEY' => 'SITEID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['TOPBRANDS_BYSITE'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT	MASTER_SITE.MASTERSITE_ID AS SITEID, SUM(CLICK_TRACKING_SUMMARY.SCORE) AS SUMSCORE, BRAND.BRAND_NAME, BRAND.BRAND_ID
    							FROM CLICK_TRACKING_SUMMARY
    							INNER JOIN BRAND USING (BRAND_ID)
    							INNER JOIN MASTER_SITE USING (SITE_ID)
    							WHERE BRAND.HAS_LOGO = 1
    							GROUP BY MASTER_SITE.MASTERSITE_ID,BRAND.BRAND_ID
    							ORDER BY MASTER_SITE.MASTERSITE_ID, SUMSCORE DESC',
		'TRAVERSAL_KEY' => 'SITEID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['MASTERSITE_ECPC'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT CAT_ID, SITE_ID, ECPC FROM MASTERSITE_ECPC ORDER BY SITE_ID, CAT_ID',
		'TRAVERSAL_KEY' => 'SITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['CATEGORY_CURRENCY_CONVERTER'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT GEOZONE_CODE, RATE FROM CATEGORY_CURRENCY_CONVERTER',
		'TRAVERSAL_KEY' => 'GEOZONE_CODE',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['SITE_PROFIL'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT SITE.SITE_ID, SITE.LANG_ID, SITE_NAME, SITE_URL, SITE.SITE_SC_URL, GEOZONE_CODE, SITE_TYPE, SITE_PROFIL.HAS_LOGO as SITE_PROFIL_HAS_LOGO, SITE.HAS_LOGO as SITE_HAS_LOGO, PRODUCT_TYPE, STORE_TYPE, SHIPPING_COUNTRY, SECURE_PAYMENT, SITE_DESCRIPTION, SITE_SERVICE1, SITE_SERVICE2, SITE_SERVICE3, SITE_SERVICE4, SITE_SERVICE5, CANCELLATION_PERIOD, COMPANY_NAME, COMPANY_ADDRESS, COMPANY_ADDRESS2, COMPANY_CITY, COMPANY_ZIPCODE, COMPANY_COUNTRY, COMPANY_PHONE, COMPANY_PHONE_COST, COMPANY_FAX, COMPANY_REGISTRATION_ID, COMPANY_EMAIL, TS, LANG_NAME, COUNTRY_NAME, SITE_PROFIL_ID, MASTERSITE_TRACKER, SITE.SITE_TS_CREATE FROM SITE LEFT JOIN SITE_PROFIL USING(SITE_ID) LEFT JOIN MASTERSITE_INFO USING(SITE_ID) INNER JOIN LANGUAGE ON SITE.LANG_ID=LANGUAGE.LANG_ID LEFT JOIN COUNTRY_NAME ON SITE_PROFIL.COMPANY_COUNTRY=COUNTRY_NAME.COUNTRY_CODE AND SITE.LANG_ID=COUNTRY_NAME.LANG_ID',
		'TRAVERSAL_KEY' => 'SITE.SITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['CONSTANT'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT NAME,VALUE FROM CONSTANT',
		'ALLOW_MULTIPLE_KEY' => true,
		'ALLOW_COMPRESS' => false
		);

		$aCacheConfig['CATEGORY_CHILD'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT CAT_ID,CHILD_ID,1 FROM CATEGORY_CHILD',
		'TRAVERSAL_KEY' => array('CAT_ID','CHILD_ID'),
		'ALLOW_MULTIPLE_KEY' => true,
		'KEEP_KEY' => false
		);

		$aCacheConfig['CATEGORY_LEAF'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT DISTINCT LEAF_ID,1 FROM CATEGORY_LEAF',
		'TRAVERSAL_KEY' => 'LEAF_ID',
		'ALLOW_MULTIPLE_KEY' => true,
		'KEEP_KEY' => false
		);

		$aCacheConfig['CATEGORY_ROOT'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT CAT_ID,ROOT_CAT_ID FROM CATEGORY_ROOT',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true,
		'KEEP_KEY' => false
		);

		$aCacheConfig['CATEGORY_LOW'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT CAT_ID FROM CATEGORY WHERE KEYWORD_PRIORITY = 2',
		'ALLOW_MULTIPLE_KEY' => true,
		'KEEP_KEY' => false
		);

		$aCacheConfig['CATEGORY_VERYLOW'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT CAT_ID FROM CATEGORY WHERE KEYWORD_PRIORITY = 3',
		'ALLOW_MULTIPLE_KEY' => true,
		'KEEP_KEY' => false
		);

		$aCacheConfig['ARTISTS'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT ARTIST_ID,ARTIST_NAME,ARTIST_TYPE FROM ARTIST',
		'TRAVERSAL_KEY' => 'ARTIST_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['ACTORS'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT ACTOR_ID,ACTOR_NAME FROM ACTOR',
		'TRAVERSAL_KEY' => 'ACTOR_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['EDITORS'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT EDITOR_ID,EDITOR_NAME FROM EDITOR',
		'TRAVERSAL_KEY' => 'EDITOR_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['COUNTRY_CODE'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT DISTINCT COUNTRY_CODE FROM COUNTRY_NAME',
		'TRAVERSAL_KEY' => 'COUNTRY_CODE',
		'ALLOW_MULTIPLE_KEY' => true,
		'KEEP_KEY' => false
		);

		$aCacheConfig['CATEGORY_ADULT'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT CAT_ID,TYPE FROM ADULT_CAT_TM',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['CATEGORY_CENSORED'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT CAT_ID FROM CENSORED_CATEGORY',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['IS_CAT_SEP'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT CAT_SEP_ID FROM CATEGORY_SEPARATOR',
		'TRAVERSAL_KEY' => 'CAT_SEP_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['COUNTRY_SUMMARY'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT SUM(PRICE_NB) as PRICE_NB,COUNT(SITE_ID) AS SITE_NB FROM SITE_FULLSIZE_VIEW_TM',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['USER'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => '
			SELECT u.USER_ID, u.USER_NICK, u.USER_SEX, ua.PHOTO_STATUS, uid.DISPLAY_SEX, u.HASH_ID, u.HAS_PHOTO
			FROM USER u
			INNER JOIN USER_ACTIVATION_TM ua USING(USER_ID)
			LEFT JOIN USER_INFO_DISPLAY_TM uid USING(USER_ID) WHERE u.ACTIVE = 1',
		'TRAVERSAL_KEY' => 'USER_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['GG_CATNAME'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT CAT_ID, CAT_NAME FROM CATEGORY_NAME WHERE LANG_ID = 1',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true,
		'KEEP_KEY' => false
		);

		// To rename en SITE_TO_SHARD
		$aCacheConfig['SITE_TO_SHARD_TMP'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT SITE_ID,SHARD_ID FROM SITE',
		'TRAVERSAL_KEY' => 'SITE_ID',
		'ALLOW_MULTIPLE_KEY' => true,
		'MUST_HAVE_KEY' => true
		);

		$aCacheConfig['PRODUCT_SHORT_DATA'] = array(
		'CACHE_TYPE' => TWMC_GLOBAL,
		'QUERY' => 'SELECT PRODUCT.CAT_ID, PRODUCT.PRODUCT_ID, ROOT_CAT_ID, PRODUCT_REF, PRODUCT.BRAND_ID, (PRODUCT_PHOTO.PRODUCT_ID IS NOT NULL) AS HAS_IMG FROM PRODUCT INNER JOIN CATEGORY_ROOT USING(CAT_ID) LEFT JOIN PRODUCT_PHOTO USING(PRODUCT_ID)',
		'TRAVERSAL_KEY' => 'PRODUCT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['MASTERSITES_BYSITE'] 		= $this->getShared('MASTERSITES_BYSITE');
		$aCacheConfig['CATEGORY_PID']				= $this->getShared('CATEGORY_PID');
		$aCacheConfig['CATEGORY_PID_FIRST_CHILDS']	= $this->getShared('CATEGORY_PID_FIRST_CHILDS');

		return ($aCacheConfig);
	}

	// Local Cache
	//Geozone specific caches
	public function getGeozoneCacheConfig($sGeoZoneCode)
	{
		if ((defined('DB_SHARD') && DB_SHARD > 0) || (defined('TWENGA_DB_SHARD') && TWENGA_DB_SHARD > 0))
		{
			$ext_shard = '_CONSO';
		}
		else
		{
			$ext_shard = '';
		}

		// Used by Monitor.php & load mmcache
		$aCacheConfig['zMONITOR'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT "OK" as "OK"',
		'CACHE_ID' => 'MONITOR'
		);

		// A deplacer sur le master
		$aCacheConfig['SITE_TO_SHARD'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT SITE_ID,SHARD_ID FROM SITE WHERE GEOZONE_CODE = '.$sGeoZoneCode.'',
		'TRAVERSAL_KEY' => 'SITE_ID',
		'ALLOW_MULTIPLE_KEY' => true,
		'MUST_HAVE_KEY' => true
		);

		$aCacheConfig['COUNTRY_LOCAL_SUMMARY'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT SUM(PRICE_NB) as PRICE_NB,COUNT(SITE_ID) AS SITE_NB FROM SITE_FULLSIZE WHERE '.$sGeoZoneCode.' = '.$sGeoZoneCode.'',
		'TRAVERSAL_KEY' => NULL,
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['MASTERSITE_SIZE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT MASTERSITE_ID, CAT_ID, SUM(PRICE_NB) AS PRICE_NB FROM SITE_SIZE INNER JOIN MASTER_SITE USING(SITE_ID) WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' GROUP BY CAT_ID,MASTERSITE_ID ORDER BY MASTERSITE_ID',
		'TRAVERSAL_KEY' => 'MASTERSITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['BESTSELLER_ITEM'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT CAT_ID, TYPE, ITEM_ID, SITE_ID,MASTERSITE_ID FROM BESTSELLER_ITEM_CM INNER JOIN SITE USING(SITE_ID) WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND STORE_TYPE = 0 AND RANKING <= 30 AND MASTERSITE_ID NOT IN (117670, 207) GROUP BY MASTERSITE_ID, CAT_ID ORDER BY CAT_ID ASC, RANKING ASC ',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['CATEGORY_BOUNDS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT CAT_ID, MIN_BOUND,MAX_BOUND FROM PRICEBOUNDS_ON_CATEGORY_CM WHERE GEOZONE_CODE = '.$sGeoZoneCode.'',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['SPONSO_PACK'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT PACK_ID, MASTERSITE_ID, ROOT_CAT_ID, TEXT_LINK, START_DATE, END_DATE FROM SALES_SPONSOPACK_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.'',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['WEB_BOX'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT WEBBOX_ID, TITLE, CONTENT,ROOT_CAT_ID, CAT_ID_LIST, TS_START, TS_END, ACTIVE, DELETED, TYPE, LINK_URL,LINK_FOLLOW,POSTIMP_URL FROM WEB_BOX_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY ROOT_CAT_ID, SEQUENCE',
		'TRAVERSAL_KEY' => 'ROOT_CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['FAVBRANDS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT CAT_ID, NB_PRODUCTS, BRAND.BRAND_ID, BRAND_NAME, NB_PRODUCTS_SEO_OPENED, BRAND.HAS_LOGO FROM CATEGORY_TOPBRAND INNER JOIN BRAND USING(BRAND_ID) WHERE NB_PRODUCTS > 5 AND '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY CAT_ID,NB_PRODUCTS DESC,BRAND_NAME',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		// A Virer
		$aCacheConfig['FAVBRANDS_ATTRIB_VALUE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT abs.BRAND_ID as "0" ,abs.CAT_ID,abs.ATTRIB_VALUE_ID
			FROM ATTRIBUTE_BRAND_CAT_SIZE_CM abs
			INNER JOIN BRAND b ON b.BRAND_ID = abs.BRAND_ID
			JOIN ATTRIBUTE_VALUE av ON av.ATTRIB_VALUE_ID = abs.ATTRIB_VALUE_ID
			JOIN ATTRIBUTE_DICTIONARY_ON_GEOZONE adg ON adg.ATTRIB_ID = av.ATTRIB_ID AND adg.GEOZONE_CODE = '.$sGeoZoneCode.'
			WHERE  SEO_CROSS_BRAND = 1 AND abs.NB >=3
			ORDER BY abs.CAT_ID, abs.ATTRIB_VALUE_ID, NB DESC',
		'TRAVERSAL_KEY' => array('abs.CAT_ID','abs.ATTRIB_VALUE_ID'),
		'ALLOW_MULTIPLE_KEY' => true,
		'KEEP_KEY' => false
		);

		$aCacheConfig['ATTRIB'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT adg.ATTRIB_ID,ad.ATTRIB_TYPE, adg.ATTRIB_NAME, adg.SEO_INDEX, adg.SEO_CROSS_BRAND, au.*
			FROM ATTRIBUTE_DICTIONARY_ON_GEOZONE adg
			JOIN ATTRIBUTE_DICTIONARY ad ON adg.ATTRIB_ID = ad.ATTRIB_ID
			LEFT JOIN ATTRIBUTE_UNIT au ON au.UNIT_ID = adg.UNIT_ID
			WHERE adg.GEOZONE_CODE='.$sGeoZoneCode.' ORDER BY adg.ATTRIB_ID',
		'TRAVERSAL_KEY' => 'adg.ATTRIB_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['ATTRIB_VALUES'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT ATTRIB_VALUE_NAME, ATTRIB_VALUE_ID, ATTRIB_ID FROM ATTRIBUTE_VALUE WHERE ATTRIB_VALUE_NAME != \'NULL\' AND '.$sGeoZoneCode.'='.$sGeoZoneCode,
		'TRAVERSAL_KEY' => 'ATTRIB_VALUE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['ATTRIB_INACTIVE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT  cf.ATTRIB_ID, cf.CAT_ID, ATTRIB_ACTIVE FROM ATTRIBUTE_ON_CATEGORY_LEAF cf JOIN ATTRIBUTE_ON_CATEGORY c ON c.CAT_ID = cf.CAT_PID AND c.GEOZONE_CODE = cf.GEOZONE_CODE AND c.ATTRIB_ID = cf.ATTRIB_ID WHERE cf.GEOZONE_CODE = '.$sGeoZoneCode.' AND ATTRIB_ACTIVE = 0',
		'TRAVERSAL_KEY' => array('cf.CAT_ID','cf.ATTRIB_ID'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['ATTRIB_SEQ'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT acl.CAT_ID,acl.DISPLAY_SEQ, ad.ATTRIB_ID
			FROM ATTRIBUTE_DICTIONARY ad JOIN ATTRIBUTE_DICTIONARY_ON_GEOZONE adg ON adg.ATTRIB_ID=ad.ATTRIB_ID
			JOIN ATTRIBUTE_ON_CATEGORY ac ON ac.ATTRIB_ID=ad.ATTRIB_ID AND ac.GEOZONE_CODE=adg.GEOZONE_CODE
			JOIN ATTRIBUTE_ON_CATEGORY_LEAF acl ON acl.ATTRIB_ID=adg.ATTRIB_ID AND acl.GEOZONE_CODE=adg.GEOZONE_CODE AND acl.CAT_PID=ac.CAT_ID
			WHERE adg.GEOZONE_CODE = '.$sGeoZoneCode.' AND ac.ATTRIB_ACTIVE=1
			ORDER BY acl.CAT_ID,ad.ATTRIB_ID',
		'TRAVERSAL_KEY' => array('acl.CAT_ID','ad.ATTRIB_ID'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['CAT_ATTRIB'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT acl.CAT_ID,acl.DISPLAY_SEQ, ad.ATTRIB_TYPE, ad.ATTRIB_ID, adg.ATTRIB_NAME, au.UNIT_NAME, adg.SEO_INDEX, adg.SEO_CROSS_BRAND
			FROM ATTRIBUTE_DICTIONARY ad JOIN ATTRIBUTE_DICTIONARY_ON_GEOZONE adg ON adg.ATTRIB_ID=ad.ATTRIB_ID
			JOIN ATTRIBUTE_ON_CATEGORY ac ON ac.ATTRIB_ID=ad.ATTRIB_ID AND ac.GEOZONE_CODE=adg.GEOZONE_CODE
			JOIN ATTRIBUTE_ON_CATEGORY_LEAF acl ON acl.ATTRIB_ID=adg.ATTRIB_ID AND acl.GEOZONE_CODE=adg.GEOZONE_CODE AND acl.CAT_PID=ac.CAT_ID
			LEFT JOIN ATTRIBUTE_UNIT au ON au.UNIT_ID=adg.UNIT_ID WHERE
			adg.GEOZONE_CODE='.$sGeoZoneCode.' AND ac.ATTRIB_ACTIVE=1
			ORDER BY acl.CAT_ID,DISPLAY_SEQ',
		'TRAVERSAL_KEY' => 'acl.CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['ACTOR_SIZE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT ACTOR_ID, PRICES_NB FROM ACTOR_SIZE_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode,
		'TRAVERSAL_KEY' => 'ACTOR_ID',
		'ALLOW_MULTIPLE_KEY' => true,
		'ALLOW_COMPRESS' => false
		);

		$aCacheConfig['ARTIST_VIDEO_SIZE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT agi.ARTIST_ID, count(PRICE_NB) as PRICES_NB FROM VIDEO_PRICE_SUMMARY vps
			JOIN VIDEO c ON c.VIDEO_ID = vps.VIDEO_ID
			JOIN ARTIST_GROUP_ITEMS agi ON agi.ARTIST_GROUP_ID = c.ARTIST_GROUP_ID
			WHERE vps.PRICE_NB > 2 AND '.$sGeoZoneCode.'='.$sGeoZoneCode.'
			GROUP BY agi.ARTIST_ID
			HAVING count(PRICE_NB) > 3',
		'TRAVERSAL_KEY' => 'ARTIST_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['ARTIST_MUSIC_SIZE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT agi.ARTIST_ID, count(PRICE_NB) as PRICES_NB FROM MUSIC_PRICE_SUMMARY vps
			JOIN MUSIC c ON c.MUSIC_ID = vps.MUSIC_ID
			JOIN ARTIST_GROUP_ITEMS agi ON agi.ARTIST_GROUP_ID = c.ARTIST_GROUP_ID
			WHERE vps.PRICE_NB > 2 AND '.$sGeoZoneCode.'='.$sGeoZoneCode.'
			GROUP BY agi.ARTIST_ID
			HAVING count(PRICE_NB) > 3',
		'TRAVERSAL_KEY' => 'ARTIST_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['ARTIST_BOOK_SIZE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT agi.ARTIST_ID, count(PRICE_NB) as PRICES_NB FROM BOOK_PRICE_SUMMARY vps
			JOIN BOOK c ON c.BOOK_ID = vps.BOOK_ID
			JOIN ARTIST_GROUP_ITEMS agi ON agi.ARTIST_GROUP_ID = c.ARTIST_GROUP_ID
			WHERE vps.PRICE_NB > 2 AND '.$sGeoZoneCode.'='.$sGeoZoneCode.'
			GROUP BY agi.ARTIST_ID
			HAVING count(PRICE_NB) > 3',
		'TRAVERSAL_KEY' => 'ARTIST_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['MERCHANT_RATINGS_SUMMARY'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT SITE.SITE_ID, VOTES,  MERCHANT_REV_RECOMMEND, MERCHANT_REV_RECOMMEND_PERCENT, MERCHANT_REVNOTE, MERCHANT_REVNOTE1, MERCHANT_REVNOTE2, MERCHANT_REVNOTE3, MERCHANT_REVNOTE4 FROM MERCHANTREVIEW_SUMMARY INNER JOIN SITE USING (SITE_ID) WHERE GEOZONE_CODE='.$sGeoZoneCode,
		'TRAVERSAL_KEY' => 'SITE.SITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['DROPCLASS_BY_BRAND'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT BRAND_ID, BRAND_SIZE.CAT_ID, DROPCLASS_NB FROM BRAND_SIZE WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND DROPCLASS_NB > 0 ORDER BY BRAND_ID',
		'TRAVERSAL_KEY' => 'BRAND_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['CLICK_TRACKING_SUMMARY'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT SEO_INDEX, TYPE, IS_FINAL_CAT, ITEM_REVIEW_URL, BRAND_ID, ITEM_ID, CAT_ID, BEST_PRICE, ITEM_URL, IS_ADULT, ITEM_DESIGNATION, SCORE, NB_CLICKS, MAIN_CAT_ID,SITE_ID,AFFILIATE_ID,MASTERSITE_ID, BEST_ITEM_ID AS BID,BEST_ITEM_PRICE AS BIP,ATTRIB_VALUE_ID_1 AS A1,ATTRIB_VALUE_ID_2 AS A2,ATTRIB_VALUE_ID_3 AS A3,ATTRIB_VALUE_ID_4 AS A4,ATTRIB_VALUE_ID_5 AS A5 FROM CLICK_TRACKING_SUMMARY'.$ext_shard.' LEFT JOIN MASTER_SITE USING(SITE_ID) WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY CAT_ID,SCORE DESC',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['NB_CLICKS_CAT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT SUM(NB_CLICKS) as NB_CLICKS, CAT_ID FROM CLICK_TRACKING_SUMMARY'.$ext_shard.' WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' GROUP BY CAT_ID ORDER BY CAT_ID',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['NB_CLICKS_CAT_BY_SITE_ID'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT SUM(NB_CLICKS) as NB_CLICKS,SITE_ID,CAT_ID,ROOT_CAT_ID FROM CLICK_TRACKING_SUMMARY'.$ext_shard.' WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND IS_FINAL_CAT = 0 AND CAT_ID != ROOT_CAT_ID GROUP BY CAT_ID, SITE_ID ORDER BY ROOT_CAT_ID,SITE_ID,NB_CLICKS DESC',
		'TRAVERSAL_KEY' => array("ROOT_CAT_ID","SITE_ID"),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['POPULAR_BRANDS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT SUM(NB_CLICKS) as NB_CLICKS, CAT_ID, BRAND_ID, BRAND_NAME FROM CLICK_TRACKING_SUMMARY'.$ext_shard.' INNER JOIN BRAND USING(BRAND_ID) WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND BRAND_ID != 0 GROUP BY BRAND_ID, CAT_ID ORDER BY CAT_ID, NB_CLICKS DESC',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['SITE_FULLSIZE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT SUM(PRICE_NB) as PRICE_NB, MASTERSITE_ID FROM MASTER_SITE INNER JOIN SITE_FULLSIZE USING(SITE_ID) WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' GROUP BY MASTERSITE_ID ORDER BY MASTERSITE_ID',
		'TRAVERSAL_KEY' => 'MASTERSITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['ADS_DISALLOW'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT CAT_ID, PROVIDER, GEOZONE_CODE FROM ADS_DISALLOW WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode,
		'ALLOW_MULTIPLE_KEY' => true,
		'KEEP_KEY' => false,
		'ALLOW_COMPRESS' => false
		);

		$aCacheConfig['BMV_AMAZON_VIDEO'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT (VIDEO_PHOTO.VIDEO_ID IS NOT NULL) AS HAS_IMG, VIDEO_PRICE_SUMMARY.BEST_PRICE, VIDEO_PRICE_SUMMARY.MERCHANT_NB, VIDEO.VIDEO_REF, BMV_AMAZON_CM.PRODUCT_ID AS ID, BMV_AMAZON_CM.CAT_ID, BMV_AMAZON_CM.FEED FROM BMV_AMAZON_CM INNER JOIN VIDEO_PRICE_SUMMARY ON BMV_AMAZON_CM.PRODUCT_ID = VIDEO_PRICE_SUMMARY.VIDEO_ID INNER JOIN VIDEO ON BMV_AMAZON_CM.PRODUCT_ID = VIDEO.VIDEO_ID LEFT JOIN VIDEO_PHOTO ON VIDEO.VIDEO_ID=VIDEO_PHOTO.VIDEO_ID WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY CAT_ID, FEED, BMV_AMAZON_CM.ID ASC',
		'TRAVERSAL_KEY' => array('CAT_ID','FEED'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['BMV_AMAZON_MUSIC'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT (MUSIC_PHOTO.MUSIC_ID IS NOT NULL) AS HAS_IMG, MUSIC_PRICE_SUMMARY.BEST_PRICE,MUSIC_PRICE_SUMMARY.MERCHANT_NB, MUSIC.MUSIC_REF, BMV_AMAZON_CM.PRODUCT_ID AS ID, BMV_AMAZON_CM.CAT_ID, BMV_AMAZON_CM.FEED, GROUP_CONCAT(ARTIST.ARTIST_NAME), ARTIST_GROUP_ITEMS.ARTIST_ID, ARTIST_GROUP_ITEMS.ARTIST_GROUP_ID FROM BMV_AMAZON_CM INNER JOIN  MUSIC_PRICE_SUMMARY ON BMV_AMAZON_CM.PRODUCT_ID = MUSIC_PRICE_SUMMARY.MUSIC_ID INNER JOIN MUSIC ON BMV_AMAZON_CM.PRODUCT_ID = MUSIC.MUSIC_ID LEFT JOIN MUSIC_PHOTO ON MUSIC.MUSIC_ID=MUSIC_PHOTO.MUSIC_ID INNER JOIN ARTIST_GROUP_ITEMS ON MUSIC.ARTIST_GROUP_ID = ARTIST_GROUP_ITEMS.ARTIST_GROUP_ID INNER JOIN ARTIST ON ARTIST.ARTIST_ID = ARTIST_GROUP_ITEMS.ARTIST_ID WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' GROUP BY CAT_ID,FEED, BMV_AMAZON_CM.ID  ORDER BY CAT_ID, FEED, BMV_AMAZON_CM.ID ASC',
		'TRAVERSAL_KEY' => array('CAT_ID','FEED'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['BMV_AMAZON_BOOK'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT (BOOK_PHOTO.BOOK_ID IS NOT NULL) AS HAS_IMG, BOOK_PRICE_SUMMARY.BEST_PRICE,BOOK_PRICE_SUMMARY.MERCHANT_NB,  BOOK.BOOK_REF, BMV_AMAZON_CM.PRODUCT_ID AS ID, BMV_AMAZON_CM.CAT_ID, BMV_AMAZON_CM.FEED FROM BMV_AMAZON_CM INNER JOIN BOOK_PRICE_SUMMARY ON BMV_AMAZON_CM.PRODUCT_ID = BOOK_PRICE_SUMMARY.BOOK_ID INNER JOIN BOOK ON BMV_AMAZON_CM.PRODUCT_ID = BOOK.BOOK_ID LEFT JOIN BOOK_PHOTO ON BOOK.BOOK_ID=BOOK_PHOTO.BOOK_ID WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY CAT_ID, FEED, BMV_AMAZON_CM.ID ASC',
		'TRAVERSAL_KEY'=>array('CAT_ID','FEED'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['PARTNER_LINKS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT PARTNER_NAME,PARTNER_URL,PARTNER_URLTITLE,PARTNER_URL_SCRAMBLE,PARTNER_PROPAGATE,CAT_ID,BRAND_ID,POSITION FROM PARTNER WHERE GEOZONE_CODE = '.$sGeoZoneCode.' ORDER BY CAT_ID,PARTNER_NAME',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['TOP_CAT_BY_MASTERSITEID'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT MAIN_CAT_ID as CAT_ID, SUM(NB_CLICKS) as NB_CLICKS, MASTERSITE_ID FROM CLICK_TRACKING_SUMMARY'.$ext_shard.' CTS INNER JOIN MASTER_SITE ON MASTER_SITE.SITE_ID = CTS.SITE_ID WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND MASTER_SITE.MASTERSITE_ID IS NOT NULL GROUP BY MAIN_CAT_ID ORDER BY MASTERSITE_ID, NB_CLICKS DESC',
		'TRAVERSAL_KEY' => 'MASTERSITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['SHOPPING_GUIDES'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT DISTINCT CAT_ID, CMS_EVENTS.CMS_EVENT_ID, CMS_EVENTS.H1_TITLE FROM CMS_EVENTS INNER JOIN CMS_EVENT_BLOCKS ON CMS_EVENT_BLOCKS.CMS_EVENT_ID = CMS_EVENTS.CMS_EVENT_ID INNER JOIN CMS_EVENT_BLOCK_CATS ON CMS_EVENT_BLOCK_CATS.CMS_BLOCK_ID = CMS_EVENT_BLOCKS.CMS_BLOCK_ID WHERE CMS_EVENTS.GEOZONE_CODE ='.$sGeoZoneCode.' AND CMS_EVENTS.PUBLISH = 1 ORDER BY CAT_ID ASC',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['CATEGORY_SIZE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT * FROM CATEGORY_SIZE WHERE GEOZONE_CODE='.$sGeoZoneCode,
		'TRAVERSAL_KEY' => 'CAT_ID',
		'KEEP_KEY' => false
		);

		$aCacheConfig['BRAND_SIZE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT BRAND_ID, CAT_ID, PRICE_NB FROM BRAND_SIZE WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY BRAND_ID',
		'TRAVERSAL_KEY' => array("CAT_ID","BRAND_ID"),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['BRAND_SIZE_LEAF_BY_BRAND'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT BRAND_ID, CAT_ID, PRICE_NB FROM BRAND_SIZE_LEAF WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY BRAND_ID',
		'TRAVERSAL_KEY' => 'BRAND_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['NB_USER'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT count(USER_ID) AS NB_USER FROM USER WHERE GEOZONE_CODE = '.$sGeoZoneCode,
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['TWADS_CAMPAIGNS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT UNIX_TIMESTAMP(NOW()) as TS_LAST_UPDATE, C.CAMPAIGN_ID, C.MASTERSITE_ID, C.PACK_ID, C.GROUP_ID, C.TS_START, C.TS_END, UNIX_TIMESTAMP(C.TS_START) AS UNIX_TS_START, UNIX_TIMESTAMP(C.TS_END) AS UNIX_TS_END, C.SEQUENCE, C.ACTIVE, F.FORMAT_ID, F.LABEL, F.DESCRIPTION, CR2.CRITERIA_VALUE AS ROOT_CAT_ID, GROUP_CONCAT(CR.CRITERIA_TYPE) AS CRITERIA_TYPES, GROUP_CONCAT(CR.CRITERIA_VALUE) AS CRITERIA_VALUES
			FROM ADS_CAMPAIGNS_CM C
			INNER JOIN ADS_FORMATS_CM F USING(FORMAT_ID)
			INNER JOIN ADS_CRITERIAS_CM CR USING(CAMPAIGN_ID)
			INNER JOIN ADS_CRITERIAS_CM CR2 ON CR2.CAMPAIGN_ID = C.CAMPAIGN_ID AND (CR2.CRITERIA_TYPE = "ROOT_CAT_ID")
			WHERE (C.TS_END > NOW() || C.TS_END = "0000-00-00") AND C.ACTIVE = 1 AND C.DELETED = 0 AND '.$sGeoZoneCode.'='.$sGeoZoneCode.'
			GROUP BY C.CAMPAIGN_ID, ROOT_CAT_ID
			ORDER BY FORMAT_ID, ROOT_CAT_ID DESC, SEQUENCE ASC
		',
		'TRAVERSAL_KEY' => array('FORMAT_ID', 'ROOT_CAT_ID'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['TWADS_PARAMS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT UNIX_TIMESTAMP(NOW()) as TS_LAST_UPDATE, PV.PARAM_ID, P.FORMAT_ID, PV.CAMPAIGN_ID, P.PARAM_NAME, P.PARAM_TYPE, PV.PARAM_VALUE
			FROM ADS_PARAMS_VALUES_CM PV
			INNER JOIN ADS_PARAMS_CM P USING(PARAM_ID)
			INNER JOIN ADS_CAMPAIGNS_CM C USING(CAMPAIGN_ID)
			WHERE (C.TS_END > NOW() || C.TS_END = "0000-00-00") AND C.ACTIVE = 1 AND C.DELETED = 0 AND '.$sGeoZoneCode.'='.$sGeoZoneCode.'
			ORDER BY PV.CAMPAIGN_ID
		',
		'TRAVERSAL_KEY' => 'CAMPAIGN_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['ABTESTING'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT TEST_ID, NAME, SCENARIO, WEIGHT, PARAM
			FROM ABTESTS_SCENARIO_CM
			WHERE TEST_ID = (
					SELECT TEST_ID
					FROM ABTESTS_CM
					WHERE TS_START <= NOW() AND  ( TS_END > NOW() OR TS_END = "0000-00-00")  AND ACTIVE = 1 AND DELETED = 0
					ORDER BY TS_START DESC
					LIMIT 1
				)
			ORDER BY NAME ASC, SCENARIO ASC, WEIGHT ASC, PARAM ASC
		',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['BACKLINKS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT URL_ID FROM BACKLINK_REDIRECT_CM',
		'TRAVERSAL_KEY' => 'URL_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['VOUCHER_CODE_ALL_MERCHANTS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => "SELECT DISTINCT V.MASTERSITE_ID, C.NB_REBATES, S.SITE_NAME FROM VOUCHER_CODE_CM V INNER JOIN SITE S ON V.MASTERSITE_ID = S.SITE_ID LEFT JOIN (SELECT COUNT(*) AS NB_REBATES, MASTERSITE_ID FROM VOUCHER_CODE_CM WHERE VALIDATED = 1 AND EXCLUSIF = 0 AND (START_DATE < NOW() OR START_DATE = '0000-00-00') AND ( END_DATE > NOW() OR END_DATE = '0000-00-00') GROUP BY MASTERSITE_ID) C ON V.MASTERSITE_ID = C.MASTERSITE_ID WHERE V.VALIDATED = 1 AND ".$sGeoZoneCode."=".$sGeoZoneCode." ORDER BY S.SITE_NAME ASC",
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['VOUCHER_CODE_HEADER_COUNT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => "SELECT COUNT(*) AS NB_MERCHANTS, SUM(CODE_PER_MERCHANT) AS NB_CODES FROM (SELECT COUNT(*) AS CODE_PER_MERCHANT FROM VOUCHER_CODE_CM INNER JOIN SITE S ON MASTERSITE_ID = S.SITE_ID WHERE VALIDATED = 1 AND (START_DATE < NOW() OR START_DATE = '0000-00-00') AND (END_DATE > NOW() OR END_DATE = '0000-00-00') GROUP BY MASTERSITE_ID) C WHERE ".$sGeoZoneCode."=".$sGeoZoneCode,
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['VOUCHER_CODE_NB_PER_MERCHANT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => "SELECT COUNT(*) AS NB_REBATES, MASTERSITE_ID FROM VOUCHER_CODE_CM INNER JOIN SITE S ON MASTERSITE_ID = S.SITE_ID WHERE ".$sGeoZoneCode."=".$sGeoZoneCode." AND VALIDATED = 1 AND EXCLUSIF = 0 AND (START_DATE < NOW() OR START_DATE = '0000-00-00') AND (END_DATE > NOW() OR END_DATE = '0000-00-00') GROUP BY MASTERSITE_ID",
		'TRAVERSAL_KEY' => 'MASTERSITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['VOUCHER_CODE_UNIV_TOP_MERCHANT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT ROOT_CAT_ID, NB_REBATES, MASTERSITE_ID, SITE_NAME, HAS_LOGO, @num
			FROM (
				SELECT ROOT_CAT_ID, COUNT(VC.VOUCHER_CODE_ID) AS NB_REBATES, VOUCHER_CODE_SITE_CM.MASTERSITE_ID, S.SITE_NAME, S.HAS_LOGO
				FROM (SELECT @ROOT_CAT_ID:=0) x, (SELECT @num:=0) xx, VOUCHER_CODE_SITE_CM
				INNER JOIN SITE S ON S.SITE_ID = VOUCHER_CODE_SITE_CM.MASTERSITE_ID
				INNER JOIN MERCHANT M ON M.SITE_ID = VOUCHER_CODE_SITE_CM.MASTERSITE_ID
				LEFT JOIN (
					SELECT VOUCHER_CODE_ID, MASTERSITE_ID
					FROM VOUCHER_CODE_CM
					WHERE (START_DATE < NOW() OR START_DATE = "0000-00-00") AND (END_DATE > NOW() OR END_DATE = "0000-00-00")
					AND VALIDATED = 1
					AND EXCLUSIF = 0
				) VC ON VOUCHER_CODE_SITE_CM.MASTERSITE_ID = VC.MASTERSITE_ID
				WHERE AFFILIATE_ID > 0
				AND ROOT_CAT_ID > 0
				AND '.$sGeoZoneCode.'='.$sGeoZoneCode.'
				GROUP BY VOUCHER_CODE_SITE_CM.MASTERSITE_ID ORDER BY ROOT_CAT_ID, NB_REBATES DESC
			) cl
			WHERE
			(@num := if(cl.ROOT_CAT_ID = @ROOT_CAT_ID, @num + 1, 1)) is not null
			AND (@ROOT_CAT_ID := cl.ROOT_CAT_ID) is not null
			AND (@num <= 24)',
		'TRAVERSAL_KEY' => 'ROOT_CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['VOUCHER_CODE_TOP_MERCHANT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT COUNT(*) AS NB_REBATES, VOUCHER_CODE_CM.MASTERSITE_ID, S.SITE_NAME, S.HAS_LOGO
			FROM VOUCHER_CODE_CM
			INNER JOIN VOUCHER_CODE_SITE_CM ON VOUCHER_CODE_SITE_CM.MASTERSITE_ID = VOUCHER_CODE_CM.MASTERSITE_ID
			INNER JOIN SITE S ON S.SITE_ID = VOUCHER_CODE_CM.MASTERSITE_ID
			INNER JOIN MERCHANT M ON M.SITE_ID = VOUCHER_CODE_CM.MASTERSITE_ID
			WHERE AFFILIATE_ID > 0 AND VALIDATED = 1 AND EXCLUSIF = 0
			AND (START_DATE < NOW() OR START_DATE = "0000-00-00")
			AND (END_DATE > NOW() OR END_DATE = "0000-00-00")
			AND '.$sGeoZoneCode.'='.$sGeoZoneCode.'
			GROUP BY VOUCHER_CODE_CM.MASTERSITE_ID
			ORDER BY NB_REBATES DESC
			LIMIT 24',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['VOUCHER_CODE_EXPIRED'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT VOUCHER_CODE_CM.MASTERSITE_ID, DESCRIPTION, START_DATE, END_DATE, VOUCHER_CODE_ID, SITE.HAS_LOGO, SITE.SITE_NAME, CODE FROM VOUCHER_CODE_CM INNER JOIN SITE ON SITE.SITE_ID = VOUCHER_CODE_CM.MASTERSITE_ID WHERE VALIDATED = 1 AND (START_DATE < NOW() OR START_DATE = "0000-00-00") AND (END_DATE < NOW() AND END_DATE != "0000-00-00") AND '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY VOUCHER_CODE_CM.MASTERSITE_ID,TS_INSERT DESC',
		'TRAVERSAL_KEY' => 'VOUCHER_CODE_CM.MASTERSITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['VOUCHER_CODES'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT VOUCHER_CODE_CM.MASTERSITE_ID, DESCRIPTION, START_DATE, END_DATE, VOUCHER_CODE_ID, SITE.HAS_LOGO, SITE.SITE_NAME, CODE, ROOT_CAT_ID, EXCLUSIF FROM VOUCHER_CODE_CM INNER JOIN SITE ON SITE.SITE_ID = VOUCHER_CODE_CM.MASTERSITE_ID LEFT JOIN VOUCHER_CODE_SITE_CM ON VOUCHER_CODE_SITE_CM.MASTERSITE_ID = VOUCHER_CODE_CM.MASTERSITE_ID WHERE VALIDATED = 1 AND (START_DATE < NOW() OR START_DATE = "0000-00-00") AND (END_DATE > NOW() OR END_DATE = "0000-00-00") AND '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY TS_INSERT DESC',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['VOUCHER_CODE_MASTERSITE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT VOUCHER_CODE_CM.VOUCHER_CODE_ID, VOUCHER_CODE_CM.URL, VOUCHER_CODE_FEED_CM.MASTERSITE_ID
					FROM VOUCHER_CODE_CM
						INNER JOIN VOUCHER_CODE_FEED_CM using(VOUCHER_CODE_FEED_ID)
					WHERE VALIDATED = 1 AND (START_DATE < NOW() OR START_DATE = "0000-00-00") AND (END_DATE > NOW() OR END_DATE = "0000-00-00")
						and VOUCHER_CODE_CM.URL != "" and VOUCHER_CODE_FEED_CM.MASTERSITE_ID != 0 AND '.$sGeoZoneCode.'='.$sGeoZoneCode.'
					ORDER BY TS_INSERT DESC',
		'TRAVERSAL_KEY' => 'VOUCHER_CODE_CM.VOUCHER_CODE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['CATEGORY_CONTENT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT CAT_ID, TYPE_CONTENT, TEXT_CONTENT FROM CATEGORY_CONTENT_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY CAT_ID, TS_LAST_UPDATE DESC',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['CATEGORY_PHOTO'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT ITEM_ID, TYPE, CAT_ID FROM CATEGORY_PHOTO WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode,
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['TOP_MASTERSITE_BY_CAT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT CAT_ID,CATEGORY_SITE_NB_CLICK_CM.SITE_ID,MASTER_SITE.MASTERSITE_ID,SITE_NAME,NB_CLICKS,MERCHANT_REVNOTE, AFFILIATE_ID, SITE.STORE_TYPE FROM CATEGORY_SITE_NB_CLICK_CM INNER JOIN MASTER_SITE USING (SITE_ID) INNER JOIN SITE USING (SITE_ID) LEFT JOIN MERCHANT ON MERCHANT.SITE_ID = SITE.SITE_ID LEFT JOIN MERCHANTREVIEW_SUMMARY ON SITE.SITE_ID = MERCHANTREVIEW_SUMMARY.SITE_ID WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' GROUP BY CAT_ID,CATEGORY_SITE_NB_CLICK_CM.SITE_ID ORDER BY CAT_ID ASC, NB_CLICKS DESC, AFFILIATE_ID DESC ',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);





		return($aCacheConfig);
	}



	// Local Cache L2
	public function getGeozoneCacheL2Config($sGeoZoneCode, $sReleaseGeoZone)
	{
		$URL_PATH_TABLE = 'URL_PATH_TMP_LOCAL';
		if ((defined('DB_SHARD') && DB_SHARD > 0) || (defined('TWENGA_DB_SHARD') && TWENGA_DB_SHARD > 0))
		{
			$ext_shard = '_CONSO';
		}
		else
		{
			$ext_shard = '';
		}

		// Store actuel version of L2 cache
		$aCacheConfig['L2_RELEASE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT "'.$sReleaseGeoZone.'" as "RELEASE"',
		'CACHE_ID' => 'L2_RELEASE',
		'RELEASE' => $sReleaseGeoZone
		);

		$aCacheConfig['REFRESH'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT TS_REFRESH FROM REFRESH_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY TS_REFRESH DESC LIMIT 1',
		'ALLOW_MULTIPLE_KEY' => false,
		'KEEP_KEY'=>false
		);

		// Used by Monitor.php
		$aCacheConfig['TEST_L2'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT "OK" as "OK"',
		'CACHE_ID' => 'TEST_L2'
		);


		$aCacheConfig['TOPITEMS_BYSITEID'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
		SELECT MASTERSITE_ID,cl.ITEM_DESIGNATION AS DESIGNATION, ITEM_ID, CAT_ID, IS_ADULT, SCORE, TYPE, @num
        FROM
        (
        SELECT MASTERSITE_ID,ITEM_DESIGNATION, ITEM_ID, CAT_ID, IS_ADULT, SCORE, TYPE, (SELECT @MASTERSITE_ID:=0) x, (SELECT @num:=0) xx
        FROM CLICK_TRACKING_SUMMARY'.$ext_shard.' LEFT JOIN MASTER_SITE USING(SITE_ID) WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND IS_ADULT = FALSE AND IS_FINAL_CAT = 1 ORDER BY MASTERSITE_ID,SCORE DESC
        ) cl
        WHERE
        (@num := if(cl.MASTERSITE_ID = @MASTERSITE_ID, @num + 1, 1)) is not null
        			AND (@MASTERSITE_ID := cl.MASTERSITE_ID) is not null
        			AND (@num <= 20)
		',
		'TRAVERSAL_KEY' => 'MASTERSITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);
		
		$aCacheConfig['TOPUNIVS_BYSITEID'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
    		SELECT SCORE, ROOT_CAT_ID AS ROOTCATID, MASTER_SITE_ID AS MASTERSITE_ID,@num
            FROM
            (
            SELECT
            SUM(NB_CLICKS) AS SCORE
            , ROOT_CAT_ID
            , MASTER_SITE_ID
            ,(SELECT @MASTERSITE_ID:=0) x, (SELECT @num:=0) xx
            FROM
            HY2_MONETIZATION_SITE_CAT_MY_DM_LOCAL
            JOIN CATEGORY_ROOT
                USING(CAT_ID)
            WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.'
            AND ROOT_CAT_ID != 1
            GROUP BY
            ROOT_CAT_ID,MASTER_SITE_ID
            ORDER BY MASTER_SITE_ID, ROOT_CAT_ID
            ) cl
             WHERE
                    (@num := if(cl.MASTER_SITE_ID = @MASTERSITE_ID, @num + 1, 1)) is not null
                    			AND (@MASTERSITE_ID := cl.MASTER_SITE_ID) is not null
                    			AND (@num <= 3)
		',
		'TRAVERSAL_KEY' => 'MASTERSITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['SCORE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT PRODUCT_ID,REVIEW_NB,SCORE FROM SCORE WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode,
		'TRAVERSAL_KEY' => 'PRODUCT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['PRICE_SUMMARY'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT PRODUCT_ID, BEST_PRICE_NEW, BEST_PRICE_USED, WORST_PRICE, PRICE_NB, PRICE_NB_NEW, PRICE_NB_USED, MERCHANT_NB, BEST_PRICE_SITE_ID FROM PRICE_SUMMARY WHERE GEOZONE_CODE = '.$sGeoZoneCode,
		'TRAVERSAL_KEY' => 'PRODUCT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['PRODUCT_SIMILAR'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
			SELECT ps.PRODUCT_ID, ps.SIMILAR_ID, ps.ATTRIB_MATCH, p.PRODUCT_REF as REF_NAME, p.CAT_ID, p.BRAND_ID, ps2.BEST_PRICE,up.SCORE
			FROM PRODUCT_SIMILAR ps
				JOIN PRODUCT p ON p.PRODUCT_ID = ps.SIMILAR_ID
				JOIN PRODUCT_PHOTO pp on pp.PRODUCT_ID =  ps.SIMILAR_ID
				JOIN PRICE_SUMMARY ps2 ON ps2.PRODUCT_ID = p.PRODUCT_ID
				LEFT JOIN '.$URL_PATH_TABLE.' up ON up.ITEM_ID = p.PRODUCT_ID AND up.TYPE = "P"
			WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode,
		'TRAVERSAL_KEY' => 'PRODUCT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['MOST_SEARCHED'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'select URL, RAW_KW, SCORE, ROOT_CAT_ID FROM '.$URL_PATH_TABLE.', CATEGORY '
					.'WHERE PAGE_TYPE IN (20,21,22)  AND '.$URL_PATH_TABLE.'.CAT_ID = CATEGORY.CAT_ID '
					.'AND IS_ADULT=0 ORDER BY SCORE DESC LIMIT 40',
		'ALLOW_MULTIPLE_KEY' => true
		);

		/*$aCacheConfig['MOST_SEARCHED'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT res2.URL, res2.RAW_KW, res2.SCORE, res2.ROOT_CAT_ID
			FROM (select ROOT_CAT_ID, MAX(SCORE) as MAX_SCORE from (select ROOT_CAT_ID, SCORE FROM '.$URL_PATH_TABLE.' WHERE PAGE_TYPE IN (20,21,22) ORDER BY SCORE DESC LIMIT 30)
			r1 group by ROOT_CAT_ID ORDER BY MAX_SCORE DESC) res1 inner join (select URL, RAW_KW, SCORE, ROOT_CAT_ID FROM '.$URL_PATH_TABLE.' WHERE PAGE_TYPE IN (20,21,22) ORDER BY SCORE DESC LIMIT 30) res2 on res1.ROOT_CAT_ID = res2.ROOT_CAT_ID ORDER BY MAX_SCORE DESC, SCORE DESC',
		'ALLOW_MULTIPLE_KEY' => true
		);*/

		$aCacheConfig['ITEM_VIDEO'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
			SELECT PRODUCT_VIDEO.VIDEO_ID, PRODUCT_VIDEO.ITEM_ID, PRODUCT_VIDEO.SOURCE_ID, PRODUCT_VIDEO.SOURCE_VIDEO_REF, PRODUCT_VIDEO.VIDEO_URL, PRODUCT_VIDEO.PREVIEW_URL, PRODUCT_VIDEO.TITLE, PRODUCT_VIDEO.DESCRIPTION, SEC_TO_TIME(PRODUCT_VIDEO.DURATION) as DURATION, PRODUCT_VIDEO.LANG_ID, PRODUCT_VIDEO.AUTHOR_NAME, PRODUCT_VIDEO.DISPLAY_NB, PRODUCT_VIDEO.NB_VOTE_POSITIVE, PRODUCT_VIDEO.NB_VOTE_NEGATIVE, PRODUCT_VIDEO.SCORE, PRODUCT_VIDEO.TS_PUBLISH, PRODUCT_VIDEO.PREVIEW_URL_SMALL, PRODUCT_VIDEO.TYPE
			FROM PRODUCT_VIDEO
			INNER JOIN PRODUCT ON PRODUCT.PRODUCT_ID = PRODUCT_VIDEO.ITEM_ID AND PRODUCT_VIDEO.TYPE = "P"
			INNER JOIN CATEGORY_ROOT ON CATEGORY_ROOT.CAT_ID = PRODUCT.CAT_ID
			INNER JOIN PRODUCT_VIDEO_ON_CATEGORY UNIVERSE_TO_ADD ON UNIVERSE_TO_ADD.CAT_ID = CATEGORY_ROOT.ROOT_CAT_ID
			LEFT JOIN PRODUCT_VIDEO_ON_CATEGORY CAT_TO_BYPASS ON CAT_TO_BYPASS.CAT_ID = CATEGORY_ROOT.CAT_ID
			WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.'
			AND PRODUCT_VIDEO.VIDEO_URL Not IN (SELECT VIDEO_URL FROM PRODUCT_VIDEO_BANNED)
			AND CAT_TO_BYPASS.CAT_ID Is Null
			ORDER BY PRODUCT_VIDEO.ITEM_ID, PRODUCT_VIDEO.SCORE DESC',
		'TRAVERSAL_KEY' => 'ITEM_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['BOOK_NB_MERCHANT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT BOOK_ID, MERCHANT_NB FROM BOOK_PRICE_SUMMARY WHERE MERCHANT_NB >= 6 AND  '.$sGeoZoneCode.'='.$sGeoZoneCode.'',
		'TRAVERSAL_KEY' => 'BOOK_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['MUSIC_NB_MERCHANT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT MUSIC_ID, MERCHANT_NB FROM MUSIC_PRICE_SUMMARY WHERE MERCHANT_NB >= 4 AND '.$sGeoZoneCode.'='.$sGeoZoneCode.'',
		'TRAVERSAL_KEY' => 'MUSIC_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['VIDEO_NB_MERCHANT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT VIDEO_ID, MERCHANT_NB FROM VIDEO_PRICE_SUMMARY WHERE MERCHANT_NB >= 4 AND '.$sGeoZoneCode.'='.$sGeoZoneCode.'',
		'TRAVERSAL_KEY' => 'VIDEO_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['BOOK_RECENT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
			SELECT  ID, REF,BEST_PRICE,CAT_ID,MERCHANT_NB, HAS_IMG,  @num
			FROM
			(
				SELECT ITEM_ID AS ID, REF,RECENT_RELEASE.BEST_PRICE,CAT_ID,MERCHANT_NB,1 as HAS_IMG
				FROM (SELECT @CAT_ID:=0) x, (SELECT @num:=0) xx,RECENT_RELEASE
				INNER JOIN BOOK_PRICE_SUMMARY ON RECENT_RELEASE.ITEM_ID = BOOK_PRICE_SUMMARY.BOOK_ID
				WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND TYPE = "B"
				ORDER BY CAT_ID,RANKING ASC
			) cl
			WHERE
			(@num := if(cl.CAT_ID = @CAT_ID, @num + 1, 1)) is not null
			AND (@CAT_ID := cl.CAT_ID) is not null
			AND (@num <= 25)
		',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['MUSIC_RECENT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT ITEM_ID AS ID, REF,RECENT_RELEASE.BEST_PRICE,CAT_ID,MERCHANT_NB,1 as HAS_IMG FROM RECENT_RELEASE INNER JOIN MUSIC_PRICE_SUMMARY ON RECENT_RELEASE.ITEM_ID = MUSIC_PRICE_SUMMARY.MUSIC_ID WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND TYPE = "M" ORDER BY CAT_ID,RANKING ASC ',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['VIDEO_RECENT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT ITEM_ID AS ID, REF,RECENT_RELEASE.BEST_PRICE,CAT_ID, MERCHANT_NB,1 as HAS_IMG FROM RECENT_RELEASE INNER JOIN VIDEO_PRICE_SUMMARY ON RECENT_RELEASE.ITEM_ID = VIDEO_PRICE_SUMMARY.VIDEO_ID WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND TYPE = "V" ORDER BY CAT_ID,RANKING ASC ',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['ATTRIB_CAT_NAME'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT ATTRIB_ID, CAT_ID, NAME, CAT_NAME_SYNONYM FROM ATTRIBUTE_CATEGORY_NAME WHERE GEOZONE_CODE = '.$sGeoZoneCode.' ORDER BY CAT_ID, ATTRIB_ID, CAT_NAME_SYNONYM',
		'TRAVERSAL_KEY' => array('CAT_ID', 'ATTRIB_ID'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['POLL_COMMENTS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT COMMENT, UNIX_TIMESTAMP(TS) AS TS, DISPLAY FROM POLL_CM
				WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND TRIM(COMMENT) <> "" AND STATUS=1 AND USEFULNESS="usefull"
				ORDER BY TS DESC',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['LOG_SEARCH_PHOTO'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT MD5(URL) AS MD5_URL, CAT_ID, URL_ID FROM LOG_SEARCH_PHOTO_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND STATUS IN (1,3,4) ORDER BY URL',
		'TRAVERSAL_KEY' => 'MD5_URL',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['LOG_SEARCH_PHOTO_KEY'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT URL_ID, URL, CAT_ID, STATUS FROM LOG_SEARCH_PHOTO_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND STATUS IN (1,3,4) AND URL_ID IS NOT NULL ORDER BY URL_ID',
		'TRAVERSAL_KEY' => 'URL_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);


		$aCacheConfig['AUTO_PATH_V2']  = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT URL, KW_ID, KW, PAGE_TYPE, SCORE, TYPE  FROM AUTO_PATH_V2_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode,
		'TRAVERSAL_KEY' => array('KW_ID', 'TYPE'),
		'ALLOW_MULTIPLE_KEY' => false
		);


		$aCacheConfig['CAT_PRICEDROPS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
			SELECT CAT_PID, PERCENT, ITEM_ID, TYPE, BEST_PRICE, INITIAL_PRICE, MASTERSITE_ID, SITE_ID, SITE_TYPE,  BRAND_ID, MAIN_CAT_ID,ROOT_CAT_ID, PRODUCT_REF, PRODUCT_DESIGNATION, @num
			FROM
			(
				SELECT CATEGORY_LEAF.CAT_ID AS CAT_PID, IF(SITE_TYPE = 4,1,0) AS AFFILIATE,(((BEST_PRICE - INITIAL_PRICE) * 100) / INITIAL_PRICE) AS PERCENT,
				ITEM_ID, TYPE, BEST_PRICE, INITIAL_PRICE, MASTERSITE_ID, SITE_ID, SITE_TYPE,  BRAND_ID, MAIN_CAT_ID,ROOT_CAT_ID, PRODUCT_REF, PRODUCT_DESIGNATION
				FROM (SELECT @CAT_PID:=0) x, (SELECT @num:=0) xx,BEST_PRICE_DROP_CM
				LEFT JOIN CATEGORY_LEAF ON MAIN_CAT_ID = CATEGORY_LEAF.LEAF_ID
				INNER JOIN PRICEBOUNDS_ON_CATEGORY_CM ON MAIN_CAT_ID = PRICEBOUNDS_ON_CATEGORY_CM.CAT_ID
				WHERE CATEGORY_LEAF.CAT_ID != 1 AND BEST_PRICE BETWEEN MIN_BOUND AND MAX_BOUND
				ORDER BY CATEGORY_LEAF.CAT_ID, AFFILIATE DESC, RANKING ASC, PERCENT DESC
			) cl
			WHERE
			(@num := if(cl.CAT_PID = @CAT_PID, @num + 1, 1)) is not null
			AND (@CAT_PID := cl.CAT_PID) is not null
			AND (@num <= 10)',
		'TRAVERSAL_KEY' => 'CAT_PID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['BRAND_PRICEDROPS_BY_CAT_ID'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT BRAND_ID, CATEGORY_LEAF.CAT_ID, (((BEST_PRICE - INITIAL_PRICE) * 100) / INITIAL_PRICE) AS PERCENT, ITEM_ID, TYPE, BEST_PRICE, INITIAL_PRICE, MASTERSITE_ID, SITE_ID, SITE_TYPE, MAIN_CAT_ID, ROOT_CAT_ID, PRODUCT_REF, PRODUCT_DESIGNATION
			FROM BEST_PRICE_DROP_CM
			LEFT JOIN CATEGORY_LEAF ON MAIN_CAT_ID = CATEGORY_LEAF.LEAF_ID
			INNER JOIN PRICEBOUNDS_ON_CATEGORY_CM ON MAIN_CAT_ID = PRICEBOUNDS_ON_CATEGORY_CM.CAT_ID
			WHERE BRAND_ID > 0 AND CATEGORY_LEAF.CAT_ID != 1 AND SITE_TYPE = 4 AND BEST_PRICE BETWEEN MIN_BOUND AND MAX_BOUND
			ORDER BY BRAND_ID, CATEGORY_LEAF.CAT_ID, RANKING ASC, PERCENT DESC',
		'TRAVERSAL_KEY' => array('BRAND_ID', 'CATEGORY_LEAF.CAT_ID'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['BRAND_CATLIST']  = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT CATEGORY_CHILD.CAT_ID AS PARENT_ID,CATEGORY_CHILD.CHILD_ID as CAT_ID FROM BRAND_SIZE_LEAF
			LEFT JOIN CATEGORY_CHILD ON BRAND_SIZE_LEAF.CAT_ID = CATEGORY_CHILD.CHILD_ID
			INNER JOIN CATEGORY_LEAF ON CATEGORY_CHILD.CHILD_ID = LEAF_ID
			WHERE CATEGORY_CHILD.CAT_ID > 1
			GROUP BY CATEGORY_CHILD.CAT_ID, CATEGORY_CHILD.CHILD_ID',
		'TRAVERSAL_KEY' => array('PARENT_ID'),
		'KEEP_KEY' => false,
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['CLICK_TRACKING_BRAND'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT ROOT_CAT_ID,BRAND_ID, MAIN_CAT_ID, TYPE, ITEM_ID, PRODUCT_DESIGNATION, BEST_PRICE, SITE_ID, SEO_INDEX,AFFILIATE_ID  FROM CLICK_TRACKING_BRAND_CONSO INNER JOIN MASTER_SITE USING(SITE_ID) LEFT JOIN MERCHANT USING(SITE_ID) WHERE  '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND TYPE = "T" ORDER BY ROOT_CAT_ID, BRAND_ID, SCORE DESC',
		'TRAVERSAL_KEY' => array('ROOT_CAT_ID', 'BRAND_ID'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['ITEM_POPULAR']  = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
			SELECT CAT_PID, CAT_ID, ROOT_CAT_ID, ITEM_ID, TYPE, BRAND_ID, RAW_KW, BEST_PRICE_NEW, BEST_PRICE_USED,SCORE, NB_OFFERS,IS_ADULT, SITE_ID, AFFILIATE_ID, MASTERSITE_ID, NB_CLICKS, @num
			FROM
			(
				SELECT CATEGORY_LEAF.CAT_ID AS CAT_PID, '.$URL_PATH_TABLE.'.CAT_ID, ROOT_CAT_ID, ITEM_ID, TYPE, BRAND_ID,RAW_KW,BEST_PRICE_NEW,BEST_PRICE_USED,SCORE,NB_OFFERS,0 AS IS_ADULT,0 AS SITE_ID,0 AS AFFILIATE_ID,0 AS MASTERSITE_ID,0 AS NB_CLICKS
				FROM (SELECT @CAT_PID:=0) x, (SELECT @num:=0) xx, '.$URL_PATH_TABLE.'
					INNER JOIN PRICE_SUMMARY ON PRICE_SUMMARY.PRODUCT_ID = ITEM_ID
					INNER JOIN CATEGORY_LEAF ON '.$URL_PATH_TABLE.'.CAT_ID = LEAF_ID
					LEFT JOIN PRODUCT_PHOTO ON PRODUCT_PHOTO.PRODUCT_ID = ITEM_ID
				WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND SCORE > 0 AND TRAFFIC > 0 AND PAGE_TYPE = 34 AND TS_EVICTION IS NULL
			UNION
			SELECT CATEGORY_LEAF.CAT_ID AS CAT_PID,	MAIN_CAT_ID as CAT_ID,ROOT_CAT_ID, ITEM_ID, TYPE, BRAND_ID,ITEM_DESIGNATION AS RAW_KW,BEST_PRICE AS BEST_PRICE_NEW,0 AS BEST_PRICE_USED,0 AS SCORE,1 AS NB_OFFERS,IS_ADULT,SITE_ID,AFFILIATE_ID,MASTERSITE_ID,NB_CLICKS
				FROM (SELECT @CAT_PID:=0) x, (SELECT @num:=0) xx, CLICK_TRACKING_SUMMARY'.$ext_shard.'
					LEFT JOIN MASTER_SITE USING(SITE_ID)
					INNER JOIN CATEGORY_LEAF ON MAIN_CAT_ID = LEAF_ID
				WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND TYPE = "T"
			ORDER BY CAT_PID,TYPE ASC,SCORE DESC, NB_CLICKS DESC
			) cl
			WHERE
			(@num := if(cl.CAT_PID = @CAT_PID, @num + 1, 1)) is not null
			AND (@CAT_PID := cl.CAT_PID) is not null
			AND (@num <= 100)',
		'TRAVERSAL_KEY'=> 'CAT_PID',
		'ALLOW_MULTIPLE_KEY' => true
		);


		$aCacheConfig['URL_PATH_TOP_BRAND'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
			SELECT CAT_PID, BRAND_ID, BRAND_NAME, HAS_LOGO,T_SCORE,  NB_PRODUCTS , @num
			FROM
			(
				SELECT CATEGORY_LEAF.CAT_ID AS CAT_PID,  BRAND_ID, BRAND_NAME, HAS_LOGO, SUM(SCORE) AS T_SCORE, SUM(NB_OFFERS) AS NB_PRODUCTS
				FROM (SELECT @CAT_PID:=0) x, (SELECT @num:=0) xx, '.$URL_PATH_TABLE.'
				INNER JOIN BRAND USING(BRAND_ID)
				INNER JOIN CATEGORY_LEAF ON '.$URL_PATH_TABLE.'.CAT_ID = LEAF_ID
				WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.'
					AND BRAND_ID > 0
				GROUP BY CATEGORY_LEAF.CAT_ID,BRAND_ID
				ORDER BY CAT_PID, T_SCORE DESC,NB_PRODUCTS DESC
			) cl
			WHERE
			(@num := if(cl.CAT_PID = @CAT_PID, @num + 1, 1)) is not null
			AND (@CAT_PID := cl.CAT_PID) is not null
			AND (@num <= 50)',
		'TRAVERSAL_KEY' => 'CAT_PID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['URL_PATH_TOP_KEYWORD'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
			SELECT SCORE, RAW_KW, CAT_ID, BRAND_ID, ATTRIB_VALUE_ID, ATTRIB_VALUE_ID_2, ROOT_CAT_ID, START, REFINE_QUERY
			FROM (
				SELECT SCORE, RAW_KW, CAT_ID, BRAND_ID, ATTRIB_VALUE_ID,ATTRIB_VALUE_ID_2, ROOT_CAT_ID, START, REFINE_QUERY, @num
				FROM (
					SELECT SCORE, RAW_KW, URL, '.$URL_PATH_TABLE.'.CAT_ID, '.$URL_PATH_TABLE.'.BRAND_ID, '.$URL_PATH_TABLE.'.ROOT_CAT_ID, '.$URL_PATH_TABLE.'.ATTRIB_VALUE_ID, '.$URL_PATH_TABLE.'.ATTRIB_VALUE_ID_2, START, REFINE_QUERY
					FROM (SELECT @ROOT_CAT_ID:=0) x, (SELECT @num:=0) xx, '.$URL_PATH_TABLE.'
					INNER JOIN CATEGORY_LEAF ON '.$URL_PATH_TABLE.'.CAT_ID = CATEGORY_LEAF.LEAF_ID
					LEFT JOIN CATEGORY_ROOT_BANNED USING(ROOT_CAT_ID)
					WHERE CATEGORY_ROOT_BANNED.ROOT_CAT_ID IS NULL
					AND  '.$sGeoZoneCode.'='.$sGeoZoneCode.'
					AND TS_EVICTION IS NULL
					AND START = 0
					AND PAGE_TYPE IN (21,22)
					GROUP BY LEAF_ID, '.$URL_PATH_TABLE.'.BRAND_ID, '.$URL_PATH_TABLE.'.ATTRIB_VALUE_ID
					ORDER BY '.$URL_PATH_TABLE.'.ROOT_CAT_ID, SCORE DESC, NB_OFFERS DESC
				) cl
				WHERE
				(@num := if(cl.ROOT_CAT_ID = @ROOT_CAT_ID, @num + 1, 1)) is not null
				AND (@ROOT_CAT_ID := cl.ROOT_CAT_ID) is not null
				AND (@num <= 10)
			) f
			ORDER BY f.SCORE DESC
			LIMIT 100',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['URL_PATH_TOP_CAT_WITH_PRODUCT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
			SELECT T1.CAT_ID,T1.RAW_KW,T1.URL_ID,T1.URL, T1.SCORE, SUM(T1.NB_OFFERS) AS T_OFFERS
			FROM '.$URL_PATH_TABLE.' T1
				LEFT JOIN CATEGORY_TOP_HIGHTECH USING(CAT_ID)
			WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND T1.PAGE_TYPE = 20 AND T1.ROOT_CAT_ID IN (8,29,10,100,158,1949) AND T1.TS_EVICTION IS NULL
			GROUP BY CAT_ID
			ORDER BY PUBLISHED DESC, SCORE DESC, T_OFFERS DESC LIMIT 8',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['URL_PATH_RECENT_PRODUCT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
			SELECT CAT_PID, CAT_ID, BRAND_ID,  RAW_KW, SCORE, TYPE, ITEM_ID, @num
			FROM
			(
				SELECT CATEGORY_LEAF.CAT_ID AS CAT_PID, '.$URL_PATH_TABLE.'.CAT_ID,'.$URL_PATH_TABLE.'.BRAND_ID, RAW_KW, SCORE, TYPE, ITEM_ID, @num
				FROM (SELECT @CAT_PID:=0) x, (SELECT @num:=0) xx, '.$URL_PATH_TABLE.'
				INNER JOIN CATEGORY_LEAF ON '.$URL_PATH_TABLE.'.CAT_ID = LEAF_ID
				INNER JOIN PRODUCT ON '.$URL_PATH_TABLE.'.ITEM_ID = PRODUCT_ID
				WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.'
					AND ITEM_ID > 0
					AND TO_DAYS(NOW())-TO_DAYS(TS)<30
					AND TS_EVICTION IS NULL
				ORDER BY CAT_PID, SCORE DESC , NB_OFFERS DESC
			) cl
			WHERE
			(@num := if(cl.CAT_PID = @CAT_PID, @num + 1, 1)) is not null
			AND (@CAT_PID := cl.CAT_PID) is not null
			AND (@num <= 20)',
		'TRAVERSAL_KEY' => 'CAT_PID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['URL_PATH_CLOSE_REFS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
			SELECT CAT_PID, BRAND_ID, CAT_ID, RAW_KW, SCORE, ITEM_ID, TYPE, BEST_PRICE_NEW, BEST_PRICE_USED ,@num
			FROM
			(
				SELECT CATEGORY_LEAF.CAT_ID AS CAT_PID, BRAND_ID, '.$URL_PATH_TABLE.'.CAT_ID, RAW_KW, SCORE, ITEM_ID, TYPE,BEST_PRICE_NEW, BEST_PRICE_USED
				FROM (SELECT @CAT_PID:=0) x, (SELECT @BRAND_ID:=0) xx, (SELECT @num:=0) xxx, '.$URL_PATH_TABLE.'
				INNER JOIN CATEGORY_LEAF ON '.$URL_PATH_TABLE.'.CAT_ID = LEAF_ID
				INNER JOIN PRICE_SUMMARY ON PRODUCT_ID = ITEM_ID
				WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.'
					AND CATEGORY_LEAF.CAT_ID != 1
					AND ITEM_ID > 0
					AND TS_EVICTION IS NULL
				ORDER BY CAT_PID, BRAND_ID, SCORE DESC, NB_OFFERS DESC
			) cl
			WHERE
			(@num := if(cl.CAT_PID = @CAT_PID && cl.BRAND_ID = @BRAND_ID , @num + 1, 1)) is not null
			AND (@CAT_PID := cl.CAT_PID) is not null AND (@BRAND_ID := cl.BRAND_ID) is not null
			AND (@num <= 15)',
		'TRAVERSAL_KEY' => array('CAT_PID','BRAND_ID'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['URL_PATH_SCORE_ITEMS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT ITEM_ID, TYPE, SCORE FROM '.$URL_PATH_TABLE.' WHERE ITEM_ID > 0 AND SCORE > 0 ORDER BY TYPE, ITEM_ID',
		'TRAVERSAL_KEY' => array('TYPE', 'ITEM_ID'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['URL_PATH_NEXT_PAGES'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT CAT_ID, BRAND_ID, ATTRIB_VALUE_ID, 0 AS ATTRIB_VALUE_ID_2, 0, \'-\' AS REFINE_QUERY, START FROM URL_PATH_NEXT_PAGES_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY CAT_ID, BRAND_ID, ATTRIB_VALUE_ID',
		'TRAVERSAL_KEY' => array('CAT_ID', 'BRAND_ID', 'ATTRIB_VALUE_ID', 'ATTRIB_VALUE_ID_2', '0', 'REFINE_QUERY'),
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['URL_PATH_SEARCH_BY_URLID']  = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT URL_ID, RAW_KW, SCORE, NB_OFFERS, ROOT_CAT_ID, TS_EVICTION,PAGE_TYPE FROM '.$URL_PATH_TABLE.' WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' ',
		'TRAVERSAL_KEY' => 'URL_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		// ACCURATES
		$aCacheConfig['URL_PATH_LINKING_1'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT FROM_URL_ID, TO_URL_ID, SEO_INDEX FROM URL_PATH_LINKING_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND PLACEMENT = 10 ORDER BY FROM_URL_ID, POSITION ASC ',
		'TRAVERSAL_KEY' => 'FROM_URL_ID',
		'KEEP_KEY' => false,
		'ALLOW_MULTIPLE_KEY' => true
		);

		// SEO_BOOSTER
		$aCacheConfig['URL_PATH_LINKING_2'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT FROM_URL_ID, TO_URL_ID, SEO_INDEX FROM URL_PATH_LINKING_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND PLACEMENT = 15 ORDER BY FROM_URL_ID, POSITION ASC ',
		'TRAVERSAL_KEY' => 'FROM_URL_ID',
		'KEEP_KEY' => false,
		'ALLOW_MULTIPLE_KEY' => true
		);

		// POPULAR
		$aCacheConfig['URL_PATH_LINKING_3'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT FROM_URL_ID, TO_URL_ID, SEO_INDEX FROM URL_PATH_LINKING_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND PLACEMENT = 5 ORDER BY FROM_URL_ID, POSITION ASC ',
		'TRAVERSAL_KEY' => 'FROM_URL_ID',
		'KEEP_KEY' => false,
		'ALLOW_MULTIPLE_KEY' => true
		);

		// NAV
		$aCacheConfig['URL_PATH_LINKING_4'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT FROM_URL_ID, TO_URL_ID, RAW_KW,SEO_INDEX,DEPTH FROM URL_PATH_LINKING_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND PLACEMENT = 1 ORDER BY FROM_URL_ID, POSITION ASC ',
		'TRAVERSAL_KEY' => 'FROM_URL_ID',
		'KEEP_KEY' => false,
		'ALLOW_MULTIPLE_KEY' => true
		);

		// RECENT CATS
		$aCacheConfig['URL_PATH_LINKING_5'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT FROM_URL_ID, TO_URL_ID, RAW_KW,SEO_INDEX FROM URL_PATH_LINKING_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND PLACEMENT = 7 ORDER BY FROM_URL_ID, POSITION ASC ',
		'TRAVERSAL_KEY' => 'FROM_URL_ID',
		'KEEP_KEY' => false,
		'ALLOW_MULTIPLE_KEY' => true
		);

		// POPULAR FILTER
		$aCacheConfig['URL_PATH_LINKING_6'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT FROM_URL_ID, TO_URL_ID,SEO_INDEX FROM URL_PATH_LINKING_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND PLACEMENT = 20 ORDER BY FROM_URL_ID, POSITION ASC ',
		'TRAVERSAL_KEY' => 'FROM_URL_ID',
		'KEEP_KEY' => false,
		'ALLOW_MULTIPLE_KEY' => true
		);

		// POPULAR BY BRAND
		$aCacheConfig['URL_PATH_LINKING_7'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT BRAND_ID, URL_ID AS TO_URL_ID, 1 AS SEO_INDEX FROM ' . $URL_PATH_TABLE . ' WHERE ' . $sGeoZoneCode . '=' . $sGeoZoneCode . ' AND PAGE_TYPE IN (22,51,52,53,54) AND BRAND_ID > 0 AND SCORE > 0  ORDER BY BRAND_ID, SCORE DESC, NB_OFFERS DESC' ,
		'TRAVERSAL_KEY' => 'BRAND_ID' ,
		'KEEP_KEY' => false ,
		'ALLOW_MULTIPLE_KEY' => true
		);

		// NEW NAV
		$aCacheConfig['URL_PATH_LINKING_8'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT FROM_URL_ID, TO_URL_ID, RAW_KW,SEO_INDEX,DEPTH FROM URL_PATH_LINKING_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND PLACEMENT = 2 ORDER BY FROM_URL_ID, POSITION ASC',
		'TRAVERSAL_KEY' => 'FROM_URL_ID',
		'KEEP_KEY' => false,
		'ALLOW_MULTIPLE_KEY' => true
		);

		// TOP SECTIONS
		$aCacheConfig['URL_PATH_LINKING_9'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT FROM_URL_ID, TO_URL_ID FROM URL_PATH_LINKING_CM WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND PLACEMENT = 6 ORDER BY FROM_URL_ID, POSITION ASC',
		'TRAVERSAL_KEY' => 'FROM_URL_ID',
		'KEEP_KEY' => false,
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['TW_FACEBOOK_STREAM'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT STREAM, FROM_NAME, LINK,LINK_TITLE,LINK_PICTURE,DATE FROM FACEBOOK_FEED_CM WHERE STREAM IS NOT NULL AND STREAM != "" AND '.$sGeoZoneCode.'='.$sGeoZoneCode.' ORDER BY ID ASC',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['BRAND_UNIVERSE_POPULAR_ITEMS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT  *
		FROM
		(
			SELECT ROOT_CAT_ID, ITEM_ID, TYPE, RAW_KW, URL, SCORE, NB_MERCHANTS
			FROM (SELECT @ROOT_CAT_ID:=0) x, (SELECT @num:=0) xx, '.$URL_PATH_TABLE.'
			WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND PAGE_TYPE = 34
			ORDER BY ROOT_CAT_ID, SCORE DESC
		) cl
		WHERE (@num := if(cl.ROOT_CAT_ID = @ROOT_CAT_ID, @num + 1, 1)) is not null
		AND (@ROOT_CAT_ID := cl.ROOT_CAT_ID) is not null AND (@num <= 10)',
		'TRAVERSAL_KEY' => 'ROOT_CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);


		$aCacheConfig['BRAND_UNIVERSE_POPULAR_CATEGORIES'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => 'SELECT  *
		FROM
		(
			SELECT ROOT_CAT_ID, CAT_ID, RAW_KW, URL, SCORE
			FROM (SELECT @ROOT_CAT_ID:=0) x, (SELECT @num:=0) xx, '.$URL_PATH_TABLE.'
			WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND PAGE_TYPE = 22
			AND TS_EVICTION IS NULL AND NB_OFFERS > 0
			ORDER BY ROOT_CAT_ID, SCORE DESC
		) cl
		WHERE (@num := if(cl.ROOT_CAT_ID = @ROOT_CAT_ID, @num + 1, 1)) is not null
		AND (@ROOT_CAT_ID := cl.ROOT_CAT_ID) is not null AND (@num <= 10)',
		'TRAVERSAL_KEY' => 'ROOT_CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['USER_FACEBOOK'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_L2,
		'QUERY' => '
			SELECT USER_FACEBOOK.USER_ID, USER_FACEBOOK.FACEBOOK_ID, USER_FACEBOOK.FACEBOOK_PROFILE
			FROM USER_FACEBOOK
			INNER JOIN USER USING(USER_ID)
			WHERE USER.GEOZONE_CODE = '.$sGeoZoneCode.'
			ORDER BY USER_FACEBOOK.USER_ID DESC',
		'ALLOW_MULTIPLE_KEY' => true
		);

		return ($aCacheConfig);
	}



	public function getAllGzGeozoneCacheConfig()
	{
		$aCacheConfig['BEST_PRICE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL_GLOBAL,
		'QUERY' => 'SELECT PRODUCT_ID, GEOZONE_CODE, BEST_PRICE_NEW FROM PRICE_SUMMARY WHERE BEST_PRICE_NEW IS NOT NULL',
		'TRAVERSAL_KEY' => array('GEOZONE_CODE','PRODUCT_ID'),
		'ALLOW_MULTIPLE_KEY' => true,
		'ALLOW_COMPRESS' => false
		);

		return ($aCacheConfig);
	}



	//Language and Geozone specific caches
	// Attention au partage entre les mï¿½mes lang_id! (fr, ch, be...)
	public function getLGzCacheConfig($sGeoZoneCode, $iLanguageId)
	{
		if((defined('DB_SHARD') && DB_SHARD > 0) || (defined('TWENGA_DB_SHARD') && TWENGA_DB_SHARD > 0))
		{
			$ext_shard = '_CONSO';
		}
		else
		{
			$ext_shard = '';
		}

		$URL_PATH_TABLE = 'URL_PATH_TMP_LOCAL';

		$aCacheConfig['MERCHANT_SHIPPING'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT SITE_PROFIL_SHIPPING_TYPE.SITE_PROFIL_ID AS SITEID, SHIPPING_TYPE_NAME, SHIPPING_TYPE.SHIPPING_TYPE_ID
					 FROM SITE_PROFIL_SHIPPING_TYPE
        			INNER JOIN SHIPPING_TYPE USING (SHIPPING_TYPE_ID)
        			INNER JOIN SHIPPING_TYPE_NAME USING (SHIPPING_TYPE_ID)
        			WHERE SHIPPING_TYPE_NAME.LANG_ID='.$iLanguageId.' ORDER BY SITE_PROFIL_SHIPPING_TYPE.SITE_PROFIL_ID,SHIPPING_TYPE_NAME'	,
		'TRAVERSAL_KEY' => 'SITEID',
		'ALLOW_MULTIPLE_KEY' => true,
//		'KEEP_KEY' => false
		);

		$aCacheConfig['MERCHANT_PAYMENT'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT SITE_PROFIL_PAYMENT.SITE_PROFIL_ID AS SITEID,PAYMENT_NAME, PAYMENT.PAYMENT_ID
					FROM SITE_PROFIL_PAYMENT
					INNER JOIN PAYMENT USING (PAYMENT_ID)
					INNER JOIN PAYMENT_NAME USING (PAYMENT_ID)
					WHERE  PAYMENT_NAME.LANG_ID = ' . $iLanguageId . ' ORDER BY SITE_PROFIL_PAYMENT.SITE_PROFIL_ID,PAYMENT_NAME',
		'TRAVERSAL_KEY' => 'SITEID',
		'ALLOW_MULTIPLE_KEY' => true,
//		'KEEP_KEY' => false
		);

		$aCacheConfig['MERCHANT_ORDERS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT SITE_PROFIL_ORDER_TYPE.SITE_PROFIL_ID AS SITEID, ORDER_TYPE_NAME, ORDER_TYPE.ORDER_TYPE_ID
					 FROM SITE_PROFIL_ORDER_TYPE
			 		INNER JOIN ORDER_TYPE USING(ORDER_TYPE_ID)
			 		INNER JOIN ORDER_TYPE_NAME USING(ORDER_TYPE_ID)
					WHERE ORDER_TYPE_NAME.LANG_ID='.$iLanguageId.' ORDER BY SITE_PROFIL_ORDER_TYPE.SITE_PROFIL_ID,ORDER_TYPE_NAME',
		'TRAVERSAL_KEY' => 'SITEID',
		'ALLOW_MULTIPLE_KEY' => true,
//		'KEEP_KEY' => false
		);

		$aCacheConfig['CATSYNONYMS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT CAT_ID,CAT_NAME FROM CATEGORY_NAME_SYNONYM WHERE LANG_ID='.$iLanguageId,
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true,
		'KEEP_KEY' => false
		);

		$aCacheConfig['CATEGORY_METAS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT CAT_ID,META_TITLE,META_DESCRIPTION,META_KEYWORDS FROM CATEGORY_META WHERE LANG_ID = '.$iLanguageId,
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true,
		'KEEP_KEY' => false
		);

		$aCacheConfig['BRANDS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT T1.BRAND_ID,T1.HAS_LOGO,T1.BRAND_NAME, IFNULL(T2.BRAND_NAME, T1.BRAND_NAME COLLATE utf8_general_ci) AS BRAND_NAME_TRANSLATED FROM BRAND T1 LEFT JOIN BRAND_TRANSLATE T2 ON T2.BRAND_ID = T1.BRAND_ID AND T2.DISPLAY = 1 AND T2.LANG_ID = '.$iLanguageId,
		'TRAVERSAL_KEY' => 'BRAND_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['CATNAMES'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT CAT_ID,CAT_NAME FROM CATEGORY_NAME WHERE LANG_ID='.$iLanguageId,
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['NAVALIAS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT CAT_ID,IF (LENGTH(CAT_ALIAS) > 0, CAT_ALIAS, CAT_NAME) AS CAT_ALIAS, CAT_NAME,PRICE_NB FROM NAV_ALIAS INNER JOIN CATEGORY_DISPLAY USING (CAT_ID,GEOZONE_CODE) INNER JOIN CATEGORY_NAME USING(CAT_ID) LEFT JOIN CATEGORY_SIZE USING (CAT_ID,GEOZONE_CODE) WHERE CATEGORY_NAME.LANG_ID = '.$iLanguageId.' AND CATEGORY_VISIBLE > 0 AND GEOZONE_CODE='.$sGeoZoneCode.' ORDER BY POSITION',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['CATEGORY_LIST'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT CATEGORY_NAME.CAT_ID,CHILD_ID,CAT_NAME, CAT_TYPE FROM CATEGORY_NAME INNER JOIN CATEGORY_DISPLAY USING(CAT_ID) INNER JOIN CATEGORY_CHILD USING (CAT_ID) INNER JOIN CATEGORY USING (CAT_ID) WHERE CATEGORY_NAME.LANG_ID='.$iLanguageId .' AND LENGTH(CAT_NAME)>0 AND GEOZONE_CODE='.$sGeoZoneCode.' ORDER BY CHILD_ID,DEPTH DESC',
		'TRAVERSAL_KEY' => 'CHILD_ID',
		'ALLOW_MULTIPLE_KEY' => true,
		'KEEP_KEY' => false
		);

		$aCacheConfig['CAT_ALIAS'] = array(
		'CACHE_TYPE'=>TWMC_LOCAL,
		'QUERY'=>'SELECT CATEGORY_SHORTCUT.CAT_ID, CATEGORY_SHORTCUT.CAT_PID, CAT_NAME, CAT_TYPE, KEYWORD_PRIORITY, PRICE_NB, MERCHANT_NB, CATEGORY_SHORTCUT.CAT_SEQ FROM CATEGORY_SHORTCUT JOIN CATEGORY USING (CAT_ID) INNER JOIN CATEGORY_NAME USING(CAT_ID) INNER JOIN CATEGORY_DISPLAY USING(CAT_ID) LEFT JOIN CATEGORY_SIZE ON CATEGORY.CAT_ID=CATEGORY_SIZE.CAT_ID AND CATEGORY_DISPLAY.GEOZONE_CODE=CATEGORY_SIZE.GEOZONE_CODE WHERE LENGTH(CAT_NAME)>0 AND CATEGORY_NAME.LANG_ID = '.$iLanguageId.' AND CATEGORY.CAT_ID > 1 AND CATEGORY_VISIBLE = 1 AND CATEGORY_DISPLAY.GEOZONE_CODE = '.$sGeoZoneCode.' ORDER BY CAT_PID, CAT_SEQ, CAT_NAME',
		'TRAVERSAL_KEY'=>'CATEGORY_SHORTCUT.CAT_PID',
		'ALLOW_MULTIPLE_KEY'=>true,
		'KEEP_KEY'=>false
		);

		$aCacheConfig['NAVBAR'] = array(
		'CACHE_TYPE'=>TWMC_LOCAL,
		'QUERY'=>'SELECT ROOT_CAT_ID, CAT_ID, BRAND_ID, ATTRIB_VALUE_ID, RAW_KW, SCORE, @num
			FROM (
				SELECT ROOT_CAT_ID, CAT_ID, BRAND_ID, ATTRIB_VALUE_ID, RAW_KW, SCORE
				FROM (SELECT @ROOT_CAT_ID:=0) x, (SELECT @num:=0) xx, '.$URL_PATH_TABLE.'
					inner join CATEGORY_DISPLAY using(CAT_ID)
				WHERE GEOZONE_CODE = '.$sGeoZoneCode.'
					AND TS_EVICTION IS NULL
					AND ((PAGE_TYPE IN (20,21,22) and CATEGORY_POPULAR = 0) or (PAGE_TYPE = 20 and CATEGORY_POPULAR = 1))
					ORDER BY ROOT_CAT_ID, CATEGORY_POPULAR DESC, SCORE DESC
			) cl
			WHERE
				(@num := if(cl.ROOT_CAT_ID = @ROOT_CAT_ID, @num + 1, 1)) is not null
				AND (@ROOT_CAT_ID := cl.ROOT_CAT_ID) is not null
				AND (@num <= 5)',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['REVIEW_SUMMARY'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT PRODUCT_ID, AVG_PRONOTE, CONSREVIEW_NB, CONSREVIEW_NB, PROREVIEW_NB  FROM REVIEW_SUMMARY WHERE LANG_ID='.$iLanguageId.' AND '.$sGeoZoneCode.'='.$sGeoZoneCode,
		'TRAVERSAL_KEY' => 'PRODUCT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['TOP_MERCHANT_REBATE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT MASTERSITE_ID, count(*) as NB_REBATE, s.SITE_NAME FROM SITE s INNER JOIN VOUCHER_CODE_CM r ON s.SITE_ID = r.MASTERSITE_ID WHERE LANG_ID = '.$iLanguageId.' AND s.GEOZONE_CODE='.$sGeoZoneCode.' AND (START_DATE < NOW() OR START_DATE = "0000-00-00") AND (END_DATE > NOW() OR END_DATE = "0000-00-00") AND VALIDATED = 1 AND EXCLUSIF = 0 GROUP BY  MASTERSITE_ID ORDER BY NB_REBATE DESC',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['VOUCHER_CODE_MERCHANT_MAIN_CAT'] = array(
        'CACHE_TYPE' => TWMC_LOCAL,
        'QUERY' => "SELECT CAT_ID, CAT_NAME, MASTERSITE_ID FROM VOUCHER_CODE_SITE_CM INNER JOIN CATEGORY_NAME ON CAT_ID = ROOT_CAT_ID WHERE ".$sGeoZoneCode." = ".$sGeoZoneCode." AND LANG_ID = ".$iLanguageId,
        'TRAVERSAL_KEY' => 'MASTERSITE_ID',
		'ALLOW_MULTIPLE_KEY' => true
        );

        $aCacheConfig['VOUCHER_CODE_CAT_LIST'] = array(
        'CACHE_TYPE' => TWMC_LOCAL,
        'QUERY' => "SELECT ROOT_CAT_ID, COUNT(VOUCHER_CODE_SITE_CM.MASTERSITE_ID) AS NB_SITES, CAT_NAME
					FROM VOUCHER_CODE_SITE_CM
					INNER JOIN CATEGORY_NAME ON ROOT_CAT_ID = CAT_ID
					INNER JOIN (
					    SELECT DISTINCT MASTERSITE_ID
					    FROM VOUCHER_CODE_CM
					    WHERE VALIDATED = 1
					    AND (START_DATE < NOW() OR START_DATE = \"0000-00-00\") AND (END_DATE > NOW() OR END_DATE = \"0000-00-00\")
					    AND EXCLUSIF = 0
					) V ON V.MASTERSITE_ID = VOUCHER_CODE_SITE_CM.MASTERSITE_ID
					WHERE LANG_ID = ".$iLanguageId."
					GROUP BY ROOT_CAT_ID
					ORDER BY NB_SITES DESC, CAT_NAME",
        'ALLOW_MULTIPLE_KEY' => true
        );

        $aCacheConfig['VOUCHER_CODE_UNIVERSE_INFO'] = array(
        'CACHE_TYPE' => TWMC_LOCAL,
        'QUERY' => "SELECT COUNT(*) AS NB_MERCHANTS, CAT_NAME, ROOT_CAT_ID
					FROM VOUCHER_CODE_SITE_CM
					INNER JOIN CATEGORY_NAME ON ROOT_CAT_ID = CAT_ID
					WHERE LANG_ID = ".$iLanguageId."
					AND MASTERSITE_ID IN (
					    SELECT DISTINCT MASTERSITE_ID
					    FROM VOUCHER_CODE_CM
						WHERE (START_DATE < NOW() OR START_DATE =  \"0000-00-00\") AND (END_DATE > NOW() OR END_DATE = \"0000-00-00\")
						AND VALIDATED = 1
						AND EXCLUSIF = 0
					)
					GROUP BY ROOT_CAT_ID",
        'TRAVERSAL_KEY' => 'ROOT_CAT_ID',
        'ALLOW_MULTIPLE_KEY' => true
        );

		$aCacheConfig['TOP_BRAND_MASTERSITE'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT MASTERSITE_ID, SITE_NAME, BRAND_ID, HAS_LOGO, LOGO_URL, PRODUCTS FROM DW_BRAND_MASTERSITE_STAT_CM INNER JOIN SITE ON SITE_ID = MASTERSITE_ID INNER JOIN MASTERSITE_INFO ON MASTERSITE_INFO.SITE_ID = DW_BRAND_MASTERSITE_STAT_CM.MASTERSITE_ID WHERE HAS_LOGO = 1 AND STORE_TYPE = 0 ORDER BY BRAND_ID, TRANSACTIONS DESC',
		'TRAVERSAL_KEY' => 'BRAND_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['TRACKERS_SCRIPTS'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT ttu.PAGE_URL,ts.SCRIPT,ttu.TS_START,ttu.TS_END FROM TRACKERS_TWENGA_URL ttu LEFT JOIN TRACKERS_SCRIPT ts USING(SCRIPT_ID) WHERE ttu.TS_START <= NOW() AND ttu.TS_END > NOW() AND ts.ENABLED = 1 AND ttu.ENABLED = 1 AND ttu.GEOZONE_CODE ='.$sGeoZoneCode.' AND ts.GEOZONE_CODE = '.$sGeoZoneCode,
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['LOWEST_CATS_NAME'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT c.CAT_ID, CONCAT_WS(\' \', n.CAT_NAME, n2.CAT_NAME) as CAT_NAME FROM CATEGORY c INNER JOIN CATEGORY_NAME n ON (c.CAT_ID = n.CAT_ID AND n.LANG_ID = '.$iLanguageId.') INNER JOIN CATEGORY_NAME n2 ON (c.CAT_PID = n2.CAT_ID AND n2.LANG_ID = '.$iLanguageId.') INNER JOIN CATEGORY_CHILD ch ON (c.CAT_ID = CHILD_ID AND ch.CAT_ID IN (37706, 59383, 59384)) WHERE KEYWORD_PRIORITY = 3',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => false
		);

		$aCacheConfig['BRAND_SIZE_LEAF'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT BRAND_ID, CAT_ID, PRICE_NB, BRAND_SIZE_LEAF.ITEM_ID, BRAND_SIZE_LEAF.TYPE, SUM(SCORE) AS T_SCORE, (IF(ISNULL('.$URL_PATH_TABLE.'.CAT_ID),0,1)) AS SEO_INDEX FROM BRAND_SIZE_LEAF LEFT JOIN '.$URL_PATH_TABLE.' USING(CAT_ID, BRAND_ID) WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND CAT_ID > 0 GROUP BY CAT_ID, BRAND_ID ORDER BY CAT_ID, BRAND_ID',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['ATTRIBUTE_SIZE_LEAF'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => 'SELECT ATTRIBUTE_SIZE.ATTRIB_ID, ATTRIB_VALUE_ID, CAT_ID, PRICE_NB, SUM(SCORE) AS T_SCORE, (IF(ISNULL('.$URL_PATH_TABLE.'.CAT_ID),0,1)) AS SEO_INDEX FROM ATTRIBUTE_SIZE LEFT JOIN '.$URL_PATH_TABLE.' USING(CAT_ID, ATTRIB_VALUE_ID) WHERE GEOZONE_CODE = '.$sGeoZoneCode.' GROUP BY CAT_ID, ATTRIB_VALUE_ID ORDER BY CAT_ID, PRICE_NB',
		'TRAVERSAL_KEY' => 'CAT_ID',
		'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['URL_PATH_HOME_BRAND'] = array(
		'CACHE_TYPE' => TWMC_LOCAL,
		'QUERY' => '
			SELECT T1.BRAND_ID, IFNULL(T2.BRAND_NAME, T1.BRAND_NAME COLLATE utf8_general_ci) AS BRAND_NAME, SUM(SCORE) AS TSCORE, HAS_LOGO
			FROM '.$URL_PATH_TABLE.'
			INNER JOIN BRAND T1 USING(BRAND_ID)
			LEFT JOIN BRAND_TRANSLATE T2 ON (T2.BRAND_ID = T1.BRAND_ID AND T2.DISPLAY = 1 AND T2.LANG_ID = '.$iLanguageId.')
			WHERE '.$sGeoZoneCode.'='.$sGeoZoneCode.' AND T1.BRAND_ID > 0 AND HAS_LOGO = 1
			GROUP BY T1.BRAND_ID
			ORDER BY TSCORE DESC LIMIT 9',
		'ALLOW_MULTIPLE_KEY' => true
		);

		return ($aCacheConfig);
	}



	/**
	 * requête mise en cache par la batch night Travel
	 *
	 * @param integer $iLanguageId identifiant de la langue
	 * @return array
	 */
	function getTravelGeozoneCacheConfig($sGeoZoneCode,$iLanguageId)
	{
		$aCacheConfig['HOTEL_INFOS'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select TW_HOTEL_ID AS HOTEL_ID, CITY_ID, REGION_ID, COUNTRY_ID, CONTINENT_ID, TW_HOTEL.NAME, TW_HOTEL.LATITUDE,
								TW_HOTEL.LONGITUDE, TW_HOTEL_ADDRESS.STREET, TW_HOTEL_ADDRESS.ZIPCODE, TW_HOTEL_ADDRESS.CITY,
								TW_HOTEL_INFO_ADD.STARS, TW_HOTEL_INFO_ADD.NB_ROOM_TOTAL as ROOM_NB, NB_PHOTO, TW_HOTEL.MIN_PRICE as BEST_MIN_RATE,
								LC_INFO_ADD.HOTEL_NB as REGION_HOTEL_NB
					from TW_HOTEL
						inner join LOCATION_SUMMARY LS on TW_HOTEL.LOCATION_ID = LS.CITY_ID
						inner join TW_HOTEL_INFO_ADD using(TW_HOTEL_ID)
						left join TW_HOTEL_ADDRESS using(TW_HOTEL_ID)
						left join LC_INFO_ADD on LC_INFO_ADD.LOCATION_ID = REGION_ID
					where STATUS_ID = 2',
			'TRAVERSAL_KEY' => 'HOTEL_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 6.5 s

		$aCacheConfig['HOTEL_SCORE'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select TW_HOTEL.TW_HOTEL_ID as HOTEL_ID, HI.RANKING, HI.CLICK_NB, HI.REVIEWS_DETAILED_NB as DETAILED_NB,
							count(REVIEW.REVIEW_ID) as REVIEW_NB, avg(REVIEW.REVIEW_SCORE) as REVIEW_SCORE,
							(avg(`REVIEW`.`REVIEW_USER_APPROVED`) * 100) AS `APPROVED`
						FROM TW_HOTEL
							inner join TW_HOTEL_INFO HI using(TW_HOTEL_ID)
							left join REVIEW on REVIEW.TW_HOTEL_ID = TW_HOTEL.TW_HOTEL_ID and REVIEW.REVIEW_ACTIVE = 1 and REVIEW.REVIEW_ID = REVIEW.REVIEW_MASTERID
						WHERE HI.LANG_ID = '.$iLanguageId.' and STATUS_ID = 2
						group by HOTEL_ID',
			'TRAVERSAL_KEY' => 'HOTEL_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 4.4 s / geozone

		$aCacheConfig['CONTENT_ENHANCER'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select CONTENT_ENHANCER_ID as ID, POSITION, PARAM_TYPE, TR_ID, PARAM, PARAM_SUP, VAR_TR
					from CONTENT_ENHANCER
						inner join CONTENT_ENHANCER_TR using(CONTENT_ENHANCER_ID)',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.04 s

		$aCacheConfig['CITY_INFOS'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select CITY_ID, REGION_ID, COUNTRY_ID, CONTINENT_ID, LS.HOTEL_NB, LC_GEOLOC.LATITUDE, LC_GEOLOC.LONGITUDE,
						LC_INFO_ADD.HOTEL_NB, LS.ISO_CODE
					from LOCATION_SUMMARY LS
						inner join LC_GEOLOC on CITY_ID = LC_GEOLOC.LOCATION_ID
						inner join LC_INFO_ADD on CITY_ID = LC_INFO_ADD.LOCATION_ID
					where LS.HOTEL_NB > 0',
			'TRAVERSAL_KEY' => 'CITY_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 1.2 s

		$aCacheConfig['CITY_SCORE'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select CITY_ID, SCORE, CLICK_NB from LOCATION_SUMMARY_GZ where GEOZONE_CODE = '.$sGeoZoneCode,
			'TRAVERSAL_KEY' => 'CITY_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.02 s / geozone

		$aCacheConfig['REGION_INFOS'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select REGION_ID, COUNTRY_ID, CONTINENT_ID, sum(HOTEL_NB) as HOTEL_NB
							from LOCATION_SUMMARY
							where HOTEL_NB > 0 and REGION_ID is not null
							group by REGION_ID',
			'TRAVERSAL_KEY' => 'REGION_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.07 s

		$aCacheConfig['COUNTRY_INFOS'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select COUNTRY_ID, CONTINENT_ID, sum(HOTEL_NB) as HOTEL_NB from LOCATION_SUMMARY where HOTEL_NB > 0 group by COUNTRY_ID',
			'TRAVERSAL_KEY' => 'COUNTRY_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.07 s

		$aCacheConfig['CONTINENT_INFOS'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select CONTINENT_ID, sum(HOTEL_NB) as HOTEL_NB from LOCATION_SUMMARY where HOTEL_NB > 0 group by CONTINENT_ID',
			'TRAVERSAL_KEY' => 'CONTINENT_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.07 s

		$aCacheConfig['ALL_CONTINENT'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select CONTINENT_ID, sum(HOTEL_NB) as HOTEL_NB from LOCATION_SUMMARY where HOTEL_NB > 0 group by CONTINENT_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['HOTEL_POPULARBYCITY'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select CITY_ID, COUNTRY_ID, HOTEL_ID, NAME, STARS, BEST_PRICE, RANKING, @num
						from
						(
							select CITY_ID, COUNTRY_ID, TW_HOTEL_ID as HOTEL_ID, TH.NAME, STARS, MIN_PRICE as BEST_PRICE, LATITUDE, LONGITUDE, THI.RANKING
							from (select @CITY_ID:=0) x, (select @num:=0) xx, TW_HOTEL TH
								inner join TW_HOTEL_INFO THI using (TW_HOTEL_ID)
								inner join TW_HOTEL_INFO_ADD using (TW_HOTEL_ID)
								inner join LOCATION_SUMMARY on LOCATION_ID = CITY_ID
							where LANG_ID = '.$iLanguageId.' and NB_PHOTO > 0 and TH.STATUS_ID = 2
							order by CITY_ID, THI.RANKING desc, STARS desc, BEST_PRICE asc
						) cl
						where (@num := if(cl.CITY_ID = @CITY_ID, @num + 1, 1)) is not null
						and (@CITY_ID := cl.CITY_ID) is not null and (@num <= 12)',
			'TRAVERSAL_KEY' => 'CITY_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 3 s / geozone

		$aCacheConfig['HOTEL_BY_CITY'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select LOCATION_ID as CITY_ID, TW_HOTEL_ID as HOTEL_ID, TW_HOTEL.NAME, STARS, COUNT(TW_HOTEL_PRICE.TW_HOTEL_ID) as NB_PARTNER
								from TW_HOTEL
									inner join TW_HOTEL_INFO_ADD using(TW_HOTEL_ID)
									left join TW_HOTEL_PRICE using(TW_HOTEL_ID)
								where STATUS_ID = 2
								group by TW_HOTEL.TW_HOTEL_ID
								order by CITY_ID desc, NAME asc',
			'TRAVERSAL_KEY' => 'CITY_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 3.4 s

		$aCacheConfig['CITY_BY_COUNTRY'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select CITY_ID, REGION_ID, COUNTRY_ID, LS.HOTEL_NB, LSG.SCORE
					from LOCATION_SUMMARY LS
						inner join LOCATION_SUMMARY_GZ LSG using(CITY_ID)
						inner join LC_INFO_ADD on LC_INFO_ADD.LOCATION_ID = LS.COUNTRY_ID
					where LSG.GEOZONE_CODE = '.$sGeoZoneCode.'
					order by COUNTRY_ID asc, LSG.SCORE desc, HOTEL_NB desc',
			'TRAVERSAL_KEY' => 'COUNTRY_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.3 s / geozone

		$aCacheConfig['REGION_BY_COUNTRY'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select REGION_ID, COUNTRY_ID, sum(LS.HOTEL_NB) as HOTEL_NB, CLICK_NB, CITY_NB
				from LOCATION_SUMMARY LS
					inner join LOCATION_SUMMARY_GZ LSG using(CITY_ID)
					inner join LC_INFO_ADD on LC_INFO_ADD.LOCATION_ID = LS.COUNTRY_ID
				where LSG.GEOZONE_CODE = '.$sGeoZoneCode.'
				group by REGION_ID
				order by COUNTRY_ID, HOTEL_NB desc',
			'TRAVERSAL_KEY' => 'COUNTRY_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.3 s / geozone


		$aCacheConfig['LOCATION_FILTER'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select LOCATION_FILTER_PAGE_ID as LOCFILTID, LOCATION_ID as LOC_ID, FACILITY_ID as FAC_ID
						FROM LOCATION_FILTER_PAGE
						WHERE ACTIVE = 1
						ORDER BY LOCATION_FILTER_PAGE_ID',
			'TRAVERSAL_KEY' => 'LOCFILTID',
			'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['FILTER_BY_LOCATION'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select LOCATION_ID as LOC_ID, FACILITY_ID as FAC_ID, LOCATION_FILTER_PAGE_ID as LOCFILTID
						FROM LOCATION_FILTER_PAGE
						WHERE ACTIVE = 1
						ORDER BY LOCATION_ID',
			'TRAVERSAL_KEY' => 'LOC_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);

		$aCacheConfig['AIRPORT_BY_CITY'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select CITY.LOCATION_ID as CITY_ID, AIRPORT.LOCATION_ID as AIRPORT_ID, HOTEL_NB
				from LOCATION AIRPORT
					inner join LC_INFO_ADD using(LOCATION_ID)
					inner join LOCATION CITY on CITY.LOCATION_ID = AIRPORT.PARENT_LOCATION_ID
				where AIRPORT.LC_TYPE_ID = 6 and AIRPORT.ACTIVE = 1 and HOTEL_NB >= 10
				order by CITY_ID, HOTEL_NB desc',
			'TRAVERSAL_KEY' => 'CITY_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.04 s

		$aCacheConfig['AIRPORT_BY_HOTEL'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select TW_HOTEL_ID as HOTEL_ID, LOCATION_ID as AIRPORT_ID, DISTANCE
				from TW_HOTEL_ZONE
				where LC_TYPE_ID = 6
				order by HOTEL_ID, DISTANCE',
			'TRAVERSAL_KEY' => 'HOTEL_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.04 s

		$aCacheConfig['AIRPORT_INFOS'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select AIRPORT.LOCATION_ID as AIRPORT_ID, CITY_ID, REGION_ID, COUNTRY_ID, CONTINENT_ID, LS.HOTEL_NB,
							CITY_GEOLOC.LATITUDE as CITY_LATITUDE, CITY_GEOLOC.LONGITUDE as CITY_LONGITUDE,
							LC_GEOLOC.LATITUDE, LC_GEOLOC.LONGITUDE, LC_INFO_ADD.HOTEL_NB, LS.ISO_CODE, max(DISTANCE) as DISTANCE_MAX
					from LOCATION AIRPORT
						inner join LOCATION_SUMMARY LS on CITY_ID = AIRPORT.PARENT_LOCATION_ID
						inner join LC_GEOLOC using(LOCATION_ID)
						inner join LC_INFO_ADD using(LOCATION_ID)
						inner join TW_HOTEL_ZONE using(LOCATION_ID)
						inner join LC_GEOLOC CITY_GEOLOC ON CITY_ID = CITY_GEOLOC.LOCATION_ID
					where AIRPORT.LC_TYPE_ID = 6 and AIRPORT.ACTIVE = 1
					group by AIRPORT.LOCATION_ID',
			'TRAVERSAL_KEY' => 'AIRPORT_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.2 s

		$aCacheConfig['POPULAR_COUNTRIES'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select COUNTRY_ID, CONTINENT_ID, sum(LS.HOTEL_NB) as HOTEL_NB, CLICK_NB, count(*) AS CITY_NB
						from LOCATION_SUMMARY LS
							inner join LOCATION_SUMMARY_GZ LSG using(CITY_ID)
							inner join LC_INFO_ADD on LC_INFO_ADD.LOCATION_ID = LS.COUNTRY_ID
						where LSG.GEOZONE_CODE = '.$sGeoZoneCode.'
						group by COUNTRY_ID
						order by CONTINENT_ID desc, CLICK_NB desc',
			'TRAVERSAL_KEY' => 'CONTINENT_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.3 s / geozone

		$aCacheConfig['HOTEL_FACILITIES'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select TW_HOTEL_ID as HOTEL_ID, FACILITY_ID
							from TW_HOTEL_FACILITY
								inner join TW_HOTEL using(TW_HOTEL_ID)
							where STATUS_ID = 2',
			'TRAVERSAL_KEY' => 'HOTEL_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 2.3 s

		$aCacheConfig['HOTEL_IMAGES'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select TW_HOTEL_ID as HOTEL_ID, ITEM_ID
							from TW_HOTEL_PHOTO
								inner join PHOTO_STATUS_V2 on ITEM_ID = TW_HOTEL_PHOTO_ID
							where TYPE = "H" and ACTIVE = 1
							order by TW_HOTEL_ID',
			'TRAVERSAL_KEY' => 'HOTEL_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 14 s

		$aCacheConfig['REVIEW_IMAGES'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select R.TW_HOTEL_ID as HOTEL_ID, RP.REVIEW_PHOTO_MASTERID as ITEM_ID, TYPE, RP.REVIEW_PHOTO_TITLE as TITLE,
								RP.`USER_ID` as USER_ID, R.REVIEW_MASTERID
							from REVIEW_PHOTO RP
								inner join PHOTO_STATUS_V2 on ITEM_ID = REVIEW_PHOTO_MASTERID
								inner join REVIEW R using(REVIEW_ID)
							where RP.REVIEW_PHOTO_STATUS in ("AUTO_VALID", "MANUAL_VALID") and (TYPE = "R" or TYPE = "Q") and
								RP.GEOZONE_CODE = '.$sGeoZoneCode.' and (ITEM_TYPE_ID = -1 or ITEM_TYPE_ID = 12) and
								RP.REVIEW_PHOTO_ID != RP.REVIEW_PHOTO_MASTERID
							order by R.TW_HOTEL_ID, TYPE',
			'TRAVERSAL_KEY' => 'HOTEL_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.03 s / geozone

		$aCacheConfig['REVIEW_COMPOSITION'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select RPT.REVIEW_SECTION_TYPE_ID as ID,
								RPT.REVIEW_SECTION_TYPE_ORDER as `ORDER`,
								RPT.REVIEW_SECTION_TYPE_CODE as CODE,
								(RPT.REVIEW_SECTION_TYPE_MANDATORY+0) as MANDATORY,
								RPT.FORM_LOCALE_ID as LOCALE_ID,
								RPT.ERROR_LOCALE_ID,
								RAT.REVIEW_ATTRIBUTE_TYPE_ID as ATTR_ID,
								RAT.REVIEW_ATTRIBUTE_TYPE_PARAM as ATTR_PARAM,
								RAT.REVIEW_ATTRIBUTE_TYPE_NAME as ATTR_NAME,
								RAT.LOCALE_ID as ATTR_LOCALE_ID,
								RAT.REVIEW_ATTRIBUTE_TYPE_ORDER as ATTR_ORDER,
								(RAT.REVIEW_ATTRIBUTE_TYPE_MANDATORY+0) as ATTR_MANDATORY,
								RD.REVIEW_DATATYPE_CODE as ATTR_CODE
							from REVIEW_SECTION_TYPE RPT
								inner join REVIEW_SECTION_ATTRIBUTE_TYPE RPAT on RPT.REVIEW_SECTION_TYPE_ID = RPAT.REVIEW_SECTION_TYPE_ID
								inner join REVIEW_ATTRIBUTE_TYPE RAT on RAT.REVIEW_ATTRIBUTE_TYPE_ID = RPAT.REVIEW_ATTRIBUTE_TYPE_ID
								inner join REVIEW_DATATYPE RD on RAT.REVIEW_DATATYPE_ID = RD.REVIEW_DATATYPE_ID
							where RAT.REVIEW_ATTRIBUTE_TYPE_ACTIVE = true
							order by RPT.REVIEW_SECTION_TYPE_ORDER asc, RAT.REVIEW_ATTRIBUTE_TYPE_ORDER asc',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0 s

		$aCacheConfig['REVIEW_ATT_SUMMARY'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select RAT.REVIEW_ATTRIBUTE_TYPE_NAME as CODE, SUM(RA.REVIEW_ATTRIBUTE_NUMERIC_VALUE) as NB, COUNT(RA.REVIEW_ATTRIBUTE_ID) as NBATT,
								RAD.REVIEW_DATATYPE_CODE as TYPE, RAT.LOCALE_ID as TRAD_ID, R.TW_HOTEL_ID as HOTEL_ID
							from REVIEW_ATTRIBUTE_TYPE RAT
								left join REVIEW_ATTRIBUTE RA on RAT.REVIEW_ATTRIBUTE_TYPE_ID = RA.REVIEW_ATTRIBUTE_TYPE_ID
								inner join REVIEW_DATATYPE RAD using(REVIEW_DATATYPE_ID)
								inner join REVIEW R using(REVIEW_ID)
							where RA.REVIEW_ID != R.REVIEW_MASTERID
							group by REVIEW_ATTRIBUTE_TYPE_NAME, R.TW_HOTEL_ID
							order by R.TW_HOTEL_ID, RAT.REVIEW_ATTRIBUTE_TYPE_ID',
			'TRAVERSAL_KEY' => 'HOTEL_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 7.5 s


		$aCacheConfig['REVIEW_BY_USER'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select R.USER_ID, R.TW_HOTEL_ID AS HOTEL_ID, R.REVIEW_ID
							from REVIEW R
							left join
							(select count(R.REVIEW_ID) as NBREVBYMASTER, R.REVIEW_MASTERID, RM.REVIEW_ACTIVE AS REVIEW_MASTERACTIVE, R.TW_HOTEL_ID
								from REVIEW R
									inner join REVIEW RM on RM.REVIEW_ID = R.REVIEW_MASTERID
								where R.REVIEW_ID != R.REVIEW_MASTERID and R.REVIEW_ACTIVE and
										( R.GEOZONE_CODE = '.$sGeoZoneCode.' or R.GEOZONE_CODE = R.GEOZONE_CODE_ORIGIN )
								group by REVIEW_MASTERID
							) RBYMGEO on R.REVIEW_MASTERID = RBYMGEO.REVIEW_MASTERID and R.TW_HOTEL_ID = RBYMGEO.TW_HOTEL_ID
							left join PARTNER P using(USER_ID)
							where R.REVIEW_MASTERID != R.REVIEW_ID and P.USER_ID is null and
								(R.GEOZONE_CODE = '.$sGeoZoneCode.' or (R.GEOZONE_CODE = GEOZONE_CODE_ORIGIN and RBYMGEO.NBREVBYMASTER > 1)) and
								REVIEW_ACTIVE = true and RBYMGEO.REVIEW_MASTERACTIVE
							order by R.USER_ID, R.TW_HOTEL_ID',
			'TRAVERSAL_KEY' => 'USER_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 3.8 s / geozone

		$aCacheConfig['REVIEW_HOTEL_CUR_GEOZONE'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select R.TW_HOTEL_ID AS HOTEL_ID, R.REVIEW_ID
							from REVIEW R
								left join
								(select count(R.REVIEW_ID) as NBREVBYMASTER, R.REVIEW_MASTERID, RM.REVIEW_ACTIVE AS REVIEW_MASTERACTIVE, R.TW_HOTEL_ID
									from REVIEW R
										inner join REVIEW RM on RM.REVIEW_ID = R.REVIEW_MASTERID
									where R.REVIEW_ID != R.REVIEW_MASTERID and R.REVIEW_ACTIVE
									and R.GEOZONE_CODE = '.$sGeoZoneCode.'
									group by REVIEW_MASTERID
								) RBYMGEO on R.REVIEW_MASTERID = RBYMGEO.REVIEW_MASTERID and R.TW_HOTEL_ID = RBYMGEO.TW_HOTEL_ID
							where R.REVIEW_MASTERID != R.REVIEW_ID and R.GEOZONE_CODE = '.$sGeoZoneCode.' and REVIEW_ACTIVE = true and RBYMGEO.REVIEW_MASTERACTIVE
							order by R.TW_HOTEL_ID',
			'TRAVERSAL_KEY' => 'HOTEL_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.9 s / geozone


		$aCacheConfig['HOTEL_PRICES'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select TW_HOTEL_ID as HOTEL_ID, (TW_HOTEL_PRICE.MIN_PRICE/RATE) as PRICE, TW_HOTEL_PRICE.PARTNER_ID, URL as PARTNER_URL
							from TW_HOTEL_PRICE
								inner join TW_HOTEL using(TW_HOTEL_ID)
								left join CURRENCY using(CURRENCY_CODE)
							where STATUS_ID = 2
							order by HOTEL_ID asc, (if(PRICE is null, 1, 0)), PRICE asc',
			'TRAVERSAL_KEY' => 'HOTEL_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 7.4 s

		$aCacheConfig['FACILITY'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select F1.FACILITY_ID, PARENT_FACILITY_ID as PARENT_ID, F2.FACILITY_TYPE_ID as PARENT_TYPE, F2.FACILITY_ORDER as FC_ORDER
							from FACILITY F1
								inner join FACILITY_ASSOC using(FACILITY_ID)
								inner join FACILITY F2 on F2.FACILITY_ID = FACILITY_ASSOC.PARENT_FACILITY_ID
							where F1.IS_DISPLAY = 1
							order by FACILITY_ID, PARENT_TYPE',
			'TRAVERSAL_KEY' => array('FACILITY_ID', 'PARENT_TYPE'),
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.01 s

		$aCacheConfig['FACILITY_PARENT'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select FACILITY_ID, FACILITY_TYPE_ID as TYPE, FACILITY_ORDER as FC_ORDER
							from FACILITY
							where FACILITY_TYPE_ID in (2,3)
							order by TYPE',
			'TRAVERSAL_KEY' => array('TYPE'),
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0 s

		$aCacheConfig['FACILITY_OTHER'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select distinct F2.FACILITY_ID, F1.FACILITY_ID as OTHER_ID
							from FACILITY F1
								inner join FACILITY_ASSOC using(FACILITY_ID)
								inner join FACILITY F2 on F2.FACILITY_ID = FACILITY_ASSOC.PARENT_FACILITY_ID
							where F1.FACILITY_TYPE_ID = 1 and F1.IS_DISPLAY = 1 and F2.FACILITY_TYPE_ID = 1
							order by F2.FACILITY_ID',
			'TRAVERSAL_KEY' => 'FACILITY_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0 s

		$aCacheConfig['FACILITY_TRANS'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select FACILITY_ID, FACILITY_TRANS.NAME, FACILITY_ORDER as FC_ORDER
							from FACILITY
								inner join FACILITY_TRANS using(FACILITY_ID)
							where GEOZONE_CODE = '.$sGeoZoneCode.'
							order by FACILITY_ID',
			'TRAVERSAL_KEY' => 'FACILITY_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.01 s

		$aCacheConfig['HOTEL_PARTNERS'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select PARTNER_ID, PARTNER_NAME, PARTNER_CODE, URL as PARTNER_URL, URL_LOGO from PARTNER',
			'TRAVERSAL_KEY' => 'PARTNER_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0 s

		$aCacheConfig['HOTEL_PARTNERS_USER_ID'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select USER_ID from PARTNER where USER_ID > 0',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0 s

		$aCacheConfig['SUMMARY_TRAVEL'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select
						(select count(*) from TW_HOTEL where STATUS_ID = 2) as HOTEL_NB,
						(select count(*) from LOCATION inner join LC_INFO_ADD using(LOCATION_ID) where HOTEL_NB > 0 and LC_TYPE_ID = 1) as CITY_NB',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.1 s

		$aCacheConfig['PRICE_RANGE'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select min(MIN_PRICE) as MIN, max(MIN_PRICE) as MAX from TW_HOTEL',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0 s

		$aCacheConfig['CURRENCY'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select CURRENCY_CODE, RATE from CURRENCY',
			'TRAVERSAL_KEY' => 'CURRENCY_CODE',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0 s

		$aCacheConfig['TOP_HOTELS'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select HOTEL_ID, RANKING, GEOZONE_CODE, @num
						from
						(
							select H.TW_HOTEL_ID as HOTEL_ID, HI.RANKING, GEOZONE_CODE
							from (select @GEOZONE_CODE:=0) x, (select @num:=0) xx, TW_HOTEL H
								inner join TW_HOTEL_INFO HI using(TW_HOTEL_ID)
								inner join LANG using(LANG_ID)
							where H.STATUS_ID = 2 and MIN_PRICE is not null and NB_PHOTO > 0
							order by GEOZONE_CODE, HI.RANKING desc
						) cl
						where (@num := if(GEOZONE_CODE = @GEOZONE_CODE, @num + 1, 1)) is not null
						and (@GEOZONE_CODE := GEOZONE_CODE) is not null and (@num <= 100)',
			'TRAVERSAL_KEY' => 'GEOZONE_CODE',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 11 s

		$aCacheConfig['TOP_CITIES'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select LOCATION_ID, GEOZONE_CODE, @num
						from
						(
							select CITY_ID AS LOCATION_ID, GEOZONE_CODE
							from (select @GEOZONE_CODE:=0) x, (select @num:=0) xx, LOCATION_SUMMARY_GZ LSG
								inner join LOCATION_SUMMARY LS USING(CITY_ID)
							where HOTEL_NB > 20
							order by GEOZONE_CODE, LSG.SCORE DESC, LSG.CLICK_NB desc
						) cl
						where (@num := if(GEOZONE_CODE = @GEOZONE_CODE, @num + 1, 1)) is not null
						and (@GEOZONE_CODE := GEOZONE_CODE) is not null and (@num <= 11)',
			'TRAVERSAL_KEY' => 'GEOZONE_CODE',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.2 s

		$aCacheConfig['TOP_CITIES_CONTINENT'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select LOCATION_ID, GEOZONE_CODE, @num
						from
						(
							select CITY_ID as LOCATION_ID, GEOZONE_CODE
							from (select @GEOZONE_CODE:=0) x, (select @num:=0) xx, LOCATION_SUMMARY_GZ LSG
								inner join LOCATION_SUMMARY LS USING(CITY_ID)
								inner join LANG_LOCATION on LANG_LOCATION.CONTINENT_ID = LS.CONTINENT_ID
								inner join LANG using(LANG_ID, GEOZONE_CODE)
							where HOTEL_NB > 10
							order by GEOZONE_CODE, LSG.SCORE desc, LSG.CLICK_NB desc
						) cl
						where (@num := if(GEOZONE_CODE = @GEOZONE_CODE, @num + 1, 1)) is not null
						and (@GEOZONE_CODE := GEOZONE_CODE) is not null and (@num <= 13)',
			'TRAVERSAL_KEY' => 'GEOZONE_CODE',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.4 s

		$aCacheConfig['TOP_CITIES_COUNTRY'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select LOCATION_ID, GEOZONE_CODE, @num
						from
						(
							select CITY_ID AS LOCATION_ID, GEOZONE_CODE
							from (select @GEOZONE_CODE:=0) x, (select @num:=0) xx, LOCATION_SUMMARY_GZ LSG
								inner join LOCATION_SUMMARY LS USING(CITY_ID)
								inner join LANG_LOCATION on LANG_LOCATION.COUNTRY_ID = LS.COUNTRY_ID
								inner join LANG using(LANG_ID, GEOZONE_CODE)
							where HOTEL_NB > 5
							order by GEOZONE_CODE, LSG.SCORE desc, LSG.CLICK_NB desc
						) cl
						where (@num := if(GEOZONE_CODE = @GEOZONE_CODE, @num + 1, 1)) is not null
						and (@GEOZONE_CODE := GEOZONE_CODE) is not null and (@num <= 21)',
			'TRAVERSAL_KEY' => 'GEOZONE_CODE',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.06 s

		$aCacheConfig['LOCATION_TRANS'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select NAME, LOCATION_ID, LC_TYPE_ID, LANG_ID
					from (
						(select LOCATION_TRANS.NAME, LOCATION_ID, LC_TYPE_ID, LOCATION_TRANS.LC_NAME_SOURCE_ID, LOCATION_TRANS.LANG_ID
						from LOCATION
							inner join LOCATION_TRANS using(LOCATION_ID)
							inner join LC_INFO_ADD using(LOCATION_ID)
						where LOCATION_TYPE = "name" and (LOCATION_TRANS.LANG_ID = '.$iLanguageId.' or LOCATION_TRANS.LANG_ID = 1) and HOTEL_NB > 0
						)
					union
						(select LOCATION_TRANS.NAME, LOCATION_ID, LC_TYPE_ID, LOCATION_TRANS.LC_NAME_SOURCE_ID, LOCATION_TRANS.LANG_ID
						from LOCATION
							inner join LOCATION_TRANS using(LOCATION_ID)
						where LOCATION_TYPE = "name" and (LOCATION_TRANS.LANG_ID = '.$iLanguageId.' or LOCATION_TRANS.LANG_ID = 1) and LC_TYPE_ID > 5
						)
					order by LOCATION_ID, LANG_ID desc, LC_NAME_SOURCE_ID desc) cl
					group by LOCATION_ID;',
			'TRAVERSAL_KEY' => array('LOCATION_ID','LC_TYPE_ID'),
			'ALLOW_MULTIPLE_KEY' => true,
		);// TIME : 2.2 s / geozone

		$aCacheConfig['TRAVEL_NEXT_PAGES'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select CITY_ID, START from MAPPING_NEXT_PAGES order by CITY_ID',
			'TRAVERSAL_KEY' => 'CITY_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0 s

		$aCacheConfig['EXTERNAL_LINK'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select LOCATION_ID, URL, TR_ID, NAME
							from LC_EXTERNAL_LINK
								left join LC_EXTERNAL_LINK_INFO using(LC_EXTERNAL_LINK_ID)
							where (LC_EXTERNAL_LINK_INFO_ID is null or LANG_ID = '.$iLanguageId.') order by LOCATION_ID',
			'TRAVERSAL_KEY' => 'LOCATION_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 0.01 s / geozone

		$aCacheConfig['HOTEL_AROUND'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select TW_HOTEL_ID as HOTEL_ID, ITEM_ID, DISTANCE
							from TW_HOTEL_AROUND_HOTEL
							where ITEM_TYPE_ID = -1
							order by HOTEL_ID, DISTANCE',
			'TRAVERSAL_KEY' => 'HOTEL_ID',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME : 2 s

		$aCacheConfig['LAST_REVIEWS'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select REVIEW_ID, HOTEL_ID, DATE, USER_ID, GEOZONE_CODE, @num
						from
						(
							select REVIEW_ID, TW_HOTEL_ID as HOTEL_ID, REVIEW_TS_CREATE as DATE, USER_ID, GEOZONE_CODE
							from (select @GEOZONE_CODE:=0) x, (select @num:=0) xx,
							((
								select REVIEW_ID, TW_HOTEL_ID, REVIEW_TS_CREATE, USER_ID, GEOZONE_CODE
								from REVIEW
								where GEOZONE_CODE_ORIGIN = GEOZONE_CODE and REVIEW_ACTIVE = 1 and REVIEW_ID != REVIEW_MASTERID
									and REVIEW_TS_CREATE > date_sub(now(), interval 15 day)
								order by REVIEW_TS_CREATE desc
							)union(
								select REVIEW_ID, TW_HOTEL_ID, REVIEW_TS_CREATE, USER_ID, GEOZONE_CODE
								from (select REVIEW_ID, TW_HOTEL_ID, REVIEW_TS_CREATE, USER_ID, GEOZONE_CODE, @num_tmp
										from
										(
											select REVIEW_ID, TW_HOTEL_ID, REVIEW_TS_CREATE, USER_ID, GEOZONE_CODE
											from REVIEW, (select @GEOZONE_CODE:=0) x, (select @num_tmp:=0) xx
											where GEOZONE_CODE_ORIGIN != GEOZONE_CODE and REVIEW_ACTIVE = 1 order by GEOZONE_CODE, REVIEW_TS_CREATE desc
										) cl_tmp
										where (@num_tmp := if(GEOZONE_CODE = @GEOZONE_CODE, @num_tmp + 1, 1)) is not null
										and (@GEOZONE_CODE := GEOZONE_CODE) is not null and (@num_tmp <= 5)) as tmp2
								order by rand()
							))
							as tmp
							order by GEOZONE_CODE, DATE desc
						) cl
						where (@num := if(GEOZONE_CODE = @GEOZONE_CODE, @num + 1, 1)) is not null
						and (@GEOZONE_CODE := GEOZONE_CODE) is not null and (@num <= 5)',
			'TRAVERSAL_KEY' => 'GEOZONE_CODE',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME 1.5 s

		$aCacheConfig['PROMO'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select TITLE, PRICE, LINK, GEOZONE_CODE, @num
						from
						(
							select TITLE, PHOTO_URL, PRICE, LINK, GEOZONE_CODE
							from (select @GEOZONE_CODE:=0) x, (select @num:=0) xx, PROMO
								inner join LANG using(LANG_ID)
							order by GEOZONE_CODE, TS_CREATE desc
						) cl
						where (@num := if(GEOZONE_CODE = @GEOZONE_CODE, @num + 1, 1)) is not null
						and (@GEOZONE_CODE := GEOZONE_CODE) is not null and (@num <= 6)',
			'TRAVERSAL_KEY' => 'GEOZONE_CODE',
			'ALLOW_MULTIPLE_KEY' => true
		);// TIME 0 s

		$aCacheConfig['URL_REDIRECT'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select TW_HOTEL_ID, PARTNER_ID, TW_HOTEL_PRICE.URL, TW_HOTEL_PRICE.MIN_PRICE, PARTNER_CODE
						from TW_HOTEL_PRICE
							inner join PARTNER using(PARTNER_ID)',
			'TRAVERSAL_KEY' => array('TW_HOTEL_ID','PARTNER_ID'),
			'ALLOW_MULTIPLE_KEY' => true,
		);// TIME 2 s


		$aCacheConfig['TRAVEL_AUTO_PATH'] = array(
			'CACHE_TYPE' => TWMC_TRAVEL,
			'QUERY' => 'select KEYWORD_CODE as KW, GEOZONE_CODE, LOCATION_ID as LOC_ID from AUTO_PATH order by KW, GEOZONE_CODE',
			'TRAVERSAL_KEY' => array('KW', 'GEOZONE_CODE'),
			'ALLOW_MULTIPLE_KEY' => true,
		);// TIME 3 s

		$aCacheConfig['CITY_SCORE'.$iLanguageId]				= $aCacheConfig['CITY_SCORE'];
		$aCacheConfig['HOTEL_SCORE'.$iLanguageId]				= $aCacheConfig['HOTEL_SCORE'];
		$aCacheConfig['HOTEL_POPULARBYCITY'.$iLanguageId]		= $aCacheConfig['HOTEL_POPULARBYCITY'];
		$aCacheConfig['CITY_BY_COUNTRY'.$iLanguageId]			= $aCacheConfig['CITY_BY_COUNTRY'];
		$aCacheConfig['REGION_BY_COUNTRY'.$iLanguageId]			= $aCacheConfig['REGION_BY_COUNTRY'];
		$aCacheConfig['POPULAR_COUNTRIES'.$iLanguageId]			= $aCacheConfig['POPULAR_COUNTRIES'];
		$aCacheConfig['LOCATION_TRANS'.$iLanguageId]			= $aCacheConfig['LOCATION_TRANS'];
		$aCacheConfig['EXTERNAL_LINK'.$iLanguageId]				= $aCacheConfig['EXTERNAL_LINK'];
		$aCacheConfig['REVIEW_IMAGES'.$iLanguageId]				= $aCacheConfig['REVIEW_IMAGES'];
		$aCacheConfig['FACILITY_TRANS'.$iLanguageId]			= $aCacheConfig['FACILITY_TRANS'];
		$aCacheConfig['REVIEW_HOTEL_CUR_GEOZONE'.$iLanguageId]	= $aCacheConfig['REVIEW_HOTEL_CUR_GEOZONE'];
		$aCacheConfig['REVIEW_BY_USER'.$iLanguageId]			= $aCacheConfig['REVIEW_BY_USER'];

		return ($aCacheConfig);
	}



	// Allow to share cache between function
	private function getShared($sCacheName)
	{
		switch($sCacheName)
		{
			case 'MASTERSITES_BYSITE':
				return array(
				'CACHE_TYPE' => TWMC_GLOBAL,
				'QUERY' => 'SELECT DISTINCT SITE.SITE_ID,MASTERSITE_ID,SITE_NAME,STORE_TYPE,AFFILIATE_ID FROM SITE INNER JOIN MASTER_SITE USING(SITE_ID) LEFT JOIN MERCHANT USING(SITE_ID)',
				'TRAVERSAL_KEY' => 'SITE.SITE_ID',
				'ALLOW_MULTIPLE_KEY' => true
				);
				break;

				// Select parent_id of an cat_id
			case 'CATEGORY_PID':
				return array(
				'CACHE_TYPE' => TWMC_GLOBAL,
				'QUERY' => 'SELECT CAT_ID,CAT_PID,CAT_SEQ FROM CATEGORY',
				'TRAVERSAL_KEY' => 'CAT_ID',
				'ALLOW_MULTIPLE_KEY' => true,
				'KEEP_KEY'=>false
				);
				break;

				// Select first level cat_id under an parent id
			case 'CATEGORY_PID_FIRST_CHILDS':
				return array(
				'CACHE_TYPE' => TWMC_GLOBAL,
				'QUERY' => 'SELECT CAT_ID,CAT_PID,CAT_SEQ,CAT_TYPE FROM CATEGORY ORDER BY CAT_PID, CAT_SEQ',
				'TRAVERSAL_KEY' => 'CAT_PID',
				'ALLOW_MULTIPLE_KEY' => true
				);
				break;
		}
	}



	private function mk_catlistnav_qry($language_id, $geozone)
	{
		$qry		 = 'SELECT ';
		$qry		.= ' CATEGORY.CAT_ID,CAT_PID, CAT_NAME, CAT_TYPE, KEYWORD_PRIORITY, IF(CATEGORY_LEAF.LEAF_ID IS NOT NULL,1,0) AS IS_LEAF, PRICE_NB, MERCHANT_NB, CAT_SEQ';
		$qry		.= ' FROM CATEGORY INNER JOIN CATEGORY_NAME ON CATEGORY.CAT_ID = CATEGORY_NAME.CAT_ID';
		$qry		.= ' INNER JOIN CATEGORY_DISPLAY ON CATEGORY_NAME.CAT_ID = CATEGORY_DISPLAY.CAT_ID';
		$qry 		.= ' LEFT JOIN CATEGORY_SIZE ON CATEGORY.CAT_ID = CATEGORY_SIZE.CAT_ID AND CATEGORY_DISPLAY.GEOZONE_CODE = CATEGORY_SIZE.GEOZONE_CODE';
		$qry		.= ' LEFT JOIN CATEGORY_LEAF ON CATEGORY.CAT_ID = CATEGORY_LEAF.LEAF_ID AND CATEGORY.CAT_ID = CATEGORY_LEAF.CAT_ID ';
		$qry		.= ' WHERE LENGTH(CAT_NAME) > 0 AND CATEGORY_NAME.LANG_ID = '.$language_id.' AND CATEGORY.CAT_ID > 1 AND CATEGORY_VISIBLE = 1 AND CATEGORY_DISPLAY.GEOZONE_CODE = '.$geozone;
		$qry		.= ' ORDER BY CAT_PID, CAT_SEQ, CAT_NAME';

		return $qry;
	}

	public static function getData($sInstanceName, $keys = NULL, $sBase = NULL)
	{
		return (self::getCacheInstance($sInstanceName)->getData($keys, $sBase));
	}

	public static function getMulti($sInstanceName, $keys = NULL, $sBase = NULL)
	{
		$aRes = self::getCacheInstance($sInstanceName)->getData($keys, $sBase);

		$aClean = array();

		foreach ($aRes as $aEntry)
		{
			$aClean[] = $aEntry[0];
		}

		unset($aRes);

		return $aClean;
	}

	public static function getCacheId($sInstanceName)
	{
		return (self::getCacheInstance($sInstanceName)->getCacheId());
	}

	public static function delete($sInstanceName, $iId)
	{
		return (self::getCacheInstance($sInstanceName)->delete($iId));
	}

	public static function increment($sInstanceName, $keys)
	{
		return (self::getCacheInstance($sInstanceName)->increment($keys));
	}

	public static function isRegisteredInstance($sInstanceName)
	{
		return (true == isset(self::$aRegisteredInstance[$sInstanceName]));
	}

	/**
	 *
	 * Enter description here ...
	 * @param string $sInstanceName
	 * @return \TwengaMemoryCache
	 */
	public static function getCacheInstance($sInstanceName) {

		if (false == self::isRegisteredInstance($sInstanceName))
		{
			self::addCacheInstance($sInstanceName);
		}

		return (self::$aRegisteredInstance[$sInstanceName]);
	}

	public static function addCacheInstance($sInstanceName) {

		if (true == isset(self::$aCacheConfig[$sInstanceName]))
		{
			self::$aRegisteredInstance[$sInstanceName] = new TwengaMemoryCache(self::$aCacheConfig[$sInstanceName]);
			self::$aRegisteredInstance[$sInstanceName]->cache_name = $sInstanceName;
		}
		else
		{
			//pre(debug_backtrace());
			die('No config found for instance: '.$sInstanceName);
		}
	}

	public static function getInstance()
	{
	    if(!self::$oInstance instanceof self)
	    {
	        self::$oInstance = new self();
	    }
	    return self::$oInstance;
	}
}
