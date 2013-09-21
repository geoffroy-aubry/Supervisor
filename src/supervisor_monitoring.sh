#!/bin/bash

##
# Monitoring du fichier de log d'erreur du superviseur.
#
# Une entrée crontab l'appelle chaque minute.
# Des mails sont envoyés à $SUPERVISOR_MAIL_TO si $SUPERVISOR_ERROR_LOG_FILE est non vide.
# À chaque nouvelle erreur, jusqu'à ce que le log d'erreur soit vidé ou qu'une nouvelle erreur survient :
#   - 1 mail par minute les 10 premieres minutes
#   - 1 mail toutes les 10 minutes les ~2 premieres heures
#   - 1 mail par heure au bout de ~2 heures
#   - 1 mail toutes les 6 heures au bout de 10 heures
##

# Includes :
. $(dirname $0)/conf/config.sh

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
		echo "$mail_msg" | mutt -F "$CONF_DIR/muttrc" -e "set content_type=text/html" -s "$mail_subject" -a "$SUPERVISOR_INFO_LOG_FILE.gz" -a "$SUPERVISOR_ERROR_LOG_FILE.gz" -- $SUPERVISOR_MAIL_TO
		rm -f "$SUPERVISOR_INFO_LOG_FILE.gz"
		rm -f "$SUPERVISOR_ERROR_LOG_FILE.gz"
	fi
fi
