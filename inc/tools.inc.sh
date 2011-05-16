#!/bin/bash

RES_COL=60
MOVE_TO_COL="echo -en \\033[${RES_COL}G"

SETCOLOR_SUCCESS="echo -en \\033[0;32m"
SETCOLOR_FAILURE="echo -en \\033[1;31m"
SETCOLOR_WARNING="echo -en \\033[0;33m"
SETCOLOR_NORMAL="echo -en \\033[0;39m"
SETCOLOR_TITLE="echo -en \\033[1;36m"
SETCOLOR_SUBTITLE="echo -en \\033[1;35m"
SETCOLOR_BOLD="echo -en \\033[0;1m"

declare -A UI
UI=(
	[error.header]='\033[33m /!\ '
	[error.color]='\033[1;31m'
	[info.header]='\033[35m (i) '
	[info.color]='\033[0;39m'
	[normal.color]='\033[0;39m'
	[subtitle.color]='\033[1;35m'
	[success.color]='\033[0;32m'
	[title.color]='\033[1;36m'
)

# Retourne une chaine alphanumérique sensitive de taille le premier paramètre.
#
# @param int $1 nb de caractères attendus
# @return string chaîne aléatoire de $1 caractères
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

# Retourne la date avec les centièmes de secondes
function getDateWithCS {
	DATE_FORMAT=%Y-%m-%d\ %H:%M:%S
	NOW=`date "+$DATE_FORMAT"`
	CS=`date +%N | sed 's/^\([0-9]\{2\}\).*$/\1/'`
	echo "$NOW ${CS}cs"
}

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

function displayLog {
	MSG=`echo -e "$1" \
		| sed 's/^\([^,]*, \)\(\[WARNING\]\)/\1\\\\033[33m\2\\\\033[1;33m/' \
		| sed 's/^\([^,]*, \)\(\[QUESTION\]\)/\1\\\\033[36m\2\\\\033[1;36m/'`
	FONT='00'
	if [ "$#" -eq 2 ]; then
		if [ $2 == 'title' ]; then
			FONT='1;35'
		elif [ $2 == 'header_error' ]; then
			FONT='0;31'
		elif [ $2 == 'error' ]; then
			FONT='1;31'
		fi		
	fi
	echo -e "\033[35m (i) \033[${FONT}m$MSG\033[00m"
}