#!/usr/bin/env bash

# Exemple d'appel :
# bash /path/to/cfg2ini.sh master_synchro.cfg master_synchro.cfg.ini

CFG_PATH="$1"
INI_PATH="$2"

# Mac OS X compatibility layer
uname="$(uname)"

if [ "$uname" = 'FreeBSD' ] || [ "$uname" = 'Darwin' ]; then
    PADOCC_OS='MacOSX'
else
    PADOCC_OS='Linux'
fi

##
# Execute sed with the specified regexp-extended pattern.
# Compatible Linux and Mac OS X.
#
# @param string $1 pattern using extended regular expressions
# @see $PADOCC_OS
#
function sedRegexpExtended () {
    local pattern="$1"
    if [ "$PADOCC_OS" = 'MacOSX' ]; then
        sed -E "$pattern";
    else
        sed -r "$pattern";
    fi
}

. $CFG_PATH

DEFINES=$(grep -E "^[a-zA-Z0-9_]+=(\"|\'|\$)" "$CFG_PATH" | sedRegexpExtended s/^\([a-zA-Z0-9_]+\).*/\\1/ | tr '\n\r' ' ')
echo "; INI file auto generated from $CFG_PATH" > $INI_PATH
echo "; $(date +'%Y-%m-%d %H:%M:%S')" >> $INI_PATH
for DEFINE in $DEFINES; do
    VALUE=$(eval echo \$$DEFINE | tr -d '\n\r')
    echo $DEFINE = \"${VALUE}\" >>$INI_PATH
done
