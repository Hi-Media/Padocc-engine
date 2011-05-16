#!/bin/bash

# Includes :
. /home/gaubry/deployment/conf/config.inc.sh
. $INC_DIR/tools.inc.sh

# Variables :
ID=`getRandomString 8`
SCRIPT_NAME=$1
shift

# Initialisation du répertoire de logs :
[ -d "$LOG_DIR" ] || mkdir -p "$LOG_DIR"
ERROR_LOG_FILE=$LOG_DIR/$SCRIPT_NAME.$ID.error.log

# Validation du nom du script en paramètre :
if [ -z "$SCRIPT_NAME" ]; then
	displayMsg error "Missing script name."
	echo "`getDateWithCS`;$ID;NO SCRIPT;FAILED" >> $SUPERVISOR_LOG_FILE
	exit 1
else
	EXT=`echo ${SCRIPT_NAME##*.}`
	if [ $EXT = 'sh' ]; then 
		DIR=$SHELL_SCRIPTS_DIR
		CMD="$SHELL_SCRIPTS_DIR/$SCRIPT_NAME $*"
	else
		DIR=$PHP_SCRIPTS_DIR
		CMD="$PHP_CMD $PHP_SCRIPTS_DIR/$SCRIPT_NAME $*"
	fi
	
	if [ ! -f "$DIR/$SCRIPT_NAME" ]; then
		displayMsg error "Script not found."
		echo "`getDateWithCS`;$ID;$SCRIPT_NAME;SCRIPT NOT FOUND" >> $SUPERVISOR_LOG_FILE
		exit 2
	fi
fi

# Initialisation des logs :
> $ERROR_LOG_FILE
echo "`getDateWithCS`;$ID;$SCRIPT_NAME;START" >> $SUPERVISOR_LOG_FILE
displayMsg title "Starting '$SCRIPT_NAME' script with id '$ID'"

# Appel du script passé en paramètres, en empilant le log d'erreurs à la suite des paramètres déjà fournis :
$CMD $ERROR_LOG_FILE 2>>$ERROR_LOG_FILE | while read LINE ; do
	NOW="`getDateWithCS`"
	echo "$NOW;$ID;$SCRIPT_NAME;$LINE" >> $SUPERVISOR_LOG_FILE
	displayLog "$NOW, $LINE";
done

# Gestion des erreurs et affichage :
if [ -s $ERROR_LOG_FILE ]; then
	echo "`getDateWithCS`;$ID;$SCRIPT_NAME;ERROR" >> $SUPERVISOR_LOG_FILE
	displayMsg error "$SCRIPT_NAME failed!"
	echo ''
	
	displayMsg subtitle "`basename $SUPERVISOR_LOG_FILE` :"
	cat $SUPERVISOR_LOG_FILE | grep ";$ID;" | while read line; do displayMsg info "$line"; done
	echo ''
	
	displayMsg subtitle "`basename $ERROR_LOG_FILE` :"
	cat $ERROR_LOG_FILE | ( read line; displayMsg info "$line"; while read line; do displayMsg info "$line"; done )
	echo ''
else
	result=OK
	echo "`getDateWithCS`;$ID;$SCRIPT_NAME;$result" >> $SUPERVISOR_LOG_FILE
	displayMsg success "$result"
	echo ''
	rm -f $ERROR_LOG_FILE
fi