#!/bin/bash
# @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>

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
        echo "Git: reset local repository"
        git reset --hard 1>/dev/null || exit $?
    fi
    echo "Git: fetch '$reponame' repository"
    git fetch --quiet --prune $reponame 1>/dev/null || exit $?
else
    echo "Git: clone '$reponame'"
    rm -rf "$srcdir" && mkdir -p "$srcdir" && cd "$srcdir" && \
    git clone --quiet --origin "$reponame" "$repository" . 1>/dev/null || exit $?
fi

if git branch -r --no-color | grep -q "$reponame/$ref"; then
    if git branch --no-color | grep -q "$ref"; then
        echo "Git: checkout and update local branch '$ref'"
        git checkout --quiet "$ref" 1>/dev/null && git pull --quiet "$reponame" "$ref" 1>/dev/null || exit $?
    else
        echo "Git: checkout remote branch '$reponame/$ref'"
        git checkout --quiet -fb "$ref" "$reponame/$ref" 1>/dev/null || exit $?
    fi
elif git tag | grep -q "$ref"; then
    if git branch --no-color | grep -q "$ref"; then
        echo "Git: tag '$ref' already checkouted."
        git checkout --quiet "$ref" 1>/dev/null || exit $?
    else
        echo "Git: checkout tag '$ref'..."
        git checkout --quiet -fb "$ref" "$ref" 1>/dev/null || exit $?
    fi
else
    echo "Git: branch or tag '$ref' not found!" >&2 && exit 1
fi
