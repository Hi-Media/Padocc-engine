#!/bin/bash

# chmod +x ~/deployment/scripts/php/deployment/inc/cfg2ini.inc.sh; ~/deployment/scripts/php/deployment/inc/cfg2ini.inc.sh $HOME/deployment/scripts/php/deployment/conf/master_synchro.cfg $HOME/deployment/scripts/php/deployment/conf/servers.ini

CFG_PATH=$1
INI_PATH=$2

. $CFG_PATH

DEFINES=`grep -E "^[a-zA-Z0-9_]+=(\"|\'|\$)" $CFG_PATH | sed -r s/^\([a-zA-Z0-9_]+\).*/\\\\1/ | tr '\n' ' '`
echo "; INI file auto generated from $CFG_PATH" >$INI_PATH
echo "; "`date +'%Y-%m-%d %H:%M:%S'` >>$INI_PATH
for DEFINE in $DEFINES; do
	echo -n "$DEFINE = " >>$INI_PATH
	eval "echo \\\"\$$DEFINE\\\"" >>$INI_PATH
done