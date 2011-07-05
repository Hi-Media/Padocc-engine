#!/bin/bash

repository="$1"
module="$2"
srcdir="$3"

if [ -z "$repository" ] || [ -z "$module" ] || [ -z "$srcdir" ]; then
	echo 'Missing parameters!' >&2
	exit 1
fi

if [ -d "$srcdir/$module" ]; then
	cd "$srcdir" && rm -rf * || exit $?
elif [ ! -d "$srcdir" ]; then
	mkdir -p "$srcdir" || exit $?
fi

cd "$srcdir" && cvs -q -d"$repository" export -DNOW "$module" | wc -l
