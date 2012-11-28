#!/bin/bash
# @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
# Example: /bin/bash parallelize.inc.sh "aai@aai-01" "ssh [] /bin/bash <<EOF\nls -l\nEOF\n"
# Example: time /bin/bash parallelize.inc.sh "1 2 3 4" "sleep []"

uid="$(date +'%Y%m%d%H%M%S')_$(printf '%05d' $RANDOM)"
values="$1"; shift
pattern="$@"

if [ -z "$values" ] || [ -z "$pattern" ]; then
    echo 'Usage: /bin/bash parallelize.inc.sh "host1 user@host2 ..." "cmd where [] will be replace by hosts"'
    echo 'Missing parameters!' >&2
    exit 1
fi

TMP_DIR='/tmp'
PREFIX_PATH_PATTERN="$TMP_DIR/parallel.logs.$uid."
OUT_PATH_PATTERN="$PREFIX_PATH_PATTERN%s.out"
ERR_PATH_PATTERN="$PREFIX_PATH_PATTERN%s.err"

rm -f "$PREFIX_PATH_PATTERN"*
startDate="$(date +%s)"

pids=''
for value in $values; do
    cmd=$(echo -e "$pattern" | sed -e "s/\[\]/$value/g")
    outPath="$(printf "$OUT_PATH_PATTERN" "$value")"
    errPath="$(printf "$ERR_PATH_PATTERN" "$value")"
    (eval "$cmd" >$outPath 2>$errPath && touch $outPath) &
    pids="$pids $!"
done

results=''
for pid in $pids; do
    wait $pid
    results="$results $?"
done
results=($results)

i=0
for value in $values; do
    outPath="$(printf "$OUT_PATH_PATTERN" "$value")"
    errPath="$(printf "$ERR_PATH_PATTERN" "$value")"
    outEndDate="$(date --reference=$outPath +%s)"
    errEndDate="$(date --reference=$errPath +%s)"
    [ "$outEndDate" -gt "$errEndDate" ] && endDate="$outEndDate" || endDate="$errEndDate"

    let "elapsedTime=endDate-startDate" || :
    echo "---[$value]-->${results[$i]}|${elapsedTime}s"
    echo [CMD] && echo -e "$pattern" | sed -e "s/\[\]/$value/g"
    echo [OUT] && [ -s "$outPath" ] && cat $outPath && echo
    echo [ERR] && [ -s "$errPath" ] && cat $errPath && echo
    echo ///
    let i++ || :
done

rm -f "$PREFIX_PATH_PATTERN"*
