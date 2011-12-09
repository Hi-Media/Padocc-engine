#!/bin/bash
# @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
# Example: /bin/bash parallelize.inc.sh "123" "aai@aai-01" "ssh [] /bin/bash <<EOF\nls -l\nEOF\n"

uid="$1"; shift
hostnames="$1"; shift
pattern="$@"

if [ -z "$uid" ] || [ -z "$hostnames" ] || [ -z "$pattern" ]; then
    echo 'Usage: /bin/bash parallelize.inc.sh "uniqID" "host1 user@host2 ..." "cmd where [] will be replace by hosts"'
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
for hostname in $hostnames; do
    cmd=$(echo -e "$pattern" | sed -e "s/\[\]/$hostname/g")
    outPath="$(printf "$OUT_PATH_PATTERN" "$hostname")"
    errPath="$(printf "$ERR_PATH_PATTERN" "$hostname")"
    eval "$cmd" >$outPath 2>$errPath &
    pids="$pids $!"
done

results=''
for pid in $pids; do
    wait $pid
    results="$results $?"
done
results=($results)

i=0
for hostname in $hostnames; do
    outPath="$(printf "$OUT_PATH_PATTERN" "$hostname")"
    errPath="$(printf "$ERR_PATH_PATTERN" "$hostname")"
    outEndDate="$(date --reference=$outPath +%s)"
    errEndDate="$(date --reference=$errPath +%s)"
    [ "$outEndDate" -gt "$errEndDate" ] && endDate="$outEndDate" || endDate="$errEndDate"

    let "elapsedTime=endDate-startDate" || :
    echo "$endDate|$startDate => $elapsedTime"
    echo "---[$hostname]-->${results[$i]}|${elapsedTime}s"
    echo [OUT] && cat $outPath
    [ -s "$errPath" ] && echo [ERR] && cat $errPath
    let i++ || :
done
