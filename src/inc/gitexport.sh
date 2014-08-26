#!/usr/bin/env bash

##
# Script d'export de dépôt Git.
#
# Permet d'exporter une branche ou un tag d'un dépôt Git dans le répertoire de votre choix.
# Aucun pré-requis :
#   – Crée le répertoire au besoin,
#   – Puis toujours au besoin, clone, reset --hard, fetch ou checkout selon le contenu initial du répertoire spécifié.
# Ceci permet un export accéléré si vous ré-exploitez le même répertoire d'appels en appels pour un dépôt donné,
# même si vous spécifiez une autre branche ou tag.
# Un "git clean -dfx" est exécuté en fin de script si et seulement si le paramètre <must-clean> vaut 1.
#
# Usage : bash /path/to/git-export.sh <url-repo-git> <ref-to-export> <directory> [<must-clean>]
# Example :
#   bash ~/eclipse-workspace-3.8/himedia-common/lib/git/git-export.sh \
#       git@indefero.hi-media-techno.com:advertising-comtrack-tracker.git \
#       v2.0.3 \
#       /tmp/tracker_export
#
# @author Geoffroy AUBRY <gaubry@himedia.com>
#

repository="$1"
reponame='origin'
ref="$2"
srcdir="$3"
mustclean="$4"

if [ -z "$repository" ] || [ -z "$ref" ] || [ -z "$srcdir" ]; then
    echo 'Missing parameters!' >&2
    exit 1
fi

mkdir -p "$srcdir" && cd "$srcdir" || exit $?

# Injection of SSH options into git commands:
DIR=$(cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd)
SSH_KEY=${DIR}/../../conf/padocc-ssh
echo "ssh -i $SSH_KEY \$@" > /tmp/.git_ssh.$$
chmod +x /tmp/.git_ssh.$$
export GIT_SSH=/tmp/.git_ssh.$$

# remove temporary file on exit:
trap 'rm -f /tmp/.git_ssh.$$' 0

current_repository="$(git remote -v 2>/dev/null | grep -E ^$reponame | head -n 1 | awk '{print $2}')"
if [ "$current_repository" = "$repository" ]; then
    if [ $(git status --porcelain --ignore-submodules=all | wc -l) -ne 0 ]; then
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
    echo "Git: checkout tag '$ref'..."
    git checkout --quiet -f "refs/tags/$ref" 1>/dev/null || exit $?
else
    echo "Git: branch or tag '$ref' not found!" >&2 && exit 1
fi

if [ "$mustclean" = '1' ]; then
    echo 'Cleans the working tree...'
    git clean -dfx --quiet 1>/dev/null || exit $?
fi
