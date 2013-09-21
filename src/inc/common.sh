#!/bin/bash

# Includes :
. $INC_DIR/tools.sh
. $INC_DIR/coloredUI.sh

##
# Initialisation du répertoire de logs :
#
# @uses $EXECUTION_ID, $LOG_DIR, $SCRIPT_ERROR_LOG_FILE, $SCRIPT_INFO_LOG_FILE, $SCRIPT_NAME
#
function initScriptLogs () {
    [ -d "$LOG_DIR" ] || mkdir -p "$LOG_DIR"
    SCRIPT_ERROR_LOG_FILE=$LOG_DIR/$(basename "$SCRIPT_NAME").$EXECUTION_ID.error.log
    SCRIPT_INFO_LOG_FILE=$LOG_DIR/$(basename "$SCRIPT_NAME").$EXECUTION_ID.info.log
}

##
# S'assure de l'existence du script à superviser et le cas échéant construit la commande ($CMD) à exécuter.
#
# @uses $SCRIPT_NAME, $EXECUTION_ID, $SUPERVISOR_INFO_LOG_FILE, $SHELL_SCRIPTS_DIR, $PHP_SCRIPTS_DIR, $PHP_CMD, $SCRIPT_PARAMETERS.
#
function checkScriptCalled () {
    local now
    if [ -z "$SCRIPT_NAME" ]; then
        getDateWithCS; now="$RETVAL"
        echo "$now;$EXECUTION_ID;NO SCRIPT;INIT ERROR" >> $SUPERVISOR_INFO_LOG_FILE
        die "Missing script name!"
    else
        local directory
        local ext="${SCRIPT_NAME##*.}"
        local script_path
        if [ "$ext" = 'sh' ]; then
            [ "${SCRIPT_NAME:0:1}" = '/' ] && script_path="$SCRIPT_NAME" || script_path="$SHELL_SCRIPTS_DIR/$SCRIPT_NAME"
            CMD="$BASH_CMD $script_path $SCRIPT_PARAMETERS"
        elif [ "$ext" = 'php' ]; then
            [ "${SCRIPT_NAME:0:1}" = '/' ] && script_path="$SCRIPT_NAME" || script_path="$PHP_SCRIPTS_DIR/$SCRIPT_NAME"
            CMD="$PHP_CMD $script_path $SCRIPT_PARAMETERS"
        else
            getDateWithCS; now="$RETVAL"
            echo "$now;$EXECUTION_ID;$SCRIPT_NAME;INIT ERROR" >> $SUPERVISOR_INFO_LOG_FILE
            die "Extension of script '$SCRIPT_NAME' not handled!"
        fi

        if [ ! -f "$script_path" ]; then
            getDateWithCS; now="$RETVAL"
            echo "$now;$EXECUTION_ID;$SCRIPT_NAME;INIT ERROR" >> $SUPERVISOR_INFO_LOG_FILE
            die "Script '$script_path' not found!"
        fi
    fi
}

##
# Affiche le résultat de l'exécution de la ou les requêtes SQL spécifiées, en mode batch.
#
# @uses $DB_SUPERVISOR_DB, $DB_SUPERVISOR_HOST, $DB_SUPERVISOR_PASSWORD, $DB_SUPERVISOR_USERNAME
# @param string $1-$n contenu de la ou les requêtes SQL à jouer
#
function execQuery () {
    local query="$@"
    mysql -u $DB_SUPERVISOR_USERNAME $DB_SUPERVISOR_DB -h $DB_SUPERVISOR_HOST \
        --password=$DB_SUPERVISOR_PASSWORD --skip-column-names --batch -e "$query"
}

##
# Affiche le résultat de l'exécution de la ou les requêtes SQL spécifiées, en mode batch
# et converti au format CSV (" et ;).
#
# @param string $1-$n contenu de la ou les requêtes SQL à jouer
#
function execQuery2CSV () {
    execQuery "$@" | sed -r 's/"/""/g' | sed -r 's/\t/";"/g' |sed -r 's/^|$/"/g'
}

function checkBeforeAdd () {
    local script_name="$1"
    local mask="$2"
    execQuery " \
        SELECT COUNT(*) AS NB \
        FROM SUPERVISOR_DEMAND \
        WHERE SUPERVISOR_DEMAND_STATUS_ID IN ($SUPERVISOR_STATUS_WAITING, $SUPERVISOR_STATUS_IN_PROGRESS) \
        AND SCRIPT_NAME='$script_name' \
        AND PARAMETERS LIKE '$mask'"
}

##
# Notifie en DB du démarrage de l'exécution d'un script supervisé,
# et affiche le nombre de lignes modifiées qui doit être égal à 1.
#
function startScript () {
    local date="$(date +'%Y-%m-%d %H:%M:%S')"
    if [ ! -z "$DB_SUPERVISOR_DB"]; then
        execQuery " \
            UPDATE SUPERVISOR_DEMAND SET \
                EXECUTION_ID='$EXECUTION_ID', \
                DATE_START='$date', \
                SUPERVISOR_DEMAND_STATUS_ID=$SUPERVISOR_STATUS_IN_PROGRESS \
            WHERE SUPERVISOR_DEMAND_ID=$SUPERVISOR_ID \
            LIMIT 1; \
            SELECT ROW_COUNT()"
    else
        echo 1
    fi
}

##
# Notifie en DB de la fin d'exécution d'un script supervisé,
# et affiche le nombre de lignes modifiées qui doit être égal à 1.
#
# @param int $1 statut de fin d'exécution du script.
#
function endScript () {
    local status="$1"
    local date="$(date +'%Y-%m-%d %H:%M:%S')"
    if [ ! -z "$DB_SUPERVISOR_DB"]; then
        execQuery " \
            UPDATE SUPERVISOR_DEMAND SET \
                DATE_END='$date', \
                SUPERVISOR_DEMAND_STATUS_ID=$status \
             WHERE SUPERVISOR_DEMAND_ID=$SUPERVISOR_ID
            LIMIT 1; \
            SELECT ROW_COUNT()"
    else
        echo 1
    fi
}

##
# Initialisation des logs et notification du START.
#
# @uses $EXECUTION_ID, $SCRIPT_INFO_LOG_FILE, $SCRIPT_NAME, $SUPERVISOR_INFO_LOG_FILE, $SUPERVISOR_MAIL_SUBJECT_PREFIX
#
function initExecutionOfScript () {
    [ ! "$(startScript)" -eq "1" ] && die 'Start failed!'

    getDateWithCS; local datecs="$RETVAL"
    echo "$datecs;$EXECUTION_ID;$SCRIPT_NAME;START" >> $SUPERVISOR_INFO_LOG_FILE
    echo "$datecs;START" >> $SCRIPT_INFO_LOG_FILE
    echo

    local msg="Starting script '<b>$SCRIPT_NAME</b>' with id '<b>$EXECUTION_ID</b>'"
    CUI_displayMsg help "$msg"

    # start mail:
    if [ "$SUPERVISOR_MAIL_SEND_ON_INIT" -eq 1 ]; then
        local mail_subject="$SUPERVISOR_MAIL_SUBJECT_PREFIX ${SCRIPT_NAME##*/} > Starting ($EXECUTION_ID)"
        local mail_msg="<h3>$msg.</h3>Instigator: "
        [ -z "$INSTIGATOR_EMAIL" ] && mail_msg="$mail_msg<i>not specified</i>" || mail_msg="$mail_msg$INSTIGATOR_EMAIL"
        mail_msg="$mail_msg<br /><br />Executed command: <pre>\$ $CMD $EXECUTION_ID $SCRIPT_ERROR_LOG_FILE 2>>$SCRIPT_ERROR_LOG_FILE</pre><br />Server: $(hostname)<br />You will receive another email at the end of execution."
        echo "$mail_msg" | mutt -e "$SUPERVISOR_MAIL_MUTT_CMDS" -s "$mail_subject" -- $SUPERVISOR_MAIL_TO $INSTIGATOR_EMAIL
    fi
}

##
# Appel du script passé en paramètres, en empilant le log d'erreurs à la suite des paramètres déjà fournis.
#
# @uses $CMD, $EXECUTION_ID, $SCRIPT_ERROR_LOG_FILE, $SCRIPT_INFO_LOG_FILE, $SCRIPT_NAME
#
function executeScript () {
    local lock_failed=0
    if [ $SUPERVISOR_LOCK_SCRIPT -eq 1 ]; then
        if ! getLock "$(basename "$SCRIPT_NAME")"; then
            echo "Another instance of '<b>$(basename "$SCRIPT_NAME")</b>' is still running with supervisor!" >> $SCRIPT_ERROR_LOG_FILE
            lock_failed=1
        fi
    fi

    if [ $lock_failed -eq 0 ]; then
        local src_ifs="$IFS"
        local pipe="/tmp/fifo_${EXECUTION_ID}_$RANDOM"
        mkfifo -m 666 $pipe
        $CMD $EXECUTION_ID $SCRIPT_ERROR_LOG_FILE 2>>$SCRIPT_ERROR_LOG_FILE > $pipe &
        pid=$!

        local now
        while IFS='' read line; do
            IFS="$src_ifs"
            getDateWithCS; now="$RETVAL"
            #log_line="$now;$line"
            #echo "${log_line//[^[:print:]]\[+([0-9;])[mK]/}" >> $SCRIPT_INFO_LOG_FILE
            echo "$now;$line" | sed -r 's:(\033|\x1B)\[[0-9;]*[mK]::ig' >> $SCRIPT_INFO_LOG_FILE
            displayScriptMsg "$now" "$line"
        done < $pipe
        rm -f $pipe

        wait $pid
        status=$?
        [ $status -ne 0 ] && echo "Exit code not null: $status" >>$SCRIPT_ERROR_LOG_FILE
    fi
}

##
# Gestion des erreurs, affichage et envoi de mails après exécution du script supervisé.
#
# @uses $EXECUTION_ID, $SCRIPT_ERROR_LOG_FILE, $SCRIPT_INFO_LOG_FILE, $SCRIPT_NAME, $SUPERVISOR_INFO_LOG_FILE, $SUPERVISOR_MAIL_SUBJECT_PREFIX
#
function displayResult () {
    local mail_subject_prefix="$SUPERVISOR_MAIL_SUBJECT_PREFIX ${SCRIPT_NAME##*/} >"
    local date="$(date +'%Y-%m-%d %H:%M:%S')"
    local plural

    SUPERVISOR_MAIL_ADD_ATTACHMENT="$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz $SCRIPT_INFO_LOG_FILE.gz $SUPERVISOR_MAIL_ADD_ATTACHMENT"

    # if error:
    if [ -s $SCRIPT_ERROR_LOG_FILE ]; then
        [ ! "$(endScript $SUPERVISOR_STATUS_END_ERROR)" -eq "1" ] && die 'End failed!'

        local src_ifs="$IFS"
        getDateWithCS; local datecs="$RETVAL"
        echo "$datecs;$EXECUTION_ID;$SCRIPT_NAME;ERROR" >> $SUPERVISOR_INFO_LOG_FILE
        echo "$datecs;ERROR" >> $SCRIPT_INFO_LOG_FILE
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

        # error mail:
        if [ "$SUPERVISOR_MAIL_SEND_ON_ERROR" -eq 1 ]; then
            local mail_subject="$mail_subject_prefix ERROR ($EXECUTION_ID)"
            local mail_msg="<h3 style=\"color: #FF0000\">Error in execution of script '<b>$SCRIPT_NAME</b>' with id '<b>$EXECUTION_ID</b>'.</h3>Instigator: "
            [ -z "$INSTIGATOR_EMAIL" ] && mail_msg="$mail_msg<i>not specified</i>" || mail_msg="$mail_msg$INSTIGATOR_EMAIL"
            mail_msg="$mail_msg<br /><br />\
Executed command: <pre>\$ $CMD $EXECUTION_ID $SCRIPT_ERROR_LOG_FILE 2>>$SCRIPT_ERROR_LOG_FILE</pre><br />\
Server: $(hostname)<br />\
Supervisor log file: $(dirname $SUPERVISOR_INFO_LOG_FILE)/<b>$(basename $SUPERVISOR_INFO_LOG_FILE)</b><br />\
Execution log file: $(dirname $SCRIPT_INFO_LOG_FILE)/<b>$(basename $SCRIPT_INFO_LOG_FILE)</b><br />\
Error log file: $(dirname $SCRIPT_ERROR_LOG_FILE)/<b>$(basename $SCRIPT_ERROR_LOG_FILE)</b><br /><br />\
<b style=\"color: #FF0000\">Error:</b><br /><pre>$(cat $SCRIPT_ERROR_LOG_FILE)</pre>"
            tail -n 50 "$SUPERVISOR_INFO_LOG_FILE" | gzip > "$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz"
            gzip -c "$SCRIPT_INFO_LOG_FILE" > "$SCRIPT_INFO_LOG_FILE.gz"
            gzip -c "$SCRIPT_ERROR_LOG_FILE" > "$SCRIPT_ERROR_LOG_FILE.gz"
            SUPERVISOR_MAIL_ADD_ATTACHMENT="$SUPERVISOR_MAIL_ADD_ATTACHMENT $SCRIPT_ERROR_LOG_FILE.gz"
            echo "$mail_msg" | mutt -e "$SUPERVISOR_MAIL_MUTT_CMDS" -s "$mail_subject" -a $SUPERVISOR_MAIL_ADD_ATTACHMENT -- $SUPERVISOR_MAIL_TO $INSTIGATOR_EMAIL
            rm -f "$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz"
            rm -f "$SCRIPT_INFO_LOG_FILE.gz"
            rm -f "$SCRIPT_ERROR_LOG_FILE.gz"
        fi

    # else if warnings:
    elif [ "$nb_warnings" -gt 0 ]; then
        [ ! "$(endScript $SUPERVISOR_STATUS_END_WARNING)" -eq "1" ] && die 'End failed!'
        getDateWithCS; local datecs="$RETVAL"
        echo "$datecs;$EXECUTION_ID;$SCRIPT_NAME;WARNING" >> $SUPERVISOR_INFO_LOG_FILE
        echo "$datecs;WARNING" >> $SCRIPT_INFO_LOG_FILE
        [ "$nb_warnings" -gt 1 ] && plural='S' || plural=''
        CUI_displayMsg warning "$nb_warnings WARNING$plural"
        echo
        CUI_displayMsg help "Supervisor log file: $(dirname $SUPERVISOR_INFO_LOG_FILE)/<b>$(basename $SUPERVISOR_INFO_LOG_FILE)</b>"
        CUI_displayMsg help "Execution log file: $(dirname $SCRIPT_INFO_LOG_FILE)/<b>$(basename $SCRIPT_INFO_LOG_FILE)</b>"
        echo
        rm -f $SCRIPT_ERROR_LOG_FILE

        # warning mail:
        if [ "$SUPERVISOR_MAIL_SEND_ON_WARNING" -eq 1 ]; then
            [ "$nb_warnings" -gt 1 ] && plural='s' || plural=''
            local warning_html=''
            local history_ids=''
            for msg in "${warning_messages[@]}"; do
                warning_html="$warning_html<li>$msg</li>"
                if [[ "$msg" =~ rejected\ rows.*logs\.rejected_rows\.history_id= ]]; then
                    history_ids="$history_ids, ${msg##*=}"
                fi
            done
            warning_html="<ol>$warning_html</ol>"
            if [ ! -z "$history_ids" ]; then
                history_ids="${history_ids:1}"
                local query="$(cat <<EOF
WITH R AS (
    SELECT
        CASE
            WHEN substr(lower(message), 1, length('SQLSTATE[23505]: ')) != lower('SQLSTATE[23505]: ') THEN message
            ELSE substring(message from '^[^"]+"[^"]+"')
        END AS canonical_message, -- Unique violation
        *
    FROM logs.rejected_rows
    WHERE history_id IN ($history_ids)
)
SELECT
    row_number() OVER (ORDER BY count(*) DESC) AS "#",
    string_agg(distinct history_id::text, ', ' ORDER BY history_id::text ASC) AS history_ids,
    canonical_message,
    count(*) AS nb_of_rejected_rows,
    (count(*)*100.0/(SELECT count(*) FROM R))::numeric(4,1) AS percentage_of_rejected_rows,
    min(timestamp)::timestamp(0) with time zone AS min_date,
    max(timestamp)::timestamp(0) with time zone AS max_date,
    (SELECT R2.message FROM R AS R2 WHERE R2.canonical_message=R.canonical_message ORDER BY id ASC LIMIT 1) AS example_of_message,
    (SELECT R2.rejected_row FROM R AS R2 WHERE R2.canonical_message=R.canonical_message ORDER BY id ASC LIMIT 1) AS example_of_rejected_row
FROM R
GROUP BY canonical_message
ORDER BY "#" ASC
EOF
)";
                local css='<style type="text/css">
    table {border-collapse: collapse; border: 1px solid black;}
    table th, table td {border: 1px solid black; padding: 4px; white-space: nowrap;}
    table th {background-color: #ccc; font-size: 85%;}
    table td {font-size: 75%;}
</style>'
                local result_html="$(psql --html --table-attr="cellspacing=0" -h localhost -U dw datawarehouse_dev -c "$query")"
                warning_html="$warning_html<p>Summary of causes of rejected rows:</p>$css$result_html"
            fi
            warning_html="<br /><p style=\"color: #FF8C00\"><b>Warning$plural</b> <i>(see attached files for more details)</i>:$warning_html</p>"
            local mail_subject="$mail_subject_prefix WARNING ($EXECUTION_ID)"
            local mail_msg="<h3 style=\"color: #FF8C00\">Warning in execution of script '<b>$SCRIPT_NAME</b>' with id '<b>$EXECUTION_ID</b>'.</h3><b style=\"color: #FF8C00\">Completed with $nb_warnings warning$plural.</b><br />Instigator: "
            [ -z "$INSTIGATOR_EMAIL" ] && mail_msg="$mail_msg<i>not specified</i>" || mail_msg="$mail_msg$INSTIGATOR_EMAIL"
            mail_msg="$mail_msg<br /><br />\
Executed command: <pre>\$ $CMD $EXECUTION_ID $SCRIPT_ERROR_LOG_FILE 2>>$SCRIPT_ERROR_LOG_FILE</pre><br />\
Server: $(hostname)<br />\
Supervisor log file: $(dirname $SUPERVISOR_INFO_LOG_FILE)/<b>$(basename $SUPERVISOR_INFO_LOG_FILE)</b><br />\
Execution log file: $(dirname $SCRIPT_INFO_LOG_FILE)/<b>$(basename $SCRIPT_INFO_LOG_FILE)</b><br />\
Error log file: <i>N.A.</i><br />$warning_html"
            tail -n 50 "$SUPERVISOR_INFO_LOG_FILE" | gzip > "$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz"
            gzip -c "$SCRIPT_INFO_LOG_FILE" > "$SCRIPT_INFO_LOG_FILE.gz"
            echo "$mail_msg" | mutt -e "$SUPERVISOR_MAIL_MUTT_CMDS" -s "$mail_subject" -a $SUPERVISOR_MAIL_ADD_ATTACHMENT -- $SUPERVISOR_MAIL_TO $INSTIGATOR_EMAIL
            rm -f "$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz"
            rm -f "$SCRIPT_INFO_LOG_FILE.gz"
        fi

    # else if successful:
    else
        [ ! "$(endScript $SUPERVISOR_STATUS_END_OK)" -eq "1" ] && die 'End failed!'
        getDateWithCS; local datecs="$RETVAL"
        echo "$datecs;$EXECUTION_ID;$SCRIPT_NAME;OK" >> $SUPERVISOR_INFO_LOG_FILE
        echo "$datecs;OK" >> $SCRIPT_INFO_LOG_FILE
        CUI_displayMsg ok 'OK'
        echo
        CUI_displayMsg help "Supervisor log file: $(dirname $SUPERVISOR_INFO_LOG_FILE)/<b>$(basename $SUPERVISOR_INFO_LOG_FILE)</b>"
        CUI_displayMsg help "Execution log file: $(dirname $SCRIPT_INFO_LOG_FILE)/<b>$(basename $SCRIPT_INFO_LOG_FILE)</b>"
        echo
        rm -f $SCRIPT_ERROR_LOG_FILE

        # successful mail:
        if [ "$SUPERVISOR_MAIL_SEND_ON_SUCCESS" -eq 1 ]; then
            local mail_subject="$mail_subject_prefix Success ($EXECUTION_ID)"
            local mail_msg="Successful execution of script '<b>$SCRIPT_NAME</b>' with id '<b>$EXECUTION_ID</b>'.<br />Instigator: "
            [ -z "$INSTIGATOR_EMAIL" ] && mail_msg="$mail_msg<i>not specified</i>" || mail_msg="$mail_msg$INSTIGATOR_EMAIL"
            mail_msg="$mail_msg<br /><br />\
Executed command: <pre>\$ $CMD $EXECUTION_ID $SCRIPT_ERROR_LOG_FILE 2>>$SCRIPT_ERROR_LOG_FILE</pre><br />\
Server: $(hostname)<br />\
Supervisor log file: $(dirname $SUPERVISOR_INFO_LOG_FILE)/<b>$(basename $SUPERVISOR_INFO_LOG_FILE)</b><br />\
Execution log file: $(dirname $SCRIPT_INFO_LOG_FILE)/<b>$(basename $SCRIPT_INFO_LOG_FILE)</b><br />\
Error log file: <i>N.A.</i><br />\
No warnings."
            tail -n 50 "$SUPERVISOR_INFO_LOG_FILE" | gzip > "$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz"
            gzip -c "$SCRIPT_INFO_LOG_FILE" > "$SCRIPT_INFO_LOG_FILE.gz"
            echo "$mail_msg" | mutt -e "$SUPERVISOR_MAIL_MUTT_CMDS" -s "$mail_subject" -a $SUPERVISOR_MAIL_ADD_ATTACHMENT -- $SUPERVISOR_MAIL_TO $INSTIGATOR_EMAIL
            rm -f "$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz"
            rm -f "$SCRIPT_INFO_LOG_FILE.gz"
        fi
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
    local tmsg i

    # Trim:
    tmsg="$msg"
    if [ ! -z "$SUPERVISOR_LOG_TABULATION" ]; then
        #tmsg="${tmsg//[^[:print:]]\[+([0-9;])[mK]/}"
        tmsg="$(echo "$tmsg" | sed -r 's:(\033|\x1B)\[[0-9;]*[mK]::ig')"
        tmsg="${tmsg##+($SUPERVISOR_LOG_TABULATION)}"
    fi
    tmsg="${tmsg##+( )}"
    tmsg="${tmsg%%+( )}"

    if [ "${tmsg:0:8}" = 'WARNING ' ] || [ "${tmsg:0:9}" = '[WARNING]' ]; then
        echo -n $date
        i=$(( ${#msg} - ${#tmsg} ))
        echo -en "${msg:0:$i}"
        msg="${msg:$i}"
        msg="${msg//[^[:print:]]\[+([0-9;])[mK]/}"
        CUI_displayMsg warning "$msg"
        warning_messages[$nb_warnings]="$tmsg"
        let nb_warnings++
    elif [ "${tmsg:0:6}" = 'DEBUG ' ] || [ "${tmsg:0:7}" = '[DEBUG]' ]; then
        : #CUI_displayMsg processing "$msg"
    elif [ "${tmsg:0:7}" = 'MAILTO ' ] || [ "${tmsg:0:8}" = '[MAILTO]' ]; then
        SUPERVISOR_MAIL_TO="$SUPERVISOR_MAIL_TO ${tmsg:8}"
        : #CUI_displayMsg processing "$msg"
    elif [ "${tmsg:0:16}" = 'MAIL_ATTACHMENT ' ] || [ "${tmsg:0:17}" = '[MAIL_ATTACHMENT]' ]; then
        SUPERVISOR_MAIL_ADD_ATTACHMENT="$SUPERVISOR_MAIL_ADD_ATTACHMENT ${tmsg:17}"
    else
        echo -n $date
        CUI_displayMsg normal "$msg"
    fi
}

function die () {
    CUI_displayMsg error "$1" >&2
    echo
    exit 1
}
