#!/usr/bin/env bash
# @author Geoffroy AUBRY <gaubry@hi-media.com>

repository="$1"
module="$2"
srcdir="$3"

if [ -z "$repository" ] || [ -z "$module" ] || [ -z "$srcdir" ]; then
    echo 'Missing parameters!' >&2
    exit 1
fi

if [ -d "$srcdir/$module" ]; then
    echo "CVS: reset local repository"
    cd "$srcdir" && rm -rf * || exit $?
elif [ ! -d "$srcdir" ]; then
    echo "CVS: create directory"
    mkdir -p "$srcdir" || exit $?
fi

echo "CVS: export '$repository'"
cd "$srcdir"
nb_files="$(cvs -q -d"$repository" export -DNOW "$module" | wc -l)"
echo "CVS: $nb_files files exported"
