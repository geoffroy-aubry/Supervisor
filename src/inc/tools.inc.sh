#!/bin/bash

##
# Affiche une chaine alphanumérique sensitive de taille le premier paramètre.
#
# @param int $1 nb de caractères attendus
#
function getRandomString {
    local m=`echo {{0..9},{a..z},{A..Z}} | sed 's/ //g'`
    local n=1
    local string=''
    while [ "$n" -le "$1" ]; do
        string="$string${m:$(($RANDOM%${#m})):1}"
        ((n++))
    done
    echo $string
}

##
# Retourne dans RETVAL la date avec les centièmes de secondes au format 'YYYY-MM-DD HH:MM:SS CS\c\s'.
#
function getDateWithCS {
    local date_format=%Y-%m-%d\ %H:%M:%S
    local now=$(date "+$date_format")
    local cs=$(date +%N | sed 's/^\([0-9]\{2\}\).*$/\1/')
    RETVAL="$now ${cs}cs"
}

##
# Convertit une liste de valeurs en une ligne CSV au format suivant et l'affiche : "v1";"va""lue2";"v\'3"
#
# @param string $@ liste de valeurs
#
function convertList2CSV () {
    local row
    for v in $@; do
        v=${v//'"'/'""'}
        v=${v//"'"/"\\'"}
        row="$row;\"$v\""
    done
    echo ${row:1}
}

##
# Convertit une ligne CSV en un tableau indexé de valeurs et l'affiche.
# NOTE : Si une valeur qui n'est pas en fin de ligne est vide, l'utilisateur de la fonction se fera leurrer
# et les valeurs suivantes sembleront décalées d'une case vers la gauche.
#
# @param string $1 chaîne représentant une ligne CSV au format "v1";"va""lue2";"v\'3"
#
function convertCSV2List () {
    local csv="$1"
    local -a list

    local s="$csv"
    local value v bound

    while [ ! -z "$s" ]; do
        s="${s:1}"
        value="${s%%\"*}"
        s="${s:${#value}}"
        bound="${s:0:2}"

        while [ "$bound" = '""' ]; do
            s="${s:2}"
            v="${s%%\"*}"
            value="$value$bound$v"
            s="${s:${#v}}"
            bound="${s:0:2}"
        done

        if [ "$bound" = '";' ] || [ "$bound" = '"' ]; then
            s="${s:2}"
            value="$(echo $value | sed 's/""/"/g')"
            #[ -z "$value" ] && value='-'
            list=("${list[@]}" "$value")
        else
            echo "Bad CSV string: '$csv'!" >&2
            exit 1
        fi
    done

    echo ${list[@]}
}

##
# Lock script against parallel run.
# Example:
#     if ! getLock "$(basename $0)"; then
#         echo 'Another instance is running' >&2
#         exit
#     fi
#
# @param string $1 lock name
#
function getLock () {
    local name="$1"
    exec 9>"/tmp/$name.lock"
    if ! flock -x -n 9; then
        return 1
    fi
}
