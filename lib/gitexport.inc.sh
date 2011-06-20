#!/bin/bash

repository="$1"
reponame='origin'
ref="$2"
srcdir="$3"

if [ -z "$repository" ] || [ -z "$ref" ] || [ -z "$srcdir" ]; then
	echo 'Missing parameters!' >&2
	exit 1
fi

mkdir -p "$srcdir" && cd "$srcdir" || exit $?

current_repository="$(git remote -v 2>/dev/null | grep -E ^$reponame | head -n 1 | sed 's/^'"$reponame"'\s*//' | sed 's/\s*([^)]*)$//')"
if [ "$current_repository" = "$repository" ]; then
	if [ `git status --porcelain --ignore-submodules=all | wc -l` -ne 0 ]; then
		echo "Git reset..."
		git reset --hard 1>/dev/null || exit $?
	fi
	echo "Git fetch..."
	git fetch --prune $reponame 1>/dev/null || exit $?
else
	echo "Git clone..."
	rm -rf "$srcdir" && mkdir -p "$srcdir" && cd "$srcdir" && \
	git clone --origin "$reponame" "$repository" . 1>/dev/null || exit $?
fi

if git branch -r --no-color | grep -q "$reponame/$ref"; then
	if git branch --no-color | grep -q "$ref"; then
		echo "Checkout and update local branch '$ref'..."
		git checkout --quiet "$ref" 1>/dev/null && git pull --quiet "$reponame" "$ref" 1>/dev/null || exit $?
	else
		echo "Checkout remote branch '$reponame/$ref'..."
		git checkout --quiet -fb "$ref" "$reponame/$ref" 1>/dev/null || exit $?
	fi
elif git tag | grep -q "$ref"; then
	if git branch --no-color | grep -q "$ref"; then
		echo "Tag '$ref' already checkouted."
	else
		echo "Checkout tag '$ref'..."
		git checkout --quiet -fb "$ref" "$ref" 1>/dev/null || exit $?
	fi
else
	echo "Tag '$ref' not found!" >&2 && exit 1
fi
