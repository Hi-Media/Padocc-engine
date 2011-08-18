#!/bin/bash

# /bin/bash ~/deployment/lib/properties/cfg2ini.inc.sh $HOME/deployment/resources/master_synchro.cfg $HOME/deployment/resources/master_synchro.cfg.ini
# /bin/bash ~/deployment/lib/properties/cfg2ini.inc.sh $HOME/deployment/resources/tests_property_file.cfg $HOME/deployment/resources/tests_property_file.cfg.ini

CFG_PATH="$1"
INI_PATH="$2"

. $CFG_PATH

DEFINES=`grep -E "^[a-zA-Z0-9_]+=(\"|\'|\$)" "$CFG_PATH" | sed -r s/^\([a-zA-Z0-9_]+\).*/\\\\1/ | tr '\n' ' '`
echo "; INI file auto generated from $CFG_PATH" >$INI_PATH
echo "; $(date +'%Y-%m-%d %H:%M:%S')" >>$INI_PATH
for DEFINE in $DEFINES; do
    echo -n "$DEFINE = " >>$INI_PATH
    eval "echo \\\"\$$DEFINE\\\"" >>$INI_PATH
done