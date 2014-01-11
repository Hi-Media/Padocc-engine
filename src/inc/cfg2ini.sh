#!/usr/bin/env bash

# Exemple d'appel :
# bash /path/to/cfg2ini.sh master_synchro.cfg master_synchro.cfg.ini

CFG_PATH="$1"
INI_PATH="$2"

. $CFG_PATH

DEFINES=$(grep -E "^[a-zA-Z0-9_]+=(\"|\'|\$)" "$CFG_PATH" | sed -r s/^\([a-zA-Z0-9_]+\).*/\\1/ | tr '\n\r' ' ')
echo "; INI file auto generated from $CFG_PATH" > $INI_PATH
echo "; $(date +'%Y-%m-%d %H:%M:%S')" >> $INI_PATH
for DEFINE in $DEFINES; do
    VALUE=$(eval echo \$$DEFINE | tr -d '\n\r')
    echo $DEFINE = \"${VALUE}\" >>$INI_PATH
done
