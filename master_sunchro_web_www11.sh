#!/bin/sh TMPFILE=/home/tmp/.master_synchro_web-$RANDOM-$$ /home/prod/twenga/tools/master_synchro --web web | tee $TMPFILEtwbuild=`grep "Twengabuild#" $TMPFILE | cut -d ':' -f2 | awk '{print $1}'` #echo "Master synchro failed during install. Check logfile: $TMPFILE" TMPFILE2=/home/tmp/.master_synchro_deployweb-$twbuild /home/prod/twenga/tools/master_synchro --deployweb web --target www34 --twbuild $twbuild | tee $TMPFILE2/home/prod/twenga/tools/master_synchro --deployweb web --target www36 --twbuild $twbuild | tee $TMPFILE2/home/prod/twenga/tools/master_synchro --deployweb web --target www42 --twbuild $twbuild | tee $TMPFILE2/home/prod/twenga/tools/master_synchro --deployweb web --target www49 --twbuild $twbuild | tee $TMPFILE2/home/prod/twenga/tools/master_synchro --deployweb web --target www-01 --twbuild $twbuild | tee $TMPFILE2/home/prod/twenga/tools/master_synchro --deployweb web --target wwwtest1 --twbuild $twbuild | tee $TMPFILE2/home/prod/twenga/tools/master_synchro --deployweb web --target wwwtest2 --twbuild $twbuild | tee $TMPFILE2 mv $TMPFILE ${TMPFILE}_$twbuild