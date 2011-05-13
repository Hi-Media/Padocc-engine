#!/bin/sh

. /home/gaubry/twenga/tools/master_synchro.cfg

# Liste des paramètres de script autorisés et de leur domaine de définition (chaîne de valeurs séparées par un espace).
# Positionner le domaine à "" (chaîne vide) pour un paramètre à valeur libre.
declare -A AVAILABLE_OPTIONS
AVAILABLE_OPTIONS=(
	[aai]=""
	[autoconfirm]=""
	[bin]=""
	[config]=""
	[deployweb]="bct exclusive internal qa web"
	[ebay]=""
	[monitor]=""
	[mysql_backup]=""
	[pagekeyword_staging]=""
	[pagekeyword]=""
	[photo_crawler]=""
	[photo_storage]=""
	[target]=""
	[tmpphoto]=""
	[twbuild]=""
	[url_path]=""
	[verbose]=""
	[web]="bct internal qa web common"
	[web]="admin adserver bct common config exclusive extranet extranet-qa fulladmin image image2 img internal merchant qa recrute rewrite web"
	[webscripts]=""
)

# Valeur par défaut d'un paramètre de script
OPTION_DEFAULT_VALUE='__default__'

OK_MSG=`echo -e "\033[1;32mOK\033[00m"`
WAITING_MSG=`echo -e "\033[32m...\033[00m "`
TIMESTAMP_FORMAT='[%Y-%m-%d %H:%M:%S] ' # Formattage de l'horodatage des echo()

# Affiche l'aide
function displayHelp {
	echo -e "Usage:
	\t--config config_src\t\t\t\t: synchronize configuration files
	\t--photo_crawler\t\t\t\t\t: synchronize scripts files
	\t--ebay\t\t\t\t\t\t: synchronize scripts files
	\t--webscripts\t\t\t\t\t: synchronize scripts files
	\t--photo_storage\t\t\t\t\t: synchronize scripts files
	\t--url_path\t\t\t\t\t: synchronize scripts files
	\t--mysql_backup\t\t\t\t\t: synchronize scripts files
	\t--pagekeyword\t\t\t\t\t: synchronize scripts files
	\t--pagekeyword_staging\t\t\t\t: synchronize scripts files
	\t--monitor\t\t\t\t\t: synchronize scripts files for cron
	\t--aai\t\t\t\t\t\t: synchronize scripts files for cron
	\t--bin [server]\t\t\t\t\t: synchronize binaries on servers
	\t--tmpphoto [absolute_filename]\t\t\t: synchronize tmpphotos on image web server [only absolute_filename if provided]
	\t--help\t\t\t\t\t\t: this help message
	
	\t--deployweb bct|exclusive|internal|qa|web\t: deploy new web files
	\t--twbuild twengabuild\t\t\t\t: twengabuild to deploy, required by --deployweb
	\t--target server\t\t\t\t\t: force target server for both --deployweb and --web
	\t--autoconfirm\t\t\t\t\t: force confirm change for both --deployweb and --web
	\t--web [web|qa|internal|bct|common]\t\t: synchronize web files
	\t--web [merchant|adserver]\t\t\t: synchronize web files
	\t--web [extranet|extranet-qa]\t\t\t: synchronize web files (RTS)
	\t--web [image|image2|img]\t\t\t: ?
	\t--web [recrute]\t\t\t\t\t: synchronize recrute web files
	\t--web [config]\t\t\t\t\t: synchronize web config files
	\t--web [rewrite]\t\t\t\t\t: synchronize web rewrite rules
	\t--web [admin|fulladmin]\t\t\t\t: synchronize back office center web files
	\t--web [exclusive]\t\t\t\t: synchronize Exclusive QA web files [IN PROGRESS]
	\t--verbose\t\t\t\t\t: increase verbosity for --web"
}

# Enregistre les paramètres d'appel du script dans la hashtable (param=>value) globale $OPTIONS.
# Les paramètres sont de la forme "--param value", par exemple : "$0 --p1 --p2 value"
#
# IN : paramètres d'appel du script (getScriptParameters $@)
# OUT : 0, ou 1 si erreur d'analyse
function getScriptParameters {
	ERROR_CODE=0
	while [ $# -gt 0 ]; do
		PARAM=`echo $1 | grep '^--' | sed s/--//`
		[ -z $PARAM ] && echo "BAD $1" && ERROR_CODE=1 && break
		shift
		
		VALUE=`echo $1 | grep -v '^--'`
		if [ -z $VALUE ]; then
			VALUE=$OPTION_DEFAULT_VALUE
		else
			shift
		fi
		
		OPTIONS[$PARAM]=$VALUE
	done
	return $ERROR_CODE
}

function checkParameters {
	for PARAM in ${!OPTIONS[*]}; do
		IS_PARAM_OK=`echo ${!AVAILABLE_OPTIONS[*]} | grep "\b$PARAM\b" | wc -l`
		[ $IS_PARAM_OK = 0 ] && echo "Unknown parameter '$PARAM'!" >&2 && return 1
		
		if [ ${#AVAILABLE_OPTIONS[$PARAM]} -gt 0 ]; then
			IS_VALUE_OK=`echo ${AVAILABLE_OPTIONS[$PARAM]} | grep "\b${OPTIONS[$PARAM]}\b" | wc -l`
			[ $IS_VALUE_OK = 0 ] && echo "Bad value for '$PARAM' parameter! Must be in {${AVAILABLE_OPTIONS[$PARAM]// /, }} but '${OPTIONS[$PARAM]}' found." >&2 && return 1
		fi
	done
	return 0
}

# Utilisé par --deployweb
function confirmChange {
	if [ "$twengabuild_num" != "" ]; then
		extramsg="(TWENGABUILD: $twengabuild_num) "
	fi
	echo `date "+$TIMESTAMP_FORMAT"`"You are about to migrate $web_target ${extramsg}on the following servers:";
	echo -en "\\033[1;31m$web_servers\\033[0;39m";
    echo -n "Do you want to continue ? [Y/N] ";

	if [ ! -z ${OPTIONS[autoconfirm]} ]; then
		echo -n "Y (Autoconfirm mode) ";
	else
		read answer
		if [ "$answer" != "Y" ]; then
			echo "Synchronization aborted."
			exit 0;
		fi
	fi
}

declare -A OPTIONS
getScriptParameters $@
([ $? = 1 ] || [ $# = 0 ]) && OPTIONS[help]=$OPTION_DEFAULT_VALUE
[ ! -z ${OPTIONS[help]} ] && displayHelp && exit 1
checkParameters
[ $? != 0 ] && displayHelp && exit 2

if [ ! -z ${OPTIONS[bin]} ]; then
	[ ${OPTIONS[bin]} = $OPTION_DEFAULT_VALUE ] && OPTIONS[bin]="$SERVER_BINARIES_64"
	SRC_FOLDER_BINARIES="$FOLDER_BINARIES"
	DST_FOLDER_BINARIES="$FOLDER_BINARIES"
	BCK_FOLDER_BINARIES="$FOLDER_MASTER/BINARIES"

	timestamp=`date +'%Y%m%d.%H%M%S'`
	echo -n "Backing up binaries (ts=${timestamp})$WAITING_MSG"
	#binlist=`ls $SRC_FOLDER_BINARIES/`
	for binprog in $binlist; do
		#cp $SRC_FOLDER_BINARIES/$binprog $BCK_FOLDER_BINARIES/${binprog}.${timestamp}
		: # TMP
	done
	echo $OK_MSG;

	echo -n "Deploying binaries to "
	for server in ${OPTIONS[bin]}; do
		echo -n "$server "
		#rsync -rpt -e ssh $SRC_FOLDER_BINARIES/ $server:$DST_FOLDER_BINARIES &
		(sleep 5)& # TMP
	done
	echo $OK_MSG;
	echo -n "Waiting for synchronization to complete$WAITING_MSG"
	wait
	echo $OK_MSG
	
elif [ ! -z ${OPTIONS[tmpphoto]} ]; then
	[ ${OPTIONS[tmpphoto]} = $OPTION_DEFAULT_VALUE ] && OPTIONS[tmpphoto]="${FOLDER_TMPPHOTO_SRC}/"
	echo -n "Synchronizing tmpphotos ${OPTIONS[tmpphoto]}$WAITING_MSG";
	#rsync -rpt -e ssh "${OPTIONS[tmpphoto]}"  web1:"${FOLDER_TMPPHOTO_DST}"
	sleep 1 # TMP
	rc=$?
	if [ "$rc" != "0" ]; then
		echo "error ($rc)";
		exit 1
	else
		echo $OK_MSG
	fi
	
elif [ ! -z ${OPTIONS[photo_crawler]} ]; then
	target_servers="$SERVER_PHOTO_CRAWLER"
	echo -n "Deploying files on server Puppet-01$WAITING_MSG"
	#cd /home/prod/twengaweb/photo_crawler/; cvs up -d
	PUPPET_SRV="monitor09"
	PUPPET_DIR="/var/lib/puppet/files/user-prod/twenga/tools-photocrawler"
	
	#scp    /home/prod/twengaweb/photo_crawler/photo_crawler.php         $PUPPET_SRV:$PUPPET_DIR/photo_crawler/photo_crawler.php
	#scp    /home/prod/twengaweb/photo_crawler/purge.php                 $PUPPET_SRV:$PUPPET_DIR/photo_crawler/purge.php
	#scp    /home/prod/twengaweb/photo_crawler/copy_bmvp_photo.php       $PUPPET_SRV:$PUPPET_DIR/copy_bmvp_photo.php
	#scp -r /home/prod/twengaweb/photo_crawler/class                     $PUPPET_SRV:$PUPPET_DIR/photo_crawler/
	#scp    /home/prod/twenga/tools/start_photo_crawler                  $PUPPET_SRV:$PUPPET_DIR/start_photo_crawler
	#scp    /home/prod/twengaweb/travel/v2/scripts/start_travel_crawler 	$PUPPET_SRV:$PUPPET_DIR/start_travel_crawler
	#scp    /home/prod/twenga/tools/shard_exec                           $PUPPET_SRV:$PUPPET_DIR/shard_exec
	#scp    /home/prod/twenga/tools/shard_country_consolidation          $PUPPET_SRV:$PUPPET_DIR/shard_country_consolidation

	#scp	/home/prod/twenga/tools/shard_exec							prodimg@monitor07:/home/prodimg/twenga/tools/shard_exec
	#scp    /home/prod/twenga/tools/shard_country_consolidation			prodimg@monitor07:/home/prodimg/twenga/tools/shard_country_consolidation
	echo $OK_MSG

	for server in $target_servers; do
		echo -n "Deploying files on server $server$WAITING_MSG"
		#scp    /home/prod/twengaweb/photo_crawler/photo_crawler.php         $server:/home/prod/twenga/tools/photo_crawler/photo_crawler.php
		#scp    /home/prod/twengaweb/photo_crawler/purge.php                 $server:/home/prod/twenga/tools/photo_crawler/purge.php
		#scp    /home/prod/twengaweb/photo_crawler/copy_bmvp_photo.php       $server:/home/prod/twenga/tools/copy_bmvp_photo.php
		#scp -r /home/prod/twengaweb/photo_crawler/class                     $server:/home/prod/twenga/tools/photo_crawler/
		#scp    /home/prod/twenga/tools/start_photo_crawler                  $server:/home/prod/twenga/tools/start_photo_crawler
		#scp    /home/prod/twengaweb/travel/v2/scripts/start_travel_crawler  $server:/home/prod/twenga/tools/start_travel_crawler
		#scp    /home/prod/twenga/tools/shard_exec                           $server:/home/prod/twenga/tools/shard_exec
		#scp    /home/prod/twenga/tools/shard_country_consolidation          $server:/home/prod/twenga/tools/shard_country_consolidation
		echo $OK_MSG
	done
	
elif [ ! -z ${OPTIONS[photo_storage]} ]; then
	target_servers="$TWENGA_STORAGE_GZ1 $TWENGA_STORAGE_GZ2 $TWENGA_STORAGE_GZ3 $TWENGA_STORAGE_GZ4 $TWENGA_STORAGE_GZ5 $TWENGA_STORAGE_GZ6 $TWENGA_STORAGE_GZ7 $TWENGA_STORAGE_GZ8 $TWENGA_STORAGE_GZ9 $TWENGA_STORAGE_GZ10 $TWENGA_STORAGE_GZ11 $TWENGA_STORAGE_GZ12 $TWENGA_STORAGE_GZ13 $TWENGA_STORAGE_GZ14 $TWENGA_STORAGE_GZ15 $TWENGA_STORAGE_GZ16"
	#cd /home/prod/twengaweb/photo_crawler/; cvs up -d
	for server in $target_servers; do
		echo -n "Deploying files on server $server$WAITING_MSG"
		#scp /home/prod/twengaweb/photo_crawler/purge.php $server:/home/prod/twenga/tools/photo_crawler/purge.php
		#scp /home/prod/twengaweb/photo_crawler/run_purge $server:/home/prod/twenga/tools/photo_crawler/run_purge
		#scp /home/prod/twengaweb/photo_crawler/c_crop.php $server:/home/prod/twenga/tools/photo_crawler/c_crop.php
		#scp /home/prod/twengaweb/photo_crawler/photo_crawler.php $server:/home/prod/twenga/tools/photo_crawler/photo_crawler.php
		#scp /home/prod/twenga/tools/start_photo_crawler $server:/home/prod/twenga/tools/start_photo_crawler
		echo $OK_MSG
	done
	
elif [ ! -z ${OPTIONS[monitor]} ]; then
	target_servers="$SERVER_CRON"
	for server in $target_servers; do
		echo -n "Deploying files on server $server$WAITING_MSG"
		#scp -rp /home/prod/twengaweb/twenga_seo/twenga_seo_backup.sh $server:/home/prod/twengaweb/twenga_seo/
		#scp -rp /home/prod/twenga/tools/run_mysql_backup $server:/home/prod/twenga/tools/
		#scp -rp /home/prod/twenga/tools/run_ebay_backup.sh $server:/home/prod/twenga/tools/
		#scp -rp /home/prod/twenga/tools/feeds/build_ebay_feed $server:/home/prod/twenga/tools/feeds/
		#scp -rp /home/prod/twenga/tools/master_synchro.cfg $server:/home/prod/twenga/tools/
		#scp -rp /home/prod/twenga/tools/shard_exec $server:/home/prod/twenga/tools/
		#scp -rp /home/prod/twenga/tools/haproxycluster $server:/home/prod/twenga/tools/
		#scp -rp /home/prod/twenga/tools/wwwfulltest $server:/home/prod/twenga/tools/
		#scp -rp /home/prod/twenga/tools/monitorlib $server:/home/prod/twenga/tools/
		#scp -rp /home/prod/twenga/tools/maintenance.sh $server:/home/prod/twenga/tools/
		#scp -rp /home/prod/twenga/tools/.wwwfulltest.* $server:/home/prod/twenga/tools/
		#scp -rp /home/prod/twenga/tools/wwwcluster $server:/home/prod/twenga/tools/
		#scp -rp /home/prod/twenga/tools/runphp $server:/home/prod/twenga/tools/
		#scp -rp /home/prod/twenga/tools/repl_control.php $server:/home/prod/twenga/tools/
		#scp -rp /home/prod/twenga/tools/repl_control $server:/home/prod/twenga/tools/
		echo $OK_MSG
	done
	
elif [ ! -z ${OPTIONS[pagekeyword]} ] || [ ! -z ${OPTIONS[pagekeyword_staging]} ]; then
	if [ ! -z ${OPTIONS[pagekeyword_staging]} ]; then
		target_servers="`echo $SERVER_WBATCH_ALL | tr ' ' '\n' | grep -v ^www | grep -v wbatch02 | grep -v wbatch03 | grep -v wbatch04 | grep -v wbatch05 | grep -v wbatch11 | grep -v wbatch12 | grep -v wbatch-02 | tr '\n' ' '`"
	else
		target_servers="`echo $SERVER_WBATCH_ALL | tr ' ' '\n' | grep -v ^www | grep -v wbatch10 | grep -v wbatch01 | grep -v wbatch-01 | tr '\n' ' '`"
	fi
	#cd /home/prod/twengaweb/twengaproject/pagekeyword/; cvs up -d
	#cd /home/prod/twengaweb/url_path/; cvs up -d
	for server in $target_servers; do
		echo -n "Deploying files on server $server$WAITING_MSG"
		#rsync -rpt --cvs-exclude --exclude=config* -e ssh /home/prod/twengaweb/twengaproject/pagekeyword/ $server:/home/httpd/twengaproject/pagekeyword
		#rsync -rpt --cvs-exclude --exclude=config* -e ssh /home/prod/twengaweb/twengaproject/url_path/ $server:/home/httpd/twengaproject/url_path
		#rsync -rpt --cvs-exclude --exclude=config* -e ssh /home/prod/twenga/tools/copy_table $server:/home/prod/twenga/tools/
		#ssh $server chmod +x /home/httpd/twengaproject/pagekeyword/kw_*
		echo $OK_MSG
	done

	#cvsexport twengaweb/common common
	#cd $cvsexport_dir/common/

	for server in $target_servers; do
		: #rsync -rpt --cvs-exclude --exclude=config* -e ssh $cvsexport_dir/common/ $server:/home/httpd/twenga-common
	done

	if [ "`echo $cvsexport_dir | cut -c 1-10`" = "/home/tmp/" ]; then
		: #rm -rf $cvsexport_dir
	else
		echo "Critical error: cvsexport_dir is invalid" 1>&2
	fi
	
elif [ ! -z ${OPTIONS[webscripts]} ]; then
	target_servers="$SERVER_ADMIN"
	#cd /home/prod/twengaweb/scripts/; cvs up -d
	for server in $target_servers; do
		echo -n "Deploying files on server $server$WAITING_MSG"
		#rsync -rpt --cvs-exclude --links --exclude=config* -e ssh /home/prod/twengaweb/scripts/ $server:/home/httpd/scripts
		echo $OK_MSG
	done
	
elif [ ! -z ${OPTIONS[aai]} ]; then
	target_servers="dv4"
	#cd /home/prod/twengaweb/aai/;
	for server in $target_servers; do
		echo -n "Deploying files on server $server$WAITING_MSG"
		#rsync -rpt --cvs-exclude --exclude=config* -e ssh /home/prod/twengaweb/aai/ $server:/home/httpd/aai
		echo $OK_MSG
	done
	
	#cd /home/prod/twengaweb/smarty/aai/;
	for server in $target_servers; do
		echo -n "Deploying smarty files on server $server$WAITING_MSG"
		#rsync -rpt --cvs-exclude --exclude=config* -e ssh /home/prod/twengaweb/smarty/aai/ $server:/home/httpd/smarty/aai
		echo $OK_MSG
	done
	
	#cd /home/prod/twengaweb/common/;
	for server in $target_servers; do
		echo -n "Deploying common files on server $server$WAITING_MSG"
		#rsync -rpt --cvs-exclude --exclude=config* -e ssh /home/prod/twengaweb/common/ $server:/home/httpd/twenga-common
		echo $OK_MSG
	done
	#curl --silent --retry 2 --retry-delay 2 --max-time 5 -d server="$target_servers" -d app="aai" http://aai.twenga.com/push.php &
	
elif [ ! -z ${OPTIONS[url_path]} ]; then
	target_servers=`echo $SERVER_ADMIN | awk '{print $1}'`
	#cd /home/prod/twengaweb/twengaproject/url_path/; cvs up -d
	for server in $target_servers; do
		echo -n "Deploying files on server $server$WAITING_MSG"
		#rsync -rpt --cvs-exclude --exclude=config* -e ssh /home/prod/twengaweb/twengaproject/url_path/ $server:/home/httpd/twengaproject/url_path
		echo $OK_MSG
	done
	
elif [ ! -z ${OPTIONS[mysql_backup]} ]; then
	target_servers="`grep "\bmysql" /etc/hosts | awk '{print $2}' | cut -d '.' -f1 | uniq`"
	for server in $target_servers; do
		echo -n "Deploying files on server $server$WAITING_MSG"
		#scp /home/prod/twenga/tools/database_backup/mysql_backup $server:/data/backups
		#scp /home/prod/twenga/tools/database_backup/zipbackup $server:/data/backups
		#scp /home/prod/twenga/tools/database_backup/purge_backup $server:/data/backups
		#scp /home/prod/twenga/tools/database_backup/mysql_backup_exclude_table.cfg $server:/data/backups
		#scp /home/prod/twenga/tools/database_backup/restore_geozone $server:/data/backups
		#scp /home/prod/twenga/tools/database_backup/restore $server:/data/backups
		#scp /home/prod/twenga/tools/database_backup/twmysql_backup $server:/data/backups
		#scp /home/prod/twenga/tools/database_backup/twmysql_restore $server:/data/backups
		echo $OK_MSG
	done
	
elif [ ! -z ${OPTIONS[ebay]} ]; then
	#cd /home/prod/twengaweb/scripts/special_feeds/ebay/; cvs up -d
	#cd /home/prod/twengaweb/scripts/special_feeds/ebayClassifieds/; cvs up -d
	target_servers="`grep "\bebay" /etc/hosts | awk '{print $2}' | cut -d '.' -f1 | uniq`"
	for server in $target_servers; do
		echo -n "Deploying files on server $server$WAITING_MSG"
		source_folder=/home/prod/twengaweb/scripts/special_feeds/ebay
		dest_folder=/home/prod/twenga/crontabs/special_feeds/ebay_v2
		#scp $source_folder/crawler_desc_ebay.php $server:$dest_folder/
		#scp $source_folder/ebay_conf.php $server:$dest_folder/
		#scp $source_folder/get_ebay_feed.php $server:$dest_folder/
		#scp $source_folder/run_build_ebay $server:$dest_folder/
		#scp $source_folder/run_ebay_feed $server:$dest_folder/
		#scp $source_folder/start_crawler_desc $server:$dest_folder/
		#scp $source_folder/stop_crawler_desc $server:$dest_folder/
		#scp $source_folder/xsl/ebay_desc.xsl $server:$dest_folder/xsl/
		#scp $source_folder/xsl/ebay_general.xsl $server:$dest_folder/xsl/
		
		source_folder=/home/prod/twengaweb/scripts/special_feeds/ebay/v2
		dest_folder=/home/prod/twenga/tools/ebay
		#scp $source_folder/api/class.ebayshopping.php $server:$dest_folder/api/
		#scp $source_folder/class.ebay.php $server:$dest_folder/
		#scp $source_folder/conf.ebay.php $server:$dest_folder/
		#scp $source_folder/ebay.php $server:$dest_folder/
		#scp $source_folder/interface.ebayapi.php $server:$dest_folder/
		#scp $source_folder/run_ebay_feed $server:$dest_folder/
		#scp $source_folder/cMultiLog.php $server:$dest_folder/
		#scp $source_folder/cMultiThread.php $server:$dest_folder/
		
		source_folder=/home/prod/twengaweb/scripts/special_feeds/ebayClassifieds
		dest_folder=/home/prod/twenga/tools/ebayClassifieds
		#ssh $server "mkdir -p $dest_folder/xml; mkdir -p $dest_folder/log"
		#scp $source_folder/*.php $server:$dest_folder/
		#scp $source_folder/run_ebay_classifieds $server:$dest_folder/
		#scp $source_folder/run_ebay_classifieds_feed $server:$dest_folder/
		#scp $source_folder/cMultiLog.php $server:$dest_folder/
		#scp $source_folder/cMultiThread.php $server:$dest_folder/
		echo $OK_MSG
	done
	
elif [ ! -z ${OPTIONS[config]} ]; then
	if [ -z ${OPTIONS[config]} ]; then
		echo "Missing config_src argument!";
		exit 1;
	fi
	configsrc=${OPTIONS[config]}

	#Checking if configsrc exist
	configfolder="${FOLDER_MASTER}/$configsrc";
	configmaster="${configfolder}/.master";
	if [ ! -d "${configfolder}" ]; then
		echo "config_src is wrong!";
		exit 1;
	elif [ ! -r "${configmaster}" ]; then
		echo "Can't access .master file!";
		exit 1;
	fi

	if [ "$configsrc" = "IP" ]; then
		#rm -f ${FOLDER_MASTER}/$configsrc/etc/dev/hosts 2>/dev/null
		: #cat ${FOLDER_MASTER}/$configsrc/etc/hosts ${FOLDER_MASTER}/$configsrc/etc/dev/hosts.dev >> ${FOLDER_MASTER}/$configsrc/etc/dev/hosts
	fi

	#cat "${configmaster}" | grep -v "^#" > $TMPFILE
	while read line; do
		target=`echo $line | cut -d ':' -f1`
		file=`echo $line | cut -d ':' -f2`
		dirdst=`echo $line | cut -d ':' -f3`

		if [ "$target" = "ALL" ]; then
			mastertarget=${MASTER_TARGET_ALL}
		elif [ "$target" = "PROD" ]; then
			mastertarget=${MASTER_TARGET_PROD}
		elif [ "$target" = "DNS" ]; then
			mastertarget=${MASTER_TARGET_DNS}
		elif [ "$target" = "TEST" ]; then
			mastertarget=${MASTER_TARGET_TEST}
		elif [ "$target" = "DEV" ]; then
			mastertarget=${MASTER_TARGET_DEV}
		else
			echo "Undefined target $mastertarget!";
			#rm -f $TMPFILE
			exit 1;
		fi

		filetosync="${configfolder}/$file"
		filetosync_base=`basename ${filetosync}`
		filetosync_dst="${dirdst}/${filetosync_base}"

		echo -n "Synchronizing file $file...";
		shorthost=`hostname | cut -d '.' -f1`
		for server in $mastertarget; do
			if ( [ "$configsrc" = "BASH_TWENGA" ] && ( [ "$server" = "batch146" ] || [ "$server" = "batch147" ] ) ); then
				continue;
			fi
			echo -n "$server ";
			if [ "$server" = "$shorthost" ]; then
				: #rsync -e ssh --temp-dir=/tmp --inplace ${filetosync}  ${filetosync_dst}
			else
				: #rsync -e ssh --temp-dir=/tmp --inplace ${filetosync}  $server:${filetosync_dst}
			fi
			rc=$?
			
			if [ "$server" = "dv2" ]; then
				: #ssh $server "/home/prod/bin/bash_twenga_prod__fs3_to_dv2 /home/prod/.bash_twenga 2>/dev/null; /home/prod/bin/bash_twenga_dv2__prod_to_dev /home/prod/.bash_twenga /home/dev/.bash_twenga"
			elif [ "$server" = "dv3" ]; then
                : #ssh $server "/home/prod/bin/bash_twenga_prod__fs3_to_dv3 /home/prod/.bash_twenga 2>/dev/null"
            elif [ "$server" = "dv5" ]; then
				: #ssh $server "/home/prod/bin/bash_twenga_dv2__prod_to_dev /home/prod/.bash_twenga /home/dev/.bash_twenga"
			elif ( [ "$server" = "wbatch01" ] || [ "$server" = "batch264" ] ); then
                : #ssh $server "/home/prod/bash_twenga_prod_to_travel /home/prod/.bash_twenga 2>/dev/null"
            fi
			if [ "$rc" != "0" ]; then
				echo "error ($rc)";	
				LST_SRV_ERR="$LST_SRV_ERR $server";
				#exit 1;
			fi
		done
		echo "Server in error $LST_SRV_ERR";
		echo "done.";
	done < $TMPFILE

	if [ "$configsrc" = "BASH_TWENGA" ]; then
		for zserver in $SERVER_ADMIN; do
			#ssh $zserver "/home/prod/twenga/tools/build_config_db"
			if [ "$zserver" = "dv2" ]; then
				: #ssh dv2 "/home/prod/bin/bash_twenga_prod__fs3_to_dv2 /home/prod/.bash_twenga 2>/dev/null; /home/prod/bin/bash_twenga_dv2__prod_to_dev /home/prod/.bash_twenga /home/dev/.bash_twenga"
			fi
			if [ "$server" = "dv3" ]; then
                : #ssh dv3 "/home/prod/bin/bash_twenga_prod__fs3_to_dv3 /home/prod/.bash_twenga 2>/dev/null"
            elif ( [ "$server" = "wbatch01" ] || [ "$server" = "batch264" ] ); then
                : #ssh wbatch01 "/home/prod/bash_twenga_prod_to_travel /home/prod/.bash_twenga 2>/dev/null"
            fi
		done
	fi
	
elif [ ! -z ${OPTIONS[deployweb]} ]; then
	web_target=${OPTIONS[deployweb]}
	if [ "$web_target" = "" ]; then
		echo "Missing target argument!"
		exit 1;
	elif [ "$web_target" = "bct" ]; then
		web_servers="$SERVER_BCT_PHPWEB_ALL"
		folder_httpd="$FOLDER_HTTPD_BCT_DST"
	elif [ "$web_target" = "qa" ]; then
		web_servers="$SERVER_QA_PHPWEB_ALL"
		folder_httpd="$FOLDER_HTTPD_QA_DST"
	elif [ "$web_target" = "internal" ]; then
		web_servers="$SERVER_INT_PHPWEB_ALL"
	elif [ "$web_target" = "exclusive" ]; then
		web_servers="$SERVER_QA_EXCLUSIVE_PHPWEB_ALL"
		folder_httpd="$FOLDER_HTTPD_QA_EXCLUSIVE_DST"
	else
		web_servers="$SERVER_PHPWEB_ALL"
		folder_httpd=$FOLDER_HTTPD_DST
	fi
	
	if [ ! -z ${OPTIONS[target]} ]; then
		web_servers=${OPTIONS[target]}
	fi
	
	if [ -z ${OPTIONS[twbuild]} ]; then
		echo "Missing twbuild parameter!"
		exit 1;
	else
		twbuild=${OPTIONS[twbuild]}
	fi

	twengabuild_num=$twbuild
	# $RANDOM not defined ???
	TMPFILE_P="/home/tmp/.master_synchro_p-$RANDOM"
	rc=0
	echo `date "+$TIMESTAMP_FORMAT"`"Checking twengabuild consistency on each server..."
	for server in $web_servers; do
		#ssh $server "cd $folder_httpd/ && \
		#	test -d ${twengabuild_num}_twenga-common && \
		#	test -d ${twengabuild_num}_www.twenga && \
		#	test -d ${twengabuild_num}_hotel.twenga && \
		#	test -d smarty/${twengabuild_num}_web && \
		#	test -d smarty/${twengabuild_num}_common && \
		#	test -d smarty/${twengabuild_num}_travel && \
		#	echo TWOK" 1>$TMPFILE_P 2>&1
		echo -n `date "+$TIMESTAMP_FORMAT"`"Server $server " 
		if ( [ -s "$TMPFILE_P" ] && [ "`grep TWOK $TMPFILE_P`" != "" ] ); then
			echo "OK"
		else
			echo "KO!"
			rc=1
		fi
	done
	if [ "$rc" = "1" ]; then
		echo "Wrong TwengaBuild. Aborted" 1>&2
		exit 1
	fi

	confirmChange

	for server in $web_servers; do
		if [ "$web_target" = "web" ]; then
			echo -n `date "+$TIMESTAMP_FORMAT"`"Excluding $server from cluster... "
			#/home/prod/twenga/tools/wwwcluster $server Disable 1>&2 2>/dev/null
			if [ "$?" != "0" ]; then
				echo "Activation aborted." 1>&2
				#exit 1
			fi
			echo "done.";
		fi

		echo -n `date "+$TIMESTAMP_FORMAT"`"Activating web files ($twengabuild_num) to $server ...";
		#ssh $server "cd $folder_httpd/ && \
		#	rm -f twenga-common www.twenga hotel.twenga smarty/common smarty/web smarty/travel && \
		#	ln -sf ${twengabuild_num}_twenga-common twenga-common && \
		#	ln -sf ${twengabuild_num}_www.twenga www.twenga && \
		#	ln -sf ${twengabuild_num}_hotel.twenga hotel.twenga && \
		#	ln -sf ${twengabuild_num}_web smarty/web && \
		#	ln -sf ${twengabuild_num}_common smarty/common && \
		#	ln -sf ${twengabuild_num}_travel smarty/travel && \
		#	echo TWOK" 1>$TMPFILE_P 2>&1
		if ( [ -s "$TMPFILE_P" ] && [ "`grep TWOK $TMPFILE_P`" != "" ] ); then
			rc=0
		else
			rc=1
		fi
		echo "done. (rc=$rc)";
		#ssh $server -tt "sudo /root/apache_restart"
		echo -n `date "+$TIMESTAMP_FORMAT"`"Clearing smarty cache on server $server ...";
		#$HOME/twenga/tools/clear_cache $server smarty
		echo "done.";
		#curl --silent --retry 2 --retry-delay 2 --max-time 5 -d server=$server -d app=$web_target http://aai.twenga.com/push.php &

		if ( [ "$web_target" = "web" ] && [ "$target_server" = "" ] ); then
			echo -n `date "+$TIMESTAMP_FORMAT"`"Including $server in cluster... "
			#/home/prod/twenga/tools/wwwcluster $server Enable 1>&2 2>/dev/null &
			echo "done.";
		fi
	done

	if ( [ "$web_target" = "web" ] && [ "$target_server" = "" ] ); then
		# Mise en production classique sans specifier de target specifique
		# On peut donc synchroniser /home/httpd :
		: #/home/prod/twengaweb/tools/push_web web	
	fi
	
elif [ ! -z ${OPTIONS[web]} ]; then
	web_target=${OPTIONS[web]}
	docommon=0;
	web_servers="$SERVER_PHPWEB_ALL";
	folder_httpd=$FOLDER_HTTPD_DST
	TS=`date +'%Y%m%d%H%M%S'`

	if [ "$web_target" = "web" ]; then
		web_dest="www.twenga";
		additional_exclude_static="--exclude=*I --exclude=*Q --exclude=*B "
		twengabuild_ext="P";
	elif [ "$web_target" = "internal" ]; then
		web_dest="www.twenga";
		web_servers="$SERVER_INT_PHPWEB_ALL";
		additional_exclude_static="--exclude=*Q --exclude=*B --exclude=*P "
		twengabuild_ext="I";
	elif [ "$web_target" = "qa" ]; then
		web_servers="$SERVER_QA_PHPWEB_ALL";
		web_dest="www.twenga";
		folder_httpd="$FOLDER_HTTPD_QA_DST"
		twengabuild_ext="Q";
		additional_exclude_static="--exclude=*I --exclude=*B --exclude=*P "
	elif [ "$web_target" = "exclusive" ]; then
	    web_servers="$SERVER_QA_EXCLUSIVE_PHPWEB_ALL";
	    web_dest="exclusive";
	    folder_httpd="$FOLDER_HTTPD_QA_EXCLUSIVE_DST"
	    twengabuild_ext="Q";
	    additional_exclude_static="--exclude=*I --exclude=*B --exclude=*P "
	elif [ "$web_target" = "bct" ]; then
		web_servers="$SERVER_BCT_PHPWEB_ALL";
		web_dest="www.twenga";
		folder_httpd="$FOLDER_HTTPD_BCT_DST"
		twengabuild_ext="B";
		additional_exclude_static="--exclude=*I --exclude=*Q --exclude=*P "
	elif [ "$web_target" = "common" ]; then
		web_dest="$FOLDER_WEB_COMMON_DST";
	elif [ "$web_target" = "merchant" ]; then
		web_dest="merchant.twenga.com";
		web_servers="$SERVER_MERCHANT";
		additional_exclude="--exclude=images --exclude=js "
		docommon=1;
	elif [ "$web_target" = "adserver" ]; then
		web_dest="ads.twenga.com";
		web_servers="$SERVER_ADSERVER";
		additional_exclude=""
		docommon=1;
	elif ( [ "$web_target" = "extranet" ] || [ "$web_target" = "extranet-qa" ] ); then
		web_dest="extranet.twenga.com";
		if [ "$web_target" = "extranet" ]; then
			web_servers="$SERVER_EXTRANET";
		else
			web_servers="$SERVER_EXTRANET_QA";
		fi
		web_src="rts";
		additional_exclude="--exclude=parmcom.0* --exclude=pathfile --exclude=pdf/ --exclude=logo_merchant/"
		docommon=1;
	elif [ "$web_target" = "recrute" ]; then
		web_dest="recrute.twenga.fr";
		web_servers="$SERVER_PHPWEB_ALL $SERVER_INTERNAL_PHPWEB_ALL";
		additional_exclude=""
	elif [ "$web_target" = "image" ]; then
		web_src="web/html/images";
		web_dest="www.twenga/html/images";
		web_servers="$SERVER_STATIC_IMAGE_WEB"
		additional_exclude="--exclude=/sites"
	elif [ "$web_target" = "config" ]; then
		web_dest="www.twenga/inc/config-local";
		additional_exclude="--exclude=dynconfig*"
		web_servers="$SERVER_PHPWEB_ALL $SERVER_INTERNAL_PHPWEB_ALL";
	elif [ "$web_target" = "rewrite" ]; then
		web_src="../twenga/master/WEBREWRITE";
		web_dest="../../usr/local/apache2/conf/rewrite";
		additional_exclude=""
		web_servers="$SERVER_PHPWEB_ALL $SERVER_INTERNAL_PHPWEB_ALL";
	elif [ "$web_target" = "image2" ]; then
		web_src="web/html/images2";
		web_dest="www.twenga/html/images2";
		web_servers="$SERVER_STATIC_IMAGE_WEB"
		additional_exclude="--exclude=/sites"
	elif [ "$web_target" = "img" ]; then
		web_src="web/html/img";
		web_dest="www.twenga/html/img";
		web_servers="$SERVER_STATIC_IMAGE_WEB"
		additional_exclude="--exclude=/sites"
	elif [ "$web_target" = "admin" ] || [ "$web_target" = "fulladmin" ]; then
		web_dest="admin.twenga.com";
		web_servers="$SERVER_ADMIN"
		additional_exclude="--exclude=wiki* --exclude=.htpasswd --exclude=/logs"
		if [ "$web_target" = "fulladmin" ]; then
			docommon=1;
		fi
	fi

	if [ "$web_src" = "" ]; then
		web_src="$web_target"
	fi
	if [ "$web_src" = "fulladmin" ]; then
		web_src="admin"
	fi

	if [ ! -z ${OPTIONS[target]} ]; then
		web_servers=${OPTIONS[target]}
	fi

	if [ -z ${OPTIONS[verbose]} ]; then
		verbose="";
	else
		verbose="-v";
	fi

	confirmChange

	if ( [ "$web_target" != "rewrite" ] && [ "$web_target" != "config" ] && [ "$web_target" != "internal" ] && [ "$web_target" != "qa" ] && [ "$web_target" != "bct" ] && [ "$web_target" != "web" ] ); then
		echo -n "Backing-up web files ..."
		#cd $FOLDER_HTTPD_SRC
		if [ "$docommon" = "1" ]; then
			extratar="common"
		fi
		tarname=`echo ${web_src} | sed -e "s#/#_#g"`
		tar_target=$web_src
		#tar cvfj /home/prod/twenga/master/BACKUPS/${tarname}.$TS.tar.bz2 $tar_target $extratar 1>/dev/null
		echo "done"
	fi  

	if ( [ "$web_target" = "exclusive" ]  ); then
		##### ETAPE 1: Definition des variables #####
		twengabuild_num="$TS$twengabuild_ext-EXCLU" #TODO juste pour effacer apres le dev
		
		TMPFILE=/tmp/.master_synchro_$web_target-$twengabuild_num
		STATIC_ROOT_DIR=/home/prod/twengaweb/static/$web_target
        STATIC_DIR=$STATIC_ROOT_DIR/$twengabuild_num #Todo rajouter le web_target pour plus de secu

		# MASTER folder
        WWWCODE_FOLDER=/home/prod/twenga/master/WWWCODE/$twengabuild_num
		STATIC_FOLDER=$WWWCODE_FOLDER/STATIC
		#mkdir -p $WWWCODE_FOLDER 2>/dev/null
		#mkdir -p $STATIC_FOLDER 2>/dev/null

		echo -n "Deleting old static content...";
		for delfolder in "${STATIC_ROOT_DIR}/*$twengabuild_ext"; do
			: #rm -rf $delfolder
		done
		echo "done.";

		echo -n "Preparing tar files to deploy..."
        #cd $HOME/twengaweb/exclusive; tar cfz $WWWCODE_FOLDER/${twengabuild_num}_exclusive.tar.gz --exclude 'CVS' --exclude '*/\.#*' --exclude '.cvsignore' .
        if [ "$?" != "0" ]; then echo "Unable to prepare tar files. Aborted." 1>&2; exit 1; fi
		#cd $HOME/twengaweb/smarty/exclusive; tar cfz $WWWCODE_FOLDER/${twengabuild_num}_smarty_exclusive.tar.gz --exclude 'CVS' --exclude '*/\.#*' --exclude '.cvsignore' .
		if [ "$?" != "0" ]; then echo "Unable to prepare tar files. Aborted." 1>&2; exit 1; fi
     	#cd $HOME/twengaweb/smarty/common; tar cfz $WWWCODE_FOLDER/${twengabuild_num}_smarty_common.tar.gz --exclude 'CVS' --exclude '*/\.#*' --exclude '.cvsignore' .
        if [ "$?" != "0" ]; then echo "Unable to prepare tar files. Aborted." 1>&2; exit 1; fi
		echo "done.";

		echo "Preparing static content...";
		#/usr/local/bin/php $HOME/twenga/tools/combine/combine.php --from_template_dir "$HOME/twengaweb/smarty/web/v3/templates/" --to_dir "$STATIC_FOLDER" --twbuild "$twengabuild_num" --static_domain ".c4tw.net" 
		tar cfz $WWWCODE_FOLDER/statics.tar.gz $STATIC_FOLDER
		echo "done.";
	fi

	if ( [ "$web_target" = "internal" ] || [ "$web_target" = "qa" ] || [ "$web_target" = "bct" ] || [ "$web_target" = "web" ] ); then
		twengabuild_num="$TS$twengabuild_ext";
		#echo $twengabuild_num > "/home/prod/twengaweb/smarty/web/v3/templates/twengabuild.tpl"

		# $RANDOM not defined ???
		TMPFILE_TWENGA=/home/tmp/.master_synchro_twenga-web-$RANDOM
		TMPFILE_TRAVEL=/home/tmp/.master_synchro_twenga-travel-$RANDOM
		TWENGAPHP="/home/prod/twengaweb/web/inc/twenga.php"
        TRAVELPHP="/home/prod/twengaweb/travel/Web/Inc/twenga.php"

		#echo -e "<?php\ndefine('TWENGABUILD', '$twengabuild_num' );" > $TMPFILE_TWENGA
		#awk 'NR>1' $TWENGAPHP | grep -v "define('TWENGABUILD" >> $TMPFILE_TWENGA
		#mv $TMPFILE_TWENGA $TWENGAPHP
		
		#echo -e "<?php\ndefine('TWENGABUILD', '$twengabuild_num' );" > $TMPFILE_TRAVEL
        #awk 'NR>1' $TRAVELPHP | grep -v "define('TWENGABUILD" >> $TMPFILE_TRAVEL
		#mv $TMPFILE_TRAVEL $TRAVELPHP

		echo "Twengabuild# : $twengabuild_num";

		STATIC_ROOT_DIR=/home/prod/twengaweb/static
		STATIC_DIR=$STATIC_ROOT_DIR/$twengabuild_num

		if ( [ "$web_target" = "internal" ] || [ "$web_target" = "qa" ] || [ "$web_target" = "bct" ] ); then
			echo -n "Deleting old static content ...";
			for delfolder in "${STATIC_ROOT_DIR}/*$twengabuild_ext"; do
				: #rm -rf $delfolder
			done
			echo "done.";
		fi

		echo -n "Preparing tar files to deploy..."
		WWWCODE_FOLDER=/home/prod/twenga/master/WWWCODE/$twengabuild_num
		#mkdir -p $WWWCODE_FOLDER
		#cd $HOME/twengaweb/web; tar cfz $WWWCODE_FOLDER/${twengabuild_num}_www.twenga.tar.gz --exclude 'CVS' --exclude '*/\.#*' --exclude '.cvsignore' .
		if [ "$?" != "0" ]; then echo "Unable to prepare tar files. Aborted." 1>&2; exit 1; fi
        #cd $HOME/twengaweb/travel/Web; tar cfz $WWWCODE_FOLDER/${twengabuild_num}_hotel.twenga.tar.gz --exclude 'CVS' --exclude '*/\.#*' --exclude '.cvsignore' .			
		if [ "$?" != "0" ]; then echo "Unable to prepare tar files. Aborted." 1>&2; exit 1; fi
		#cd $HOME/twengaweb/common; tar cfz $WWWCODE_FOLDER/${twengabuild_num}_twenga-common.tar.gz --exclude 'CVS' --exclude '*/\.#*' --exclude '.cvsignore' .
		if [ "$?" != "0" ]; then echo "Unable to prepare tar files. Aborted." 1>&2; exit 1; fi
		#cd $HOME/twengaweb/smarty/web; tar cfz $WWWCODE_FOLDER/${twengabuild_num}_smarty_web.tar.gz --exclude 'CVS' --exclude '*/\.#*' --exclude '.cvsignore' .
		if [ "$?" != "0" ]; then echo "Unable to prepare tar files. Aborted." 1>&2; exit 1; fi
		#cd $HOME/twengaweb/smarty/web; tar cfz $WWWCODE_FOLDER/${twengabuild_num}_smarty_web.tar.gz --exclude 'CVS' --exclude '*/\.#*' --exclude '.cvsignore' .
		if [ "$?" != "0" ]; then echo "Unable to prepare tar files. Aborted." 1>&2; exit 1; fi
		#cd $HOME/twengaweb/smarty/travel; tar cfz $WWWCODE_FOLDER/${twengabuild_num}_smarty_travel.tar.gz --exclude 'CVS' --exclude '*/\.#*' --exclude '.cvsignore' .
		if [ "$?" != "0" ]; then echo "Unable to prepare tar files. Aborted." 1>&2; exit 1; fi
		#cd $HOME/twengaweb/smarty/common; tar cfz $WWWCODE_FOLDER/${twengabuild_num}_smarty_common.tar.gz --exclude 'CVS' --exclude '*/\.#*' --exclude '.cvsignore' .
		if [ "$?" != "0" ]; then echo "Unable to prepare tar files. Aborted." 1>&2; exit 1; fi
		echo "done.";

		dostatic=1
		if [ "$dostatic" = "1" ]; then
			if ( [ "$web_target" = "web" ] && [ "$target_server" = "" ] ); then
				# Mise en production classique sans specifier de target specifique
				# On peut donc supprimer les anciens folders et ajouter des liens symboliques
				# cd $STATIC_ROOT_DIR
				if [ "$?" != "0" ]; then
					echo "Fatal error: Unable to read $STATIC_ROOT_DIR folder" 1>&2
					exit 1;
				fi
				
				# On peut supprimer tous les folders Production sauf le 6 derniers
				#find . -name "*P" -type d | sort | head -n -6 > $TMPFILE_P
				#last_prod_folder=`find . -name "*P" -type d | sort | tail -1 | cut -d'/' -f2`
				#while read line; do
				#	rm -rf $line
				#	ln -sf $last_prod_folder $line	
				#done < $TMPFILE_P
				#rm -f $TMPFILE_P 2>/dev/null
			fi

			echo "Preparing static content ...";
			#mkdir $STATIC_DIR

			# Merchant Logos
			#mkdir -p $STATIC_DIR/img/sites 2>/dev/null
			#cd $STATIC_DIR/img/sites/
			#find /home/prod/twenga_files/merchant_logos/ -maxdepth 1 -name "*.png" -exec cp {} . \;

			# Brand Logos
			#mkdir -p $STATIC_DIR/img/brands 2>/dev/null
			#cd $STATIC_DIR/img/brands/
			#find /home/prod/twenga_files/brand_logos/ -maxdepth 1 -name "*.png" -exec cp {} . \;

			#cp -r /home/prod/twengaweb/web/html/img $STATIC_ROOT_DIR

			#mkdir -p $STATIC_DIR/travel 2>/dev/null
			#cp -r /home/prod/twengaweb/smarty/travel/js $STATIC_DIR/travel
			#cp -r /home/prod/twengaweb/smarty/travel/css $STATIC_DIR/travel

			#mkdir -p $STATIC_DIR/web  2>/dev/null
			#cp -r /home/prod/twengaweb/smarty/web/v3/js $STATIC_DIR/web
			#cp -r /home/prod/twengaweb/smarty/web/v3/css $STATIC_DIR/web #IMG IN !!

		    #mkdir -p $STATIC_DIR/webv4 2>/dev/null
			#cp -r /home/prod/twengaweb/smarty/web/v4/js $STATIC_DIR/webv4
            #cp -r /home/prod/twengaweb/smarty/web/v4/css $STATIC_DIR/webv4 #IMG IN !!
				
			for zsuffix in "" c cn; do
				echo -n "Combining files for subdomain $zsuffix..."
				#/usr/local/bin/php $HOME/twenga/tools/combine/combine.php --from_template_dir "$HOME/twengaweb/smarty/web/v3/templates/" --to_dir "$STATIC_DIR" --twbuild "$twengabuild_num" --static_domain "${zsuffix}.c4tw.net" --outpath "web"
				#/usr/local/bin/php $HOME/twenga/tools/combine/combine.php --from_template_dir "$HOME/twengaweb/smarty/web/v4/templates/" --to_dir "$STATIC_DIR" --twbuild "$twengabuild_num" --static_domain "${zsuffix}.c4tw.net" --outpath "webv4"
				#/usr/local/bin/php $HOME/twenga/tools/combine/combine.php --from_template_dir "$HOME/twengaweb/smarty/travel/templates/" --to_dir "$STATIC_DIR" --twbuild "$twengabuild_num" --static_domain "${zsuffix}.c4tw.net" --outpath "travel"
				echo "done.";
			done

			#cd $STATIC_ROOT_DIR
			if [ "$?" != "0" ]; then
				echo "Fatal error: Unable to read $STATIC_ROOT_DIR folder" 1>&2
				exit 1;
			fi
			#find . -name "CVS" -exec rm -rf {} \; 2>/dev/null
			echo "done.";

			echo -e `date "+$TIMESTAMP_FORMAT"`"Synchronizing static files ...";
			typeset -i j=0
			for server in $SERVER_STATIC_WEB_ALL; do
				echo -e `date "+$TIMESTAMP_FORMAT"`"Deploying static files to $server ...";
				#rsync -rp --cvs-exclude --exclude=.cvsignore $additional_exclude_static --delete-after -l -e ssh $verbose $FOLDER_HTTPD_SRC/static/ $server:$FOLDER_STATIC_DST/ &
				sleep 5 & # TMP
				RETVAL[$j]=$!
				j=$j+1
			done
			
			echo -n "Waiting for synchronization to complete...";
			typeset -i j=0
			for server in $SERVER_STATIC_WEB_ALL; do
				wait ${RETVAL[$j]}
				rc=$?
				if [ "$rc" != "0" ]; then
					echo `date "+$TIMESTAMP_FORMAT"`"$server error ($rc)";	
					exit 1;
				else
					echo `date "+$TIMESTAMP_FORMAT"`"$server done.";
				fi
				j=$j+1
			done
		fi

		#$HOME/twenga/tools/add_twengabuild $twengabuild_num
	fi

	for server in $web_servers; do
		echo -n `date "+$TIMESTAMP_FORMAT"`"Copying web files to $server ...";
		case $web_target in

		config)
				case $server in
				$SERVER_QA_PHPWEB_ALL)
						zfolder_httpd=$FOLDER_HTTPD_QA_DST
						;;
				$SERVER_BCT_PHPWEB_ALL)
						zfolder_httpd=$FOLDER_HTTPD_BCT_DST
						;;
				*)
						zfolder_httpd=$folder_httpd
						;;
				esac
				#rsync -rpl --cvs-exclude --exclude=.cvsignore $additional_exclude --delete-after -e ssh $verbose ${FOLDER_MASTER}/WEBCONFIG/config_* $server:$zfolder_httpd/config/www.twenga/config-local
				rc=$?
				;;

		internal|qa|bct|web)
				#scp -q $WWWCODE_FOLDER/${twengabuild_num}_* $server:/tmp
				folder_list="$folder_httpd/${twengabuild_num}_twenga-common $folder_httpd/${twengabuild_num}_www.twenga $folder_httpd/${twengabuild_num}_hotel.twenga $folder_httpd/smarty/${twengabuild_num}_web $folder_httpd/smarty/${twengabuild_num}_common $folder_httpd/smarty/${twengabuild_num}_travel"
				#ssh $server "rm -rf $folder_list && \
				#				mkdir -p $folder_list && \
				#				cd $folder_httpd/${twengabuild_num}_www.twenga && \
				#				tar xfz /tmp/${twengabuild_num}_www.twenga.tar.gz . && \
				#				rm -f /tmp/${twengabuild_num}_www.twenga.tar.gz && \
				#				cd $folder_httpd/${twengabuild_num}_hotel.twenga && \
				#				tar xfz /tmp/${twengabuild_num}_hotel.twenga.tar.gz . && \
				#				rm -f /tmp/${twengabuild_num}_hotel.twenga.tar.gz && \
				#				cd $folder_httpd/${twengabuild_num}_twenga-common && \
				#				tar xfz /tmp/${twengabuild_num}_twenga-common.tar.gz . && \
				#				rm -f /tmp/${twengabuild_num}_twenga-common.tar.gz && \
				#				cd $folder_httpd/smarty/${twengabuild_num}_travel && \
				#				tar xfz /tmp/${twengabuild_num}_smarty_travel.tar.gz . && \
				#				rm -f /tmp/${twengabuild_num}_smarty_travel.tar.gz && \
				#				cd $folder_httpd/smarty/${twengabuild_num}_web && \
				#				tar xfz /tmp/${twengabuild_num}_smarty_web.tar.gz . && \
				#				rm -f /tmp/${twengabuild_num}_smarty_web.tar.gz && \
				#				cd $folder_httpd/smarty/${twengabuild_num}_common && \
				#				tar xfz /tmp/${twengabuild_num}_smarty_common.tar.gz . && \
				#				rm -f /tmp/${twengabuild_num}_smarty_common.tar.gz && \
				#				ln -sf $folder_httpd/config/www.twenga/config.php $folder_httpd/${twengabuild_num}_www.twenga/inc/config.php && \
				#				ln -sf $folder_httpd/config/www.twenga/config-local $folder_httpd/${twengabuild_num}_www.twenga/inc/config-local && \
				#				ln -sf $folder_httpd/config/www.twenga/config_tracker.php $folder_httpd/${twengabuild_num}_www.twenga/inc/config_tracker.php && \
				#				ln -sf $folder_httpd/config/hotel.twenga/config.php $folder_httpd/${twengabuild_num}_hotel.twenga/Inc/config.php && \
				#				ln -sf $folder_httpd/config/www.twenga/config_tracker.php $folder_httpd/${twengabuild_num}_hotel.twenga/Inc/config_tracker.php && \
				#				ln -sf $folder_httpd/config/www.twenga/config-local $folder_httpd/${twengabuild_num}_hotel.twenga/Inc/config-local && \
				#				chmod 777 $folder_httpd/smarty/${twengabuild_num}_web/v3/templates_c && \
				#				chmod 777 $folder_httpd/smarty/${twengabuild_num}_web/v4/templates_c && \
				#				chmod 777 $folder_httpd/smarty/${twengabuild_num}_travel/templates_c && \
				#				echo TWOK" 1>$TMPFILE_P 2>/dev/null
				if ( [ -s "$TMPFILE_P" ] && [ "`grep TWOK $TMPFILE_P`" != "" ] ); then
					rc=0
				else
					rc=1
				fi
				;;

		exclusive)
			#ssh $server "mkdir $folder_httpd/${twengabuild_num}"
			#scp -q $WWWCODE_FOLDER/${twengabuild_num}_* $server:$folder_httpd/${twengabuild_num}
            : #ssh $server '
			#	cd '$folder_httpd/${twengabuild_num}' && \
			#	for option in *.tar.gz; do  dir=${option/".tar.gz"}; mkdir $dir; tar xfz $option -C $dir; rm $dir".tar.gz"; done;				 
			#	echo TWOK' 1>/tmp/toto2 2>/tmp/toto3
		;;

		extranet|extranet-qa)
				#rsync -rpl --cvs-exclude --exclude=.cvsignore --exclude=site_hr_upload --exclude=templates_c --exclude=cache --exclude=config* $additional_exclude --delete-after -e ssh $verbose $FOLDER_HTTPD_SRC/$web_src/ $server:$folder_httpd/$web_dest
				rc1=$?
				#rsync -rpl --cvs-exclude --exclude=.cvsignore --exclude=templates_c --exclude=cache --exclude=config* $additional_exclude --delete-after -e ssh $verbose $FOLDER_HTTPD_SRC/smarty/rts/ $server:$folder_httpd/smarty/extranet
				#curl --silent --retry 2 --retry-delay 2 --max-time 5 -d server=$server -d app="extranet" http://aai.twenga.com/push.php &
				rc2=$?
				if ( [ "$rc1" = "0" ] && [ "$rc2" = "0" ] ); then
					rc=0
				else
					rc=1
				fi
				;;
		
		admin|fulladmin)
				#rsync -rpl --cvs-exclude --exclude=.cvsignore --exclude=site_hr_upload --exclude=templates_c --exclude=cache --exclude=config* $additional_exclude --delete-after -e ssh $verbose $FOLDER_HTTPD_SRC/$web_src/ $server:$folder_httpd/$web_dest
				rc=0
				#curl --silent --retry 2 --retry-delay 2 --max-time 5 -d server=$server -d app="admin" http://aai.twenga.com/push.php &
				;;

		recrute)
				#rsync -rpl --cvs-exclude --exclude=.cvsignore --exclude=site_hr_upload --exclude=templates_c --exclude=cache --exclude=config* $additional_exclude --delete-after -e ssh $verbose $FOLDER_HTTPD_SRC/$web_src/ $server:$folder_httpd/$web_dest
				rc1=$?
				#rsync -rpl --cvs-exclude --exclude=.cvsignore --exclude=templates_c --exclude=cache --exclude=config* $additional_exclude --delete-after -e ssh $verbose $FOLDER_HTTPD_SRC/smarty/recrute/ $server:$folder_httpd/smarty/recrute
				rc2=$?
				if ( [ "$rc1" = "0" ] && [ "$rc2" = "0" ] ); then
					rc=0
				else
					rc=1
				fi
				#curl --silent --retry 2 --retry-delay 2 --max-time 5 -d server=$server -d app="recrute" http://aai.twenga.com/push.php &
				;;
		esac

		if [ "$rc" != "0" ]; then
			echo "error ($rc)";	
			exit 1;
		else
			echo "done.";
		fi
		if [ "$docommon" = "1" ]; then
			echo -n "Deploying common files to $server ...";
			#rsync -rp --cvs-exclude --exclude=.cvsignore --exclude=config* $additional_exclude --delete-after -e ssh $verbose $FOLDER_HTTPD_SRC/$FOLDER_WEB_COMMON_SRC/ $server:$folder_httpd/$FOLDER_WEB_COMMON_DST
			rc=$?
			if [ "$rc" != "0" ]; then
				echo "docommon:error ($rc)";	
				exit 1;
			else
				echo "done.";
			fi
		fi
	done

	if ( [ "$web_target" = "admin" ] || [ "$web_target" = "fulladmin" ] ); then
		: #/home/prod/twengaweb/tools/push_web admin
	fi
	echo "done.";

	if ( [ "$web_target" = "internal" ] || [ "$web_target" = "qa" ] || [ "$web_target" = "bct" ] ); then
		echo "Starting deployment of twbuild $twengabuild_num on $web_target environment..."
		#cd $current_pwd
		#$0 --autoconfirm --deployweb $web_target --twbuild $twengabuild_num
		echo "done."
	fi
fi
