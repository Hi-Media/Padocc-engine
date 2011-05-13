<?php
define("TWMC_GLOBAL", "TWMC_GLOBAL");
define("TWMC_LOCAL", "cache L1");
define("TWMC_LOCAL_GLOBAL", "cache allgz");
define("TWMC_TRAVEL", 3);
define("TWMC_LOCAL_FAST", 4);
define("TWMC_QANDA", 5);
define("TWMC_LOCAL_L2", "cache L2");
define("TWMC_ADSERVER", "TWMC_ADSERVER");

function tmTime($sMessage, $sType, &$oObject = null)
{
	if(class_exists("cTime",false))
		cTime::setTime($sMessage, "_memcache", array($oObject));
}

//An array to store frequently accessed keys in memory
$TWMC_FASTCACHE=$aTWMC=array();


function _echo($sString, $iColor=37)
{
	fwrite(STDOUT,"\x1B[".$iColor."m".$sString."\x1B[0m");
}

/**
 * This class is designed for the ram memcache
 * @author Caron Tony
 * @version	$Id: TwengaMemoryCache.php,v 1.37 2011/04/18 10:15:28 npavot Exp $
 * @access public
 */
class TwengaMemoryCache
{
	/**
	 * Duration of fail over mode in seconds
	 * @var int
	 */
	const MODE_FAILOVER_DURATION = 60;

	var $cacheid;
	var $cache_name;
	var $keynames;
	var $fullkeynames;
	var $keynames_count;
	var $sql;
	var $ttl;
	var $complex_query;
	var $allow_multiple_keys;
	var $keep_keys;
	var $memcache;
	var $keynames_prepared;
	var $invalid_cache;
	var $iAllowCompress;
	var $iCacheSize;
	var $iCompressCacheSize;
	var $iCacheSizeSave;
	var $sConnectEnv;
	var $iMustHaveKey;
	var $sMMCacheFile;
	var $sLastError;

	var $iConnected = false;

	/**
	* __construct
	* @param integer $_cache_type			?
	* @param string $_sql					Mysql Query
	* @param string $_keynames				Filter key, can be NULL (for retrieve all data with getData())
	* @param integer $_ttl
	* @param boolean $_allow_multiple_keys	Allow to return more than one key
	* @param boolean $_keep_keys			Allow to return the Filter key ($_keynames)
	* @access								public
	**/
	function __construct($aParam)
	{
		//$_cache_type,$_sql,$_keynames,$_ttl=864000,    $_allow_multiple_keys=FALSE,$_keep_keys=TRUE, $iAllowCompress=true, $iMustHaveKey=fals

		$this->sMMCacheFile = defined("MMCACHE_WEBLOG") ? MMCACHE_WEBLOG : NULL;
		$this->iCacheSize = 0;

		// Param
		$this->cache_type 			= $aParam["CACHE_TYPE"];
		$this->sDbBase				= isset($aParam["DB_BASE"]) ? $aParam["DB_BASE"] : NULL;
		$this->iMustHaveKey 		= isset($aParam["MUST_HAVE_KEY"]) ? $aParam["MUST_HAVE_KEY"] : false;
		$this->sql					= isset($aParam["QUERY"]) ? $aParam["QUERY"] : NULL;
		$this->iAllowCompress 		= isset($aParam["ALLOW_COMPRESS"]) ? $aParam["ALLOW_COMPRESS"] : true;

		// TODO Cette notion est en cours d'eviction :)
		$this->allow_multiple_keys	= isset($aParam["ALLOW_MULTIPLE_KEY"]) ? $aParam["ALLOW_MULTIPLE_KEY"] : false;
		$this->ttl 					= isset($aParam["LIFETIME"]) ? $aParam["LIFETIME"] : 0;
		if(isset($aParam["TRAVERSAL_KEY"]))
		{
			$this->fullkeynames			= !is_array($aParam["TRAVERSAL_KEY"]) ? array($aParam["TRAVERSAL_KEY"]) : $aParam["TRAVERSAL_KEY"];
		}
		else
			$this->fullkeynames			= array();
		$this->keep_keys			= isset($aParam["KEEP_KEY"]) ? $aParam["KEEP_KEY"] : true;
		//$this->sCacheIdConcat		= isset($aParam["CACHE_ID_CONCAT"]) ? $aParam["CACHE_ID_CONCAT"] : NULL;
		$this->oCacheIdRelease		= isset($aParam["CACHE_ID_RELEASE"]) ? TWMC::getCacheInstance($aParam["CACHE_ID_RELEASE"]) : NULL;
		$this->sRelease				= isset($aParam["RELEASE"]) ? $aParam["RELEASE"] : "";

		// TODO Remove createCacheId when param cache_id (monitor.php impact)
		$this->cacheid 				= isset($aParam["CACHE_ID"]) ? $this->createCacheId($aParam["CACHE_ID"]) : $this->createCacheId($this->sql);

		if($this->cache_type == TWMC_LOCAL_GLOBAL) $this->sMMCacheFile .= "_allgz";
		else if($this->cache_type == TWMC_LOCAL_L2) $this->sMMCacheFile .= "_l2";

		$this->keynames_count=count($this->fullkeynames);
		$this->keynames_prepared=false;
	}

	function setConnection()
	{
		$this->memcache = new Memcache;

		if(0)
		{
			$sL1 		 = MEMCACHE_HOST_L1;
			$sL1Failover = MEMCACHE_HOST_L1_FAILOVER;
			$sL2 		 = MEMCACHE_HOST_L2;
			$sL2Failover = MEMCACHE_HOST_L2_FAILOVER;
		}
		else
		{
			$sL1 		 = 'www17';
			$sL1Failover = $sL1;
			$sL2 		 = 'cache27'; 
			$sL2Failover = $sL2;
		}
		switch($this->cache_type)
		{
			//MEMCACHE_HOST_L1
			//MEMCACHE_HOST_L1_FAILOVER
			//MEMCACHE_HOST_L2_FAILOVER
			//MEMCACHE_HOST_L2
			case TWMC_GLOBAL:
			case TWMC_LOCAL_GLOBAL:
				$this->sConnectEnv = "MASTER";
				$this->memcache->addServer($sL1, 11700);
				$this->memcache->addServer($sL1Failover, 11700);
				break;
			case TWMC_LOCAL:
				$this->sConnectEnv = "L1";
				$this->memcache->addServer($sL1, 11700+DEFAULT_GEOZONE);
				$this->memcache->addServer($sL1Failover, 11700+DEFAULT_GEOZONE);
				break;
			case TWMC_LOCAL_L2:
				$this->sConnectEnv = "L2";
				if ( defined('MEMCACHE_L2_FORCE_FAILOVER') && MEMCACHE_L2_FORCE_FAILOVER == 1 )
				{
					$this->memcache->addServer($sL2Failover, 12400+DEFAULT_GEOZONE);
				}
		        else
		        {
					$this->memcache->addServer($sL2 , 12400+DEFAULT_GEOZONE);
					$this->memcache->addServer($sL2Failover, 12400+DEFAULT_GEOZONE);
		        }
				break;
			case TWMC_LOCAL_FAST:
				$this->sConnectEnv = "FAST";
				$this->memcache->addServer('94.75.205.19', 11411);
				$this->memcache->addServer('94.75.205.17', 11411);
				break;
			case TWMC_QANDA:
				$this->sConnectEnv = "QANDA";
				$this->memcache->addServer('94.75.205.19', 11411);
				$this->memcache->addServer('94.75.205.17', 11411);
				break;
			case TWMC_ADSERVER:
				$this->sConnectEnv = "ADSERVER";
				$this->memcache->addServer('localhost', MEMCACHE_PORT);
				break;
			case TWMC_TRAVEL:
				$this->sConnectEnv = "TRAVEL";
				$this->memcache->addServer(TRAVEL_MMCACHE, TRAVEL_MMCACHE_PORT);
				$this->memcache->addServer(TRAVEL_MMCACHE_FAILOVER, TRAVEL_MMCACHE_PORT);
				break;
			default:
				$this->sConnectEnv = "DEFAULT";
				$this->memcache->addServer(MEMCACHE_HOST_L1, 11700+DEFAULT_GEOZONE);
				$this->memcache->addServer(MEMCACHE_HOST_L1_FAILOVER, 11700+DEFAULT_GEOZONE);
				break;

		}
	}

	function connect()
	{
		if (!defined('GEN_MMCACHE') && !$this->iConnected)
		{
			$this->setConnection();

			$this->iConnected = true;
			return true;
		}
	}

	function __destruct()
	{
		if ($this->memcache!=false) @$this->memcache->close();
	}

	function createCacheId($sSql)
	{
		$sCacheId = "";
		if($this->oCacheIdRelease)
		{
			// Web Mode
			if (!defined('GEN_MMCACHE'))
				$sCacheId .= $this->oCacheIdRelease->getData();
			// Gen mmache Mode
			else
				$sCacheId .= $this->oCacheIdRelease->sRelease;
		}

		$sCacheId .= str_pad(dechex(crc32($sSql)), 8, '0', STR_PAD_LEFT);;

		return $sCacheId;
	}

	function getCacheId()
	{
		return $this->cacheid;
	}

	function isGzip(&$data)
	{
		if ( !is_scalar($data) ) return 0;
		if ( !isset($data[0]) || !isset($data[1]) ) return 0;
		return ord($data[0]).ord($data[1]) == 120218 ? 1 : 0;
	}
	function compressCache(&$aResult)
	{
		return gzcompress(serialize($aResult), 9);
	}

	function unCompressCache(&$sResult)
	{
		if($this->isGzip($sResult))
		{
			$sResult =  unserialize(gzuncompress($sResult));
		}
	}

	function preparePurge($key, $gz=0)
	{
		return false;
		$local_db = ADONewConnection(DB_DBMS);
		$local_db->Connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DB);
		$local_db->SetFetchMode(ADODB_FETCH_ASSOC);
		$sCacheId = $this->cacheid;

		$sSql = "INSERT INTO MMCACHE_PURGE (CACHE_ID, KEY_ID, GEOZONE_CODE) VALUES ('".$sCacheId."', '".$key."', '".$gz."')";

		$rs=$local_db->Execute($sSql);
	}

	function prepareKeynames()
	{
		if (!$this->keynames_prepared)
		{
			$this->keynames = array();
			foreach ($this->fullkeynames as &$v)
			{
				$p=stristr($v,'.');
				if ($p!=FALSE) $this->keynames[]=substr($p,1);
				else $this->keynames[]=$v;
			}
			$this->keynames_prepared=true;
		}
	}

	function delete($iId)
	{
		$this->connect();
		$sCacheId = $this->cacheid."_".$iId;
		return $this->memcache->delete($sCacheId);
	}

	function setData($iId, $sBase)
	{
		$this->connect();

		global $db;
		$db->base = $sBase;
		$sKey = $this->fullkeynames[0];

		if(is_array($iId))
		{
			$sBaseKey = $sKey.' IN ('.implode(",", $iId).')';
		}
		else
		{
			$sBaseKey = $sKey.' = '.$iId;
		}

		//$db->SetFetchMode(3);

		$sQuery = $this->sql;
		$sTmpSql = "";

		// Where existant
		if (stristr($sQuery,"WHERE")!=false)
			$sTmpSql.=' AND '.$sBaseKey;
		else
			$sTmpSql.=' WHERE '.$sBaseKey;
		if (stristr($sQuery,'GROUP BY'))
			$sQuery=preg_replace('/(group by.*)/i',$sTmpSql." $1",$sQuery);
		else if (stristr($sQuery,'ORDER BY'))
			$sQuery=preg_replace('/(order by.*)/i',$sTmpSql." $1",$sQuery);
		else $sQuery.=' '.$sTmpSql;

		$rs = $db->Execute($sQuery);
		$a = $rs->getArray();


		if(!is_array($iId))
		{
			$sCacheId = $this->cacheid."_".$iId;
			$a = $a[0];
			$this->memcache->set($sCacheId, $a, $this->iAllowCompress, 0);
			return $a;
		}
		else
		{
			$aReturn = array();
			foreach($a as $v)
			{
				$sCacheId = $this->cacheid."_".$v[$sKey];
				$this->memcache->set($sCacheId, $v, $this->iAllowCompress, 0);

				$aReturn[$sCacheId] = $v;
			}
			return $aReturn;
		}
	}


	function increment($keys)
	{
		$this->connect();

 		$i = $this->getData($keys);

 		$sKey = $this->cacheid.'_'.$keys;
 		if(count($i) == 0)
 			$this->memcache->set($sKey, "1", false, 0);
 		else
 			$this->memcache->increment($sKey, 1);
	}

	// Notes:
	// Si la clé est un tableau de clé, memcache renvoie les données trié dans un tableau de cache_id [a44cfbe1_7665] => Array (Donc une dimension en plus
	// Sinon le tableau est normal
	function getData($keys=NULL, $sBase=NULL)
	{
		$this->connect();
		$aReturn = array();

		// TWMC_LOCAL_FAST Must have sBase
		/*if($this->cache_type == TWMC_LOCAL_FAST && $sBase == NULL && $this->sDbBase == NULL)
		{
			if(defined("IS_DEV") && IS_DEV) echo "TWMC_LOCAL_FAST should have a base name";
			return false;
		}*/

		//$this->cache_name = $this->cache_name.rand(0,10000);
		//$sMessage = $this->cache_name.(is_array($keys)? implode('', $keys) : $keys).(rand(0,10000));
		$sMessage = $this->cache_name;

		if ($this->invalid_cache) //Straight query to Database
		{
			tmTime($sMessage."(INVALID)", "_memcache");
			tmTime($sMessage."(INVALID)", "_memcache", $this);
			return array();
		}
		else
		{
			tmTime($sMessage, "_memcache");

			// SPECIAL GETDATA ON ALLGZ
			if($this->cache_type == TWMC_LOCAL_GLOBAL)
			{
				$a = TWMC::getData('GEOZONE');
				$mBaseKey = $keys;

				if (is_scalar($keys) || $keys == NULL)
				{
					$keys = array();
					foreach($a as $k=>$v)
					{
						$keys[] = $v["GEOZONE_CODE"]."_".$mBaseKey;
					}
				}
				else
				{
					$keys = array();
					foreach($mBaseKey as $k=>$v)
					{
						foreach($a as $k2=>$v2)
						{
							$keys[] = $v2["GEOZONE_CODE"]."_".$v;
						}
					}
				}

			}

			// Retrieve only one
			if ( (isset($keys) && is_scalar($keys)) || (!isset($keys) || $keys == NULL))
			{
				global $TWMC_FASTCACHE;
				$k=$this->cacheid.'_'.(isset($keys) ? $keys : "");

				// Key already served
				if (array_key_exists($k,$TWMC_FASTCACHE))
				{
					$aReturn = &$TWMC_FASTCACHE[$k];
				}
				// Retrieve key
				else
				{
					$ret=$this->memcache->get($k);

					if($this->iAllowCompress)
					$this->unCompressCache($ret);

					// No data
					if($ret === false)
					{
						// TWMC_LOCAL_FAST allow DB retrieve when no data
						if($this->cache_type == TWMC_LOCAL_FAST && $this->sDbBase != NULL)
						{
							$mData = $this->setData($keys, $this->sDbBase);
							$aReturn = &$mData;
						}
					}
					else
					{
						// TODO Try to save all in memory
						if (is_scalar($ret))
							$TWMC_FASTCACHE[$k]=$ret;

						$aReturn = &$ret;
					}
				}
			}
			else
			{
				// Multiple keys
				$k0=$this->cacheid.'_';
				foreach ($keys as &$k)
				{
					$mykeys[]=$k0.$k;
				}
				unset($k);

				$ret = $this->memcache->get($mykeys);

				if($this->iAllowCompress)
				{
					foreach($ret as $k=>$v)
					$this->unCompressCache($ret[$k]);
				}

				// No data OR not all data
				if($ret === false || count($ret) != count($keys))
				{
					// TWMC_LOCAL_FAST allow DB retrieve when no data
					if($this->cache_type == TWMC_LOCAL_FAST && $this->sDbBase != NULL)
					{
						$mData = $this->setData($keys, $this->sDbBase);
						$aReturn = &$mData;
					}
				}

				$aReturn = &$ret;
			}
			tmTime($sMessage, "_memcache", $this);

			// TODO try to return by ref
			return $aReturn;
		}
	}

	function KeyExists(&$keys)
	{
		$this->connect();

		$kcount=count($keys);
		if ($kcount!=$this->keynames_count)
					trigger_error("Missing multiple keys in call to KeyExists!",E_USER_ERROR);

		if ($this->invalid_cache) //Straight query to Database
		{
			$local_db = ADONewConnection(DB_DBMS);
			$local_db->Connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DB);
			//$local_db->debug=true;

			$this->prepareKeynames();
			$sql=$this->sql;

			$txt=preg_replace('/(\(.*\))/','xxx',$sql);
			$p=stristr($txt,"where");
			if ($p!=false) $tmpsql.=" and ";
			else $tmpsql.=" where ";
			$i=0;
			$n=count($this->keynames);

			foreach ($this->keynames as &$k)
			{
				$tmpsql.=$k.'='.$local_db->qstr($keys[$i]);
				$i++;
				if ($i<$n) $tmpsql.=' and ';
			}
			if (stristr($txt,'GROUP BY')) {
				$sql=preg_replace('/(group by.*)/i',$tmpsql." $1",$sql);
            }else if (stristr($txt,'ORDER BY')) {
                $sql=preg_replace('/(order by.*)/i',$tmpsql." $1",$sql);
            }
			else $sql.=' '.$tmpsql;

			$rs=$local_db->CacheExecute(3600,$sql);
			$ret=($rs->fields!=false);
			$rs->Close();
			$local_db->Close();
			return $ret;
		}
		else
		{
			$id=$this->cacheid.'_';
			if (is_array($keys))
			{
				$i=1;
				foreach ($keys as &$v)
				{
					$id.=$v;
					if ($i++<$kcount) $id.='_';
				}
			}
			else $id.=$keys;

			if ($this->memcache->get($id)!==FALSE) return true;
			else return false;
		}
	}

	function refreshData()
	{
		if (!defined('GEN_MMCACHE')) return;
		if (!defined('MMCACHE_WEBLOG')) die("Missing MMCACHE_WEBLOG !\n");

		//$hCacheFile=fopen($this->sMMCacheFile."_".$this->cache_name."_".$this->cacheid, "a");
		//$hCacheFile=fopen($this->sMMCacheFile, "a");

		$hCacheFile=fopen($this->sMMCacheFile, "a");
		//$hCacheFile=fopen($this->sMMCacheFile."_".$this->cache_name."_".$this->cacheid, "a");

		$this->invalid_cache=true;

		$local_db = ADONewConnection(DB_DBMS);
		//$local_db->debug=true;
		$local_db->Connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DB);
		set_time_limit(0);
		$this->prepareKeynames();
		$local_db->SetFetchMode(ADODB_FETCH_ASSOC);
		$rs=$local_db->Execute($this->sql);

		if(!$rs)
		{
			$this->sLastError = $local_db->ErrorMsg();
			return;
		}

		if (!$rs->EOF) {

			$data=array();
			$n=count($this->keynames);
			$prefix=$this->cacheid.'_';

			if (!$this->allow_multiple_keys)
			{
				$v=$rs->fields;
				if (!$this->keep_keys) foreach ($this->keynames as &$k) unset($v[$k]);
				$x=array_values($v);
				if (count($x)==1) $store_singlevalue=true;
				else $store_singlevalue=false;
			}

			$store_ok=true;
			$old_id=NULL;
			$tmp=array();

			$the_ttl=$this->ttl;
			while (!$rs->EOF)
			{
				$v=&$rs->fields;

				$id="";
				$j=1;
				foreach ($this->keynames as &$k)
				{
					$id.=$v[$k];
					if ($j++<$n) $id.='_';
					if (!$this->keep_keys) unset($v[$k]);
				}

				if (!$this->allow_multiple_keys)
				{
					$key=$prefix.$id;
					if ($store_singlevalue)
						$this->store_dump($hCacheFile,$key,current($v),$the_ttl);
					else
						$this->store_dump($hCacheFile,$key,$v,$the_ttl);
				}
				else
				{
					if ($this->allow_multiple_keys) {
						if ($old_id===NULL || $old_id==$id) {
							$tmp[]=$v;
						}
						else
						{
							//if (array_key_exists($old_id,$data))
							//	trigger_error("Query not sorted by search key : ".$this->sql."...",E_USER_ERROR);

							$key=$prefix.$old_id;
							$this->store_dump($hCacheFile,$key,$tmp,$the_ttl);
							$data[$old_id]=1;
							unset($tmp);
							$tmp=array($v);
						}
					}

					else {
						trigger_error("Multiple keys not allowed for query ".$this->sql."...",E_USER_ERROR);
					}
				}

				unset($v);
				$rs->MoveNext();
				if (!$store_ok) {
					$rs->Close();
					$local_db->Close();
					fclose($hCacheFile);
					die("Unable to store data with key $id!");
				}
				$old_id=$id;

				if ($this->allow_multiple_keys && $rs->EOF)
				{
					$key=$prefix.$id;
					$this->store_dump($hCacheFile,$key,$tmp,$the_ttl);
				}
			}
			unset($data);
		}

		$t=time();
		$this->store_dump($hCacheFile,$this->cacheid,$t,$this->ttl);
		fclose($hCacheFile);
		$this->invalid_cache=false;
		$rs->Close();
		$local_db->Close();

	}

	function store_dump(&$log,&$k,&$v,&$ttl)
	{
		//echo ";;;;;;;".$this->cache_name;
		// Normal DATA stat
		$sData=serialize($v);
		$iData=strlen($sData);
		if($iData > 1500000)
			trigger_error("Maximum 1Mo of Data for serialize for (".$iData.") ".$k." \n".$this->sql."...",E_USER_ERROR);
		$sSet = "set $k 1 $ttl $iData\r\n";

		$iData=$iData+strlen($sSet."\r\n");
		$this->iCacheSize+=$iData;

		if($this->iAllowCompress)
		{
			if($iData > 1000)
			{
				// Compress DATA
				$sCompressData = serialize(gzcompress($sData, 9));
				$iCompressData=strlen($sCompressData);
				$sSet = "set $k 1 $ttl $iCompressData\r\n";

				$iCompressData = $iCompressData+strlen($sSet."\r\n");

				if(((($iData - $iCompressData)*100)/$iData)> 60)
				{
					$this->iCompressCacheSize += $iCompressData;
					$this->iCacheSizeSave += $iData - $iCompressData;

					fwrite($log, $sSet);
					fwrite($log,$sCompressData."\r\n");
					return true;
				}
			}
		}

		$n=strlen($sData);
		$sSet = "set $k 1 $ttl $n\r\n";
		fwrite($log, $sSet);
		fwrite($log,$sData."\r\n");

		if(isset($this->sReturnValue)) $this->sReturnValue = "mm";


		return true;
	}
}


if (!defined('GEN_MMCACHE') )
{
	require_once('TWMC.php');


	if ( SITE_USAGE == 'ADMIN' )
	{

	}
	else
	{
		//$_language_id=getLanguageId();
		//$_gz=DEFAULT_GEOZONE;

		$oCacheManager = new TWMC();
		$oCacheManager->initWebCaches(DEFAULT_GEOZONE, getLanguageId());
	}
}
?>
