#!/bin/sh
#set -x
TMPFILE="/tmp/.push_code.$RANDOM"
LOCKFILE="/tmp/.push_code.lock"

CONTROL_PATH=/home/prod/twenga_files/control

GIT_BIN=/usr/local/bin/git
export PATH=/usr/local/libexec/gitw/:$PATH

cd $CONTROL_PATH
FILES=`ls update_* 2>/dev/null`

CVS_RSH="/usr/bin/ssh-t"
export CVS_RSH

for zfile in $FILES
do
	LOCAL_LOCKFILE=${LOCKFILE}_$zfile
	#On ne fait rien si deja un lock (traitement en cours)
	if [ -s "$LOCAL_LOCKFILE" ]; then
		echo "Has lock..."
		continue;
	else
		echo "Locked !" > $LOCAL_LOCKFILE
	fi
	sleep 10
	zenv=`echo $zfile | cut -d '_' -f2`
	zenvori=$zenv
	email="`cat $CONTROL_PATH/$zfile | tr '\n' ';'`"
	# [DEBUG Geoffroy] Pkoi certains emails sont envoyes a "mcloix@twenga.com.qateam@twenga.com", avec pour sujet "[2/2] Update qa" ?
	cp -f "$CONTROL_PATH/$zfile" "$CONTROL_PATH/backup_${zfile}_"`date +'%Y%m%d%H%M%S'`
	# fin [DEBUG Geoffroy]

	if [ "$zenv" = "fulladmin" ]; then
		zenv="admin";
		docommon=1;
	elif [ "$zenv" = "fullmerchant" ]; then
		zenv="merchant";
		docommon=1;
	elif [ "$zenv" = "qa" ]; then
		#Update language files
		$HOME/twenga/tools/update_tr web 2>>$TMPFILE 1>&2
		scp /home/prod/twengaweb/web/languages/[0-9]*.php prod@dv2:/home/dev/web/languages/
		scp /home/prod/twengaweb/web/languages/[0-9]*.php prod@dv2:/data/dev-common-files/languages/web/
		cp /home/prod/twengaweb/web/languages/[0-9]*.php /home/httpd/www.twenga/languages/
		cp /home/prod/twengaweb/web/languages/[0-9]*.php /home/prod/twengaweb/travel/Web/Languages/
		$HOME/twenga/tools/update_ga 2>>$TMPFILE 1>&2
	elif [ "$zenv" = "extranet-qa" ]; then
		#Update language files
		$HOME/twenga/tools/update_tr rts 2>>$TMPFILE 1>&2
		scp /home/prod/twengaweb/rts/languages/[0-9]*.php wprod@dv2:/home/dev/web/rts/languages/
		scp /home/prod/twengaweb/rts/languages/[0-9]*.php prod@dv2:/data/dev-common-files/languages/rts/
	elif [ "$zenv" = "language" ]; then
		#Update language files
		$HOME/twenga/tools/update_tr web 2>>$TMPFILE 1>&2
        scp /home/prod/twengaweb/web/languages/[0-9]*.php prod@dv2:/data/dev-common-files/languages/web/
		scp /home/prod/twengaweb/web/languages/[0-9]*.php prod@dv2:/home/dev/web/languages/
		scp /home/prod/twengaweb/rts/languages/[0-9]*.php wprod@dv2:/home/dev/web/rts/languages/
    	scp /home/prod/twengaweb/rts/languages/[0-9]*.php prod@dv2:/data/dev-common-files/languages/rts/
		mail -s "Update $zenvori" "$email" < $TMPFILE
	elif [ "$zenv" = "webscripts" ]; then
		$HOME/twenga/tools/master_synchro --scripts webscripts 2>>$TMPFILE 1>&2
		mail -s "Update webscripts requested by $email" "matthieu.cloix@twenga.com" < $TMPFILE
	fi

	# WEB SERVER TPN
	if [ "$zenv" = "qawtpn" ]; then

		 echo "Working..." | mail -s "[1/2] Update $zenv at `date` " "$email" -b ",tony.caron@twenga.com"
		 cd $HOME/twengaweb/

		 rm -rf wtpn
		 $GIT_BIN clone git@git.twenga.com:distribution/wtpn.git 2>>$TMPFILE 1>&2
		 scp -r wtpn/* prod@www10://home/httpd/wtpn/  2>>$TMPFILE 1>&2

		 mail -s "[2/2] Update $zenv" "$email" -b "tony.caron@twenga.com" < $TMPFILE


		 rm -f $LOCAL_LOCKFILE
		 rm -f "$CONTROL_PATH/$zfile"
		 exit 1
	# MIRROR TPN
	elif [ "$zenv" = "qamtpn" ]; then

		echo "Working..." | mail -s "[1/2] Update $zenv at `date` " "$email" -b ",tony.caron@twenga.com"
		cd $HOME/twengaweb/

		rm -rf mtpn
		$GIT_BIN clone git@git.twenga.com:distribution/mtpn.git 2>>$TMPFILE 1>&2
		scp -r mtpn/* prod@www10://home/httpd/mtpn/  2>>$TMPFILE 1>&2

		mail -s "[2/2] Update $zenv" "$email" -b "tony.caron@twenga.com" < $TMPFILE

		rm -f $LOCAL_LOCKFILE
        rm -f "$CONTROL_PATH/$zfile"
		exit 1
	# PUBLISHER TP?
	elif [ "$zenv" = "qaptpn" ]; then

		echo "Working..." | mail -s "[1/2] Update $zenv at `date` " "$email" -b ",tony.caron@twenga.com"
		cd $HOME/twengaweb/

		rm -rf ptpn
		$GIT_BIN clone git@git.twenga.com:distribution/ptpn.git 2>>$TMPFILE 1>&2
		scp -r ptpn/* prod@www10://home/httpd/ptpn/  2>>$TMPFILE 1>&2

		mail -s "[2/2] Update $zenv" "$email" -b "tony.caron@twenga.com" < $TMPFILE

		rm -f $LOCAL_LOCKFILE
		rm -f "$CONTROL_PATH/$zfile"
		exit 1

	fi


	if [ "$zenv" = "qa" ]; then # On update uniquement en cas de qa (pas d'update pour internal et bct)
		cd $HOME/twengaweb/web 2>>$TMPFILE 1>&2
		rm -f $HOME/twengaweb/web/inc/twenga.php 2>/dev/null # Need to rm because this file is modified (twbuild)
		$GIT_BIN checkout $HOME/twengaweb/web/inc/twenga.php
		$GIT_BIN pull 2>>$TMPFILE 1>&2

		cd $HOME/twengaweb/travel/Web/ 2>>$TMPFILE 1>&2
        rm -f $HOME/twengaweb/travel/Web/Inc/twenga.php 2>/dev/null
        $GIT_BIN checkout $HOME/twengaweb/travel/Web/Inc/twenga.php
		$GIT_BIN pull 2>>$TMPFILE 1>&2

		cd $HOME/twengaweb/common 2>>$TMPFILE 1>&2
		cvs -q update -d 2>>$TMPFILE 1>&2

		cd $HOME/twengaweb/smarty/web 2>>$TMPFILE 1>&2
        $GIT_BIN pull 2>>$TMPFILE 1>&2

		cd $HOME/twengaweb/smarty/travel 2>>$TMPFILE 1>&2
        $GIT_BIN pull 2>>$TMPFILE 1>&2

	## To rename qa_exlusive
    elif [ "$zenv" = "exclusive" ]; then
        ## TODO gerer le twbuild
		cd $HOME/twengaweb/exclusive  2>>$TMPFILE 1>&2
		$GIT_BIN prod pull 2>>$TMPFILE 1>&2
		cd $HOME/twengaweb/smarty/exclusive
		$GIT_BIN prod pull 2>>$TMPFILE 1>&2
	fi


	if ( [ "$zenv" = "qa" ] || [ "$zenv" = "internal" ] || [ "$zenv" = "bct" ] || [ "$zenv" = "exclusive" ]); then
		cd $HOME/twenga/tools 2>>$TMPFILE 1>&2

		# We check that no files are in conflict
		if [ "`grep '^C' $TMPFILE`" = "" ]; then
			echo "Working..." | mail -s "[1/2] Update $zenv at `date` " "$email" -b "matthieu.cloix@twenga.com,tony.caron@twenga.com,maniiyalagan.nadarasa@twenga.com,laurent.macret@twenga.com"
			./master_synchro --autoconfirm --web $zenv 2>>$TMPFILE 1>&2
			mail -s "[2/2] Update $zenv" "$email" -b "matthieu.cloix@twenga.com,tony.caron@twenga.com,maniiyalagan.nadarasa@twenga.com,laurent.macret@twenga.com" < $TMPFILE
		else
			mail -s "[2/2] Update $zenv FAILED - Some files are in conflict" "$email" < $TMPFILE
		fi
	fi

	if ( [ "$zenv" = "aai" ] || [ "$zenv" = "admin" ] || [ "$zenv" = "recrute" ] || [ "$zenv" = "merchant" ] || [ "$zenv" = "adserver" ] || [ "$zenv" = "extranet" ] || [ "$zenv" = "extranet-qa" ] || [ "$zenv" = "exclusive_removed" ] ); then
		cd $HOME/twengaweb/$zenv 2>>$TMPFILE 1>&2
		case $zenv in
		exclusive)
			cd $HOME/twengaweb/exclusive  2>>$TMPFILE 1>&2
			$GIT_BIN prod pull 2>>$TMPFILE 1>&2
			cd $HOME/twengaweb/smarty/exclusive
			$GIT_BIN prod pull 2>>$TMPFILE 1>&2
                        ;;
		extranet-qa)
			cd $HOME/twengaweb/rts 2>>$TMPFILE 1>&2

			# Approche initiale :
			#$GIT_BIN prod pull 2>>$TMPFILE 1>&2
			#cd $HOME/twengaweb/smarty/rts
			#$GIT_BIN prod pull 2>>$TMPFILE 1>&2

			# Approche transitoire apres fusion des depots dev et integration :
			#$GIT_BIN pull 2>>$TMPFILE 1>&2

		    # Approche transitoire twgit : export de la release en cours :
            $GIT_BIN fetch --prune origin 2>>$TMPFILE 1>&2
            release_fullname="$($GIT_BIN branch -r --no-merged origin/stable | grep 'origin/release-' | sed 's/^[* ]*//' | tr '\n' ' ' | cut -d' ' -f1 | sed 's#^origin/##')"
            if [ -z "$release_fullname" ]; then
                echo 'No release found!' 2>>$TMPFILE 1>&2
                exit 1
            else
                echo "Selected release: '$release_fullname'" 2>>$TMPFILE 1>&2
                if $GIT_BIN branch --no-color | grep -q "$release_fullname"; then
                	echo "Branche locale detectee" 2>>$TMPFILE 1>&2
                    $GIT_BIN checkout $release_fullname 2>>$TMPFILE 1>&2
                    $GIT_BIN pull origin $release_fullname 2>>$TMPFILE 1>&2
                else
                	echo "Pas de branche locale detectee" 2>>$TMPFILE 1>&2
                    $GIT_BIN checkout -b $release_fullname origin/$release_fullname 2>>$TMPFILE 1>&2
                fi
            fi
			;;
		admin)
			$GIT_BIN prod pull 2>>$TMPFILE 1>&2
			;;
		aai)
			$GIT_BIN prod pull 2>>$TMPFILE 1>&2
			cd $HOME/twengaweb/smarty/$zenv
			$GIT_BIN prod pull 2>>$TMPFILE 1>&2
			;;
		recrute)
		    cd $HOME/twengaweb/$zenv
			cvs -q update -d 2>>$TMPFILE 1>&2
			cd $HOME/twengaweb/smarty/$zenv
			cvs -q update -d 2>>$TMPFILE 1>&2
			;;
		*)
			#cvs -q update -d 2>>$TMPFILE 1>&2
			echo "You haven't specified a env"
		esac

		if [ "$docommon" = "1" ]; then
			cd $HOME/twengaweb/common 2>>$TMPFILE 1>&2
			cvs -q update -d 2>>$TMPFILE 1>&2
		fi
		cd $HOME/twenga/tools 2>>$TMPFILE 1>&2
		if [ "$zenv" = "aai" ]; then
			./master_synchro --scripts $zenv 2>>$TMPFILE 1>&2
		elif [ "$zenvori" = "fulladmin" ]; then
			echo "Y" | ./master_synchro --web $zenvori 2>>$TMPFILE 1>&2
		else
			./master_synchro --autoconfirm --web $zenv 2>>$TMPFILE 1>&2
		fi
		mail -s "Update $zenvori" "$email" < $TMPFILE
	fi
	rm -f $LOCAL_LOCKFILE
	rm -f "$CONTROL_PATH/$zfile"
done

rm -f $TMPFILE 2>/dev/null
#!/bin/sh
set -x
TMPFILE="/tmp/.push_code.$RANDOM"
LOCKFILE="/tmp/.push_code.lock"

CONTROL_PATH=/home/prod/twenga_files/control

GIT_BIN=/usr/local/bin/git
export PATH=/usr/local/libexec/gitw/:$PATH

cd $CONTROL_PATH
FILES=`ls update_* 2>/dev/null`

CVS_RSH="/usr/bin/ssh-t"
export CVS_RSH

for zfile in $FILES; do
	LOCAL_LOCKFILE=${LOCKFILE}_$zfile
	#On ne fait rien si deja un lock (traitement en cours)
	if [ -s "$LOCAL_LOCKFILE" ]; then
		echo "Has lock..."
		continue;
	else
		echo "Locked !" > $LOCAL_LOCKFILE
	fi
	sleep 10
	zenv=`echo $zfile | cut -d '_' -f2`
	zenvori=$zenv
	email="`cat $CONTROL_PATH/$zfile | tr '\n' ';'`"
	if [ "$zenv" = "fulladmin" ]; then
		zenv="admin";
		docommon=1;
	elif [ "$zenv" = "fullmerchant" ]; then
		zenv="merchant";
		docommon=1;
	elif [ "$zenv" = "qa" ]; then
		#Update language files
		$HOME/twenga/tools/update_tr web 2>>$TMPFILE 1>&2
		scp /home/prod/twengaweb/web/languages/[0-9]*.php prod@dv2:/home/dev/web/languages/
		scp /home/prod/twengaweb/web/languages/[0-9]*.php prod@dv2:/data/dev-common-files/web/languages/
		cp /home/prod/twengaweb/web/languages/[0-9]*.php /home/httpd/www.twenga/languages/
		cp /home/prod/twengaweb/web/languages/[0-9]*.php /home/prod/twengaweb/travel/Web/Languages/
		$HOME/twenga/tools/update_ga 2>>$TMPFILE 1>&2
	elif [ "$zenv" = "extranet-qa" ]; then
		#Update language files
		$HOME/twenga/tools/update_tr rts 2>>$TMPFILE 1>&2
		scp /home/prod/twengaweb/rts/languages/[0-9]*.php wprod@dv2:/home/dev/web/rts/languages/
	elif [ "$zenv" = "language" ]; then
		#Update language files
		$HOME/twenga/tools/update_tr web 2>>$TMPFILE 1>&2
        scp /home/prod/twengaweb/web/languages/[0-9]*.php prod@dv2:/data/dev-common-files/web/languages/
		scp /home/prod/twengaweb/web/languages/[0-9]*.php prod@dv2:/home/dev/web/languages/
		mail -s "Update $zenvori" "$email" < $TMPFILE
	elif [ "$zenv" = "webscripts" ]; then
		$HOME/twenga/tools/master_synchro --scripts webscripts 2>>$TMPFILE 1>&2
		mail -s "Update webscripts requested by $email" "matthieu.cloix@twenga.com" < $TMPFILE
	fi

	if [ "$zenv" = "qa" ]; then # On update uniquement en cas de qa (pas d'update pour internal et bct)
		cd $HOME/twengaweb/web 2>>$TMPFILE 1>&2
		rm -f $HOME/twengaweb/web/inc/twenga.php 2>/dev/null # Need to rm because this file is modified (twbuild)
		$GIT_BIN checkout $HOME/twengaweb/web/inc/twenga.php
		$GIT_BIN pull 2>>$TMPFILE 1>&2

		cd $HOME/twengaweb/travel/Web/ 2>>$TMPFILE 1>&2
        rm -f $HOME/twengaweb/travel/Web/Inc/twenga.php 2>/dev/null
        $GIT_BIN checkout $HOME/twengaweb/travel/Web/Inc/twenga.php
		$GIT_BIN pull 2>>$TMPFILE 1>&2

		cd $HOME/twengaweb/common 2>>$TMPFILE 1>&2
		cvs -q update -d 2>>$TMPFILE 1>&2

		cd $HOME/twengaweb/smarty/web 2>>$TMPFILE 1>&2
        $GIT_BIN pull 2>>$TMPFILE 1>&2

		cd $HOME/twengaweb/smarty/travel 2>>$TMPFILE 1>&2
        $GIT_BIN pull 2>>$TMPFILE 1>&2
	fi

	## To rename qa_exlusive
    if [ "$zenv" = "exclusive" ]; then

        ## TODO gerer le twbuild
		cd $HOME/twengaweb/exclusive  2>>$TMPFILE 1>&2
		$GIT_BIN prod pull 2>>$TMPFILE 1>&2
		cd $HOME/twengaweb/smarty/exclusive
		$GIT_BIN prod pull 2>>$TMPFILE 1>&2
	fi


	if ( [ "$zenv" = "qa" ] || [ "$zenv" = "internal" ] || [ "$zenv" = "bct" ] || [ "$zenv" = "exclusive" ]); then
		cd $HOME/twenga/tools 2>>$TMPFILE 1>&2

		# We check that no files are in conflict
		if [ "`grep '^C' $TMPFILE`" = "" ]; then
			echo "Working..." | mail -s "[1/2] Update $zenv at `date` " "$email" -b "matthieu.cloix@twenga.com,tony.caron@twenga.com,maniiyalagan.nadarasa@twenga.com,laurent.macret@twenga.com"
			./master_synchro --autoconfirm --web $zenv 2>>$TMPFILE 1>&2
			mail -s "[2/2] Update $zenv" "$email" -b "matthieu.cloix@twenga.com,tony.caron@twenga.com,maniiyalagan.nadarasa@twenga.com,laurent.macret@twenga.com" < $TMPFILE
		else
			mail -s "[2/2] Update $zenv FAILED - Some files are in conflict" "$email" < $TMPFILE
		fi
	fi

	if ( [ "$zenv" = "aai" ] || [ "$zenv" = "admin" ] || [ "$zenv" = "recrute" ] || [ "$zenv" = "merchant" ] || [ "$zenv" = "adserver" ] || [ "$zenv" = "extranet" ] || [ "$zenv" = "extranet-qa" ] || [ "$zenv" = "exclusive_removed" ] ); then
		cd $HOME/twengaweb/$zenv 2>>$TMPFILE 1>&2
		case $zenv in
		exclusive)
			cd $HOME/twengaweb/exclusive  2>>$TMPFILE 1>&2
			$GIT_BIN prod pull 2>>$TMPFILE 1>&2
			cd $HOME/twengaweb/smarty/exclusive
			$GIT_BIN prod pull 2>>$TMPFILE 1>&2
                        ;;
		extranet-qa)
			cd $HOME/twengaweb/rts 2>>$TMPFILE 1>&2
			$GIT_BIN prod pull 2>>$TMPFILE 1>&2
			cd $HOME/twengaweb/smarty/rts
			$GIT_BIN prod pull 2>>$TMPFILE 1>&2
			;;
		admin)
			$GIT_BIN prod pull 2>>$TMPFILE 1>&2
			;;
		aai)
			$GIT_BIN prod pull 2>>$TMPFILE 1>&2
			cd $HOME/twengaweb/smarty/$zenv
			$GIT_BIN prod pull 2>>$TMPFILE 1>&2
			;;
		recrute)
		    cd $HOME/twengaweb/$zenv
			cvs -q update -d 2>>$TMPFILE 1>&2
			cd $HOME/twengaweb/smarty/$zenv
			cvs -q update -d 2>>$TMPFILE 1>&2
			;;
		*)
			#cvs -q update -d 2>>$TMPFILE 1>&2
			echo "You haven't specified a env"
		esac

		if [ "$docommon" = "1" ]; then
			cd $HOME/twengaweb/common 2>>$TMPFILE 1>&2
			cvs -q update -d 2>>$TMPFILE 1>&2
		fi
		cd $HOME/twenga/tools 2>>$TMPFILE 1>&2
		if [ "$zenv" = "aai" ]; then
			./master_synchro --scripts $zenv 2>>$TMPFILE 1>&2
		else
			if [ "$zenvori" = "fulladmin" ]; then
				echo "Y" | ./master_synchro --web $zenvori 2>>$TMPFILE 1>&2
			else
				./master_synchro --autoconfirm --web $zenv 2>>$TMPFILE 1>&2
			fi
		fi
		mail -s "Update $zenvori" "$email" < $TMPFILE
	fi
	rm -f $LOCAL_LOCKFILE
	rm -f "$CONTROL_PATH/$zfile"
done

rm -f $TMPFILE 2>/dev/null
