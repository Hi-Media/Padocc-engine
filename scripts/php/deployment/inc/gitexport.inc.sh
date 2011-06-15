#!/bin/bash

repository="$1"
ref="$2"
srcdir="$3"
destdir="$4"

mkdir -p "$srcdir" && cd "$srcdir" || exit $?
if git rev-parse --git-dir 1>/dev/null 2>&1; then
	git fetch --prune origin || exit $?
else
	git clone "$repository" . || exit $?
fi

# 	if has "$TWGIT_ORIGIN/$feature_fullname" $(get_remote_branches); then BRANCH else TAG...

if git branch --no-color | grep -q "$branch"; then
	git checkout "$branch" || exit $?
else
	git checkout -b "$branch" "origin/$branch" || exit $?
fi

rsync -az --delete --delete-excluded --cvs-exclude --exclude=.cvsignore --stats -e ssh "$srcdir/"* "$destdir"