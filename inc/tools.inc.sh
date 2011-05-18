#!/bin/bash

RES_COL=60
MOVE_TO_COL="echo -en \\033[${RES_COL}G"

# Map des colorations et en-têtes des messages du superviseur :
declare -A UI
UI=(
	[error.header]='\033[33m /!\ '
	[error.color]='\033[1;31m'
	[error_detail.color]='\033[1;31m'
	[info.header]='\033[1;36m (i) '
	[info.color]='\033[0;39m'
	[normal.color]='\033[0;39m'
	[subtitle.color]='\033[1;35m'
	[success.color]='\033[1;32m'
	[title.color]='\033[1;36m'
	[warning.header]='\033[33m /!\ '
	[warning.color]='\033[0;33m'
)

# Affiche une chaine alphanumérique sensitive de taille le premier paramètre.
#
# @param int $1 nb de caractères attendus
function getRandomString {
	M=`echo {{0..9},{a..z},{A..Z}} | sed 's/ //g'`
	n=1
	string=''
	while [ "$n" -le "$1" ]; do
		string="$string${M:$(($RANDOM%${#M})):1}"
		((n++))
	done
	echo "$string"
}

# Affiche la date avec les centièmes de secondes
function getDateWithCS {
	DATE_FORMAT=%Y-%m-%d\ %H:%M:%S
	NOW=`date "+$DATE_FORMAT"`
	CS=`date +%N | sed 's/^\([0-9]\{2\}\).*$/\1/'`
	echo "$NOW ${CS}cs"
}

# Affiche un message dans la couleur et avec l'en-tête correspondant au type spécifié.
#
# @param string $1 type de message à afficher : conditionne l'éventuelle en-tête et la couleur
# @ parma string $2 message à afficher
function displayMsg {
	TYPE=$1
	MSG=$2
	
	IS_DEFINED=`echo ${!UI[*]} | grep "\b$TYPE\b" | wc -l`
	[ $IS_DEFINED = 0 ] && echo "Unknown display type '$TYPE'!" >&2 && exit
	
	if [ ! -z "${UI[$TYPE'.header']}" ]; then
		echo -en "${UI[$TYPE'.header']}"
	fi
	echo -e "${UI[$TYPE'.color']}$MSG${UI['normal.color']}"
}

# Affiche un message provenant du flux de sortie du script exécuté.
# Met en valeur certains types de messages :
#   - les alertes sont des messages commençant par 'WARNING '
#
# @param string $1 message à afficher
function displayScriptMsg {
	MSG=$1
	if [ "${MSG:26:8}" = 'WARNING ' ]; then # la ligne commence par un timestamp suivi de ','
		displayMsg warning "$MSG"
	else
		displayMsg info "$MSG"
	fi
}
