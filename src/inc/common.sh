#!/bin/bash

# Includes :
. $INC_DIR/tools.sh
. $INC_DIR/mails.sh
. $INC_DIR/coloredUI.sh

##
# Initialisation du répertoire de logs :
#
# @uses $EXECUTION_ID, $LOG_DIR, $SCRIPT_ERROR_LOG_FILE, $SCRIPT_INFO_LOG_FILE, $SCRIPT_NAME
#
function initScriptLogs () {
    [ -d "$LOG_DIR" ] || mkdir -p "$LOG_DIR"
    SCRIPT_ERROR_LOG_FILE=$LOG_DIR/$(basename "$SCRIPT_NAME").$EXECUTION_ID.error.log
    touch $SCRIPT_ERROR_LOG_FILE
    SCRIPT_INFO_LOG_FILE=$LOG_DIR/$(basename "$SCRIPT_NAME").$EXECUTION_ID.info.log
    touch $SCRIPT_INFO_LOG_FILE
}

##
# S'assure de l'existence du script à superviser.
#
# @uses $SCRIPT_NAME, $EXECUTION_ID, $SUPERVISOR_INFO_LOG_FILE, $SCRIPT_PARAMETERS.
#
function checkScriptCalled () {
    local now
    if [ -z "$SCRIPT_NAME" ]; then
        getDateWithCS; now="$RETVAL"
        echo "$now;$EXECUTION_ID;NO SCRIPT;INIT ERROR" >> $SUPERVISOR_INFO_LOG_FILE
        echo "$now;${SUPERVISOR_PREFIX_MSG}ERROR" >> $SCRIPT_INFO_LOG_FILE
        [ "$SUPERVISOR_MAIL_SEND_ON_ERROR" -eq 1 ] && sendMailOnError
        die "Missing script name!" 65
    elif [ ! -f "$SCRIPT_NAME" ]; then
        getDateWithCS; now="$RETVAL"
        echo "$now;$EXECUTION_ID;$SCRIPT_NAME;INIT ERROR" >> $SUPERVISOR_INFO_LOG_FILE
        echo "$now;${SUPERVISOR_PREFIX_MSG}ERROR" >> $SCRIPT_INFO_LOG_FILE
        [ "$SUPERVISOR_MAIL_SEND_ON_ERROR" -eq 1 ] && sendMailOnError
        die "Script '<b>$SCRIPT_NAME</b>' not found!" 66
    elif [ ! -x "$SCRIPT_NAME" ]; then
        getDateWithCS; now="$RETVAL"
        echo "$now;$EXECUTION_ID;$SCRIPT_NAME;INIT ERROR" >> $SUPERVISOR_INFO_LOG_FILE
        echo "$now;${SUPERVISOR_PREFIX_MSG}ERROR" >> $SCRIPT_INFO_LOG_FILE
        [ "$SUPERVISOR_MAIL_SEND_ON_ERROR" -eq 1 ] && sendMailOnError
        die "Script '<b>$SCRIPT_NAME</b>' is not executable!" 67
    fi
}

##
# Initialisation des logs et notification du START.
#
# @uses $EXECUTION_ID, $SCRIPT_INFO_LOG_FILE, $SCRIPT_NAME, $SUPERVISOR_INFO_LOG_FILE, $SUPERVISOR_MAIL_SUBJECT_PREFIX
#
function initExecutionOfScript () {
    local script_name="${SCRIPT_NAME:-NO SCRIPT}"
    getDateWithCS; local datecs="$RETVAL"
    echo "$datecs;$EXECUTION_ID;$script_name;START" >> $SUPERVISOR_INFO_LOG_FILE
    echo "$datecs;${SUPERVISOR_PREFIX_MSG}START" >> $SCRIPT_INFO_LOG_FILE
    echo

    local msg="Starting script '<b>$script_name</b>' with id '<b>$EXECUTION_ID</b>'"
    CUI_displayMsg help "$msg"

    [ "$SUPERVISOR_MAIL_SEND_ON_INIT" -eq 1 ] && sendMailOnInit
}

##
# Appel du script passé en paramètres, en empilant le log d'erreurs à la suite des paramètres déjà fournis.
#
# @uses $EXECUTION_ID, $SCRIPT_ERROR_LOG_FILE, $SCRIPT_INFO_LOG_FILE, $SCRIPT_NAME, $SCRIPT_PARAMETERS
#
function executeScript () {
    local lock_failed=0
    if [ $SUPERVISOR_LOCK_SCRIPT -eq 1 ]; then
        if ! getLock "$(basename "$SCRIPT_NAME")"; then
            echo "${SUPERVISOR_PREFIX_MSG}Another instance of '<b>$(basename "$SCRIPT_NAME")</b>' is still running with supervisor!" >> $SCRIPT_ERROR_LOG_FILE
            lock_failed=1
            EXIT_CODE=69
        fi
    fi

    if [ $lock_failed -eq 0 ]; then
        local src_ifs="$IFS"
        local pipe="/tmp/fifo_${EXECUTION_ID}_$RANDOM"
        mkfifo -m 666 $pipe
        $SCRIPT_NAME $SCRIPT_PARAMETERS $EXECUTION_ID $SCRIPT_ERROR_LOG_FILE 2>>$SCRIPT_ERROR_LOG_FILE > $pipe &
        pid=$!

        local now
        while IFS='' read line; do
            IFS="$src_ifs"
            getDateWithCS; now="$RETVAL"
            echo "$now;$line" | sed -r 's:(\033|\x1B)\[[0-9;]*[mK]::ig' >> $SCRIPT_INFO_LOG_FILE
            displayScriptMsg "$now" "$line"
        done < $pipe
        rm -f $pipe

        wait $pid
        EXIT_CODE=$?
        if [ $EXIT_CODE -ne 0 ]; then
            echo "${SUPERVISOR_PREFIX_MSG}Exit code not null: $EXIT_CODE" >> $SCRIPT_ERROR_LOG_FILE
        elif [ -s $SCRIPT_ERROR_LOG_FILE ]; then
            EXIT_CODE=68
            echo "${SUPERVISOR_PREFIX_MSG}Exit code changed from 0 to $EXIT_CODE due to errors." >> $SCRIPT_ERROR_LOG_FILE
        fi
    fi
}

##
# Gestion des erreurs, affichage et envoi de mails après exécution du script supervisé.
#
# @uses $EXECUTION_ID, $SCRIPT_ERROR_LOG_FILE, $SCRIPT_INFO_LOG_FILE, $SCRIPT_NAME, $SUPERVISOR_INFO_LOG_FILE, $SUPERVISOR_MAIL_SUBJECT_PREFIX
#
function displayResult () {
    # if error:
    if [ -s $SCRIPT_ERROR_LOG_FILE ]; then
        local src_ifs="$IFS"

        getDateWithCS; local datecs="$RETVAL"
        echo "$datecs;$EXECUTION_ID;$SCRIPT_NAME;ERROR" >> $SUPERVISOR_INFO_LOG_FILE
        echo "$datecs;${SUPERVISOR_PREFIX_MSG}ERROR" >> $SCRIPT_INFO_LOG_FILE
        CUI_displayMsg error "Script '<b>$SCRIPT_NAME</b>' FAILED!"
        echo

        CUI_displayMsg help "Supervisor log file: $(dirname $SUPERVISOR_INFO_LOG_FILE)/<b>$(basename $SUPERVISOR_INFO_LOG_FILE)</b>:"
        cat $SUPERVISOR_INFO_LOG_FILE | grep ";$EXECUTION_ID;" | while IFS="" read line; do CUI_displayMsg info "$line"; done
        IFS="$src_ifs"
        echo

        CUI_displayMsg help "Execution log file: $(dirname $SCRIPT_INFO_LOG_FILE)/<b>$(basename $SCRIPT_INFO_LOG_FILE)</b>"
        CUI_displayMsg help "Error log file: $(dirname $SCRIPT_ERROR_LOG_FILE)/<b>$(basename $SCRIPT_ERROR_LOG_FILE)</b>:"
        cat $SCRIPT_ERROR_LOG_FILE | ( IFS="" read line; CUI_displayMsg error "$line"; while IFS="" read line; do CUI_displayMsg error_detail "$line"; done )
        IFS="$src_ifs"
        echo

        [ "$SUPERVISOR_MAIL_SEND_ON_ERROR" -eq 1 ] && sendMailOnError

    # else if warnings:
    elif [ "${#WARNING_MSG[*]}" -gt 0 ]; then
        local plural
        getDateWithCS; local datecs="$RETVAL"
        echo "$datecs;$EXECUTION_ID;$SCRIPT_NAME;WARNING" >> $SUPERVISOR_INFO_LOG_FILE
        echo "$datecs;${SUPERVISOR_PREFIX_MSG}WARNING" >> $SCRIPT_INFO_LOG_FILE
        [ "${#WARNING_MSG[*]}" -gt 1 ] && plural='S' || plural=''
        CUI_displayMsg warning "${#WARNING_MSG[*]} WARNING$plural"
        echo
        CUI_displayMsg help "Supervisor log file: $(dirname $SUPERVISOR_INFO_LOG_FILE)/<b>$(basename $SUPERVISOR_INFO_LOG_FILE)</b>"
        CUI_displayMsg help "Execution log file: $(dirname $SCRIPT_INFO_LOG_FILE)/<b>$(basename $SCRIPT_INFO_LOG_FILE)</b>"
        echo
        rm -f $SCRIPT_ERROR_LOG_FILE

        [ "$SUPERVISOR_MAIL_SEND_ON_WARNING" -eq 1 ] && sendMailOnWarning

    # else if successful:
    else
        getDateWithCS; local datecs="$RETVAL"
        echo "$datecs;$EXECUTION_ID;$SCRIPT_NAME;OK" >> $SUPERVISOR_INFO_LOG_FILE
        echo "$datecs;${SUPERVISOR_PREFIX_MSG}OK" >> $SCRIPT_INFO_LOG_FILE
        CUI_displayMsg ok 'OK'
        echo
        CUI_displayMsg help "Supervisor log file: $(dirname $SUPERVISOR_INFO_LOG_FILE)/<b>$(basename $SUPERVISOR_INFO_LOG_FILE)</b>"
        CUI_displayMsg help "Execution log file: $(dirname $SCRIPT_INFO_LOG_FILE)/<b>$(basename $SCRIPT_INFO_LOG_FILE)</b>"
        echo
        rm -f $SCRIPT_ERROR_LOG_FILE

        [ "$SUPERVISOR_MAIL_SEND_ON_SUCCESS" -eq 1 ] && sendMailOnSuccess
    fi
}

##
# Affiche un message provenant du flux de sortie du script exécuté.
# Met en valeur certains types de messages :
#   - les alertes sont des messages commençant par 'WARNING '
#
# @param string $1 date
# @param string $2 message à afficher
#
function displayScriptMsg {
    local date="$(CUI_displayMsg processing "$1, ")"
    local msg="$2"
    local msg_wo_tab msg_wo_color tmsg i

    # Trim:
    msg_wo_tab="$msg"
    while [ "${msg_wo_tab:0:${#SUPERVISOR_LOG_TABULATION}}" = "$SUPERVISOR_LOG_TABULATION" ]; do
        msg_wo_tab="${msg_wo_tab:${#SUPERVISOR_LOG_TABULATION}}"
    done
    msg_wo_color="${msg_wo_tab//[^[:print:]]\[+([0-9;])[mK]/}"
    tmsg="${msg_wo_color##+( )}"	# ltrim

    if [ "${tmsg:0:9}" = "$SUPERVISOR_WARNING_TAG" ]; then
        echo -n $date
        i=$(( ${#msg} - ${#msg_wo_tab} ))
        echo -en "${msg:0:$i}"
        CUI_displayMsg warning "$msg_wo_color"
        WARNING_MSG[${#WARNING_MSG[*]}]="$msg_wo_color"
    elif [ "${tmsg:0:7}" = "$SUPERVISOR_DEBUG_TAG" ]; then
        :
    elif [ "${tmsg:0:8}" = "$SUPERVISOR_MAILTO_TAG" ]; then
        SUPERVISOR_MAIL_TO="$SUPERVISOR_MAIL_TO ${tmsg:8}"
    elif [ "${tmsg:0:17}" = "$SUPERVISOR_MAIL_ATTACHMENT_TAG" ]; then
        SUPERVISOR_MAIL_ADD_ATTACHMENT="$SUPERVISOR_MAIL_ADD_ATTACHMENT ${tmsg:17}"
    else
        echo -n $date
        CUI_displayMsg normal "$msg"
    fi
}

function die () {
    local msg="$1"
    local exit_code="${2}"
    CUI_displayMsg error "$msg" >&2
    echo
    exit $exit_code
}

# N derniers jours concernés (pas forcément consécutifs)
function summarize () {
    local max_nb_days="$1"
    local actions="START;OK;WARNING;ERROR;INIT ERROR"
    local mail_msg=''
    local data=('Date' 'Script' 'Start' 'OK' 'Warning' 'Error' 'Init error')
    declare -A stats

    days="$(cat "$SUPERVISOR_INFO_LOG_FILE" | cut -d' ' -f1 | uniq | sort -r | tail -n$max_nb_days)"
    for day in $days; do
        scripts="$(cat "$SUPERVISOR_INFO_LOG_FILE" | grep "^$day " | grep ";START$" | cut -d';' -f3 | sort | uniq)"
        IFS=$'\n'
        for script in $scripts; do
            data+=("$day" "$script")
            IFS=';'
            for action in $actions; do
                stats[$action]="$(cat "$SUPERVISOR_INFO_LOG_FILE" | grep "^$day " | grep ";$action$" \
                    | cut -d';' -f3 | grep -- "$script" | wc -l)"
            done
            for action in $actions; do
                data+=("${stats[$action]}")
            done
        done
    done
    unset IFS

    printf '%s\t%s\t%s\t%s\t%s\t%s\t%s\n' "${data[@]}" \
        | column -t -s $'\t' \
        | awk '{if (NR == 1) print "\033[1;37m" $0 "\033[0m"; else print $0}'

    printf -v header '<tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>\n' "${data[@]:0:7}"
    printf -v rows '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n' "${data[@]:7}"
    mail_msg="<table border=1 cellspacing=0>$header$rows</table>"
    mail_subject="$SUPERVISOR_MAIL_SUBJECT_PREFIX > Summary"
    echo "$mail_msg" | $SUPERVISOR_MAIL_MUTT_CMD -e "$SUPERVISOR_MAIL_MUTT_CFG" -s "$mail_subject" -- $SUPERVISOR_MAIL_TO $MAIL_INSTIGATOR
}

function loadCustomizedMails () {
    if [ ! -z "$CUSTOMIZED_MAILS" ]; then
        if [ -f "$CUSTOMIZED_MAILS" ]; then
            . "$CUSTOMIZED_MAILS"
        else
            die "Customized mails file not found: '<b>$CUSTOMIZED_MAILS</b>'" 71
        fi
    fi
}

function doAction () {
    case "$ACTION" in
        archive)    archive $MIN_DAYS_BEFORE_ARCHIVING ;;
        monitor)    monitor ;;
        summarize)  summarize $SUMMARIZE_NB_DAYS ;;

        supervise)
            initScriptLogs
            initExecutionOfScript
            checkScriptCalled
            executeScript
            displayResult
            exit $EXIT_CODE
            ;;
    esac
}

##
# Monitoring du fichier de log d'erreur du superviseur.
#
# Une entrée crontab l'appelle chaque minute.
# Des mails sont envoyés à $SUPERVISOR_MAIL_TO si $SUPERVISOR_ERROR_LOG_FILE est non vide.
# À chaque nouvelle erreur, jusqu'à ce que le log d'erreur soit vidé ou qu'une nouvelle erreur survient :
#   - 10 mails séparés d'une minute
#   - puis 10 mails séparés de 10 minutes
#   - puis 10 mails séparés d'une heure
#   - puis des mails toutes les 6 heures
#
function monitor () {
    if [ -s "$SUPERVISOR_ERROR_LOG_FILE" ]; then
        [ ! -s "$SUPERVISOR_INFO_LOG_FILE" ] && touch $SUPERVISOR_INFO_LOG_FILE
        new_md5="$(md5sum $SUPERVISOR_ERROR_LOG_FILE | cut -d' ' -f1)"
        timestamp="$(date +\%s)"
        send_mail=0
        counter=1

        # Si le log de monitoring existe :
        if [ -s "$SUPERVISOR_MONITORING_LOG_FILE" ]; then
            read old_md5 counter timestamp_to_reach < <(cat "$SUPERVISOR_MONITORING_LOG_FILE")

            if [ "$old_md5" = "$new_md5" ]; then
                if [ "$timestamp" -ge "$timestamp_to_reach" ]; then
                    send_mail=1
                    let "counter++"

                    if [ "$counter" -ge "30" ]; then	# 1 mail toutes les 6 heures au bout de 10 heures :
                        let "timestamp+=6*60*60-2"
                    elif [ "$counter" -ge "20" ]; then	# 1 mail par heure au bout de ~2 heures :
                        let "timestamp+=60*60-2"
                    elif [ "$counter" -ge "10" ]; then	# 1 mail toutes les 10 minutes les ~2 premieres heures :
                        let "timestamp+=10*60-2"
                    else	# 1 mail par minute les 10 premieres minutes :
                        let "timestamp+=1*60-2"
                    fi
                else
                    send_mail=0
                fi
            else
                send_mail=1
                let "timestamp+=1*60-2"
                counter=1
            fi

        # Si le log de monitoring n'existe pas :
        else
            send_mail=1
            let "timestamp+=1*60-2"
            counter=1
        fi

        # Envoi du mail d'erreur critique :
        if [ "$send_mail" = "1" ]; then
            echo $new_md5 $counter $timestamp > "$SUPERVISOR_MONITORING_LOG_FILE"
            mail_subject="[SUPERVISOR MONITORING] CRITICAL ERROR"
            mail_msg="Supervisor generates errors while executing scripts.<br /><br />\
Server: $(hostname)<br />\
Supervisor log file: $(dirname $SUPERVISOR_INFO_LOG_FILE)/<b>$(basename $SUPERVISOR_INFO_LOG_FILE)</b><br />\
Supervisor error file: $(dirname $SUPERVISOR_ERROR_LOG_FILE)/<b>$(basename $SUPERVISOR_ERROR_LOG_FILE)</b><br /><br />\
Error:<br /><pre>$(cat $SUPERVISOR_ERROR_LOG_FILE)</pre>"
            tail -n 50 "$SUPERVISOR_INFO_LOG_FILE" | gzip > "$SUPERVISOR_INFO_LOG_FILE.gz"
            gzip -c "$SUPERVISOR_ERROR_LOG_FILE" > "$SUPERVISOR_ERROR_LOG_FILE.gz"
            sendMail "$mail_subject" "$mail_msg" "$SUPERVISOR_INFO_LOG_FILE.gz $SUPERVISOR_ERROR_LOG_FILE.gz"
            rm -f "$SUPERVISOR_INFO_LOG_FILE.gz"
            rm -f "$SUPERVISOR_ERROR_LOG_FILE.gz"
        fi
    fi
}

##
# Archive les logs de supervision en tar gzip quotidiens.
# Chaque jour compris entre J-$MIN_DAYS inclus et la date du plus vieux fichier de log non encore archivé inclus
# donnera lieu à un tar gzip si et seulement si il y a des logs appartenant
# à l'intervalle et si le tar gzip n'a pas déjà été généré.
#
# $ supervisor --archive=3
# CRON: 40 0 * * * root supervisor --archive=3
#
function archive () {
    local min_days="$1"
    local newest_date="$(date -d "- $min_days days" +%Y-%m-%d)"
    local oldest_date="$(ls -g --no-group "$LOG_DIR"/*.log --sort=time --reverse 2>/dev/null | head -n1 | awk '{print $4}')"
    local archiving_path files nb_files plural

    echo -e "\n\033[1;33mArchiving from \033[1;37m$oldest_date \033[1;33mto \033[1;37m$newest_date \033[1;33minclusive:"
    if [ "$(date -d "$oldest_date" +%s)" -gt "$(date -d "$newest_date" +%s)" ]; then
        echo -e '    \033[0mNo date to process…'
    else
        while [ "$(date -d "$oldest_date" +%s)" -le "$(date -d "$newest_date" +%s)" ]; do
            archiving_path="$(printf "$SUPERVISOR_ARCHIVING_PATTERN" "$oldest_date")"
            files="$(ls -g --no-group "$LOG_DIR"/*.log --sort=time --reverse | grep "$oldest_date" | awk '{print $6}' \
                | sed "s|^$LOG_DIR/||" \
                | grep -v \
                    -e "$(basename "$SUPERVISOR_INFO_LOG_FILE")" \
                    -e "$(basename "$SUPERVISOR_ERROR_LOG_FILE")" \
                    -e "$(basename "$SUPERVISOR_MONITORING_LOG_FILE")" \
            )"
            nb_files="$(echo "$files" | wc -l)"
            echo -en "    \033[0;35m$oldest_date\033[0m ⇒ "
            if [ ! -z "$files" ] && [ "$nb_files" -gt 0 ]; then
                if [ ! -e "$archiving_path" ]; then
                    [ "$nb_files" -gt 1 ] && plural='s' || plural=''
                    echo -e "\033[0;32marchiving \033[1;32m$nb_files \033[0;32mfile$plural into \033[1;32m$archiving_path"
                    echo "$files" \
                        | xargs tar --directory=$LOG_DIR -czvf "$archiving_path" \
                        | sed "s|^|$LOG_DIR/|" | xargs rm
                else
                    echo -e "\033[0;32malready archived into \033[1;32m$archiving_path"
                fi
            else
                echo -e "no file to archive"
            fi
            oldest_date="$(date -d "$oldest_date + 1 day" +%Y-%m-%d)";
        done
    fi
}
