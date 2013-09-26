Quelques caractéristiques :

 – supervise l'exécution de tout script exécutable (donc quel que soit le langage, même interprété tant qu'un shebang est présent) 

 – garanti l'envoi d'un e-mail, si souhaité, à l'initialisation, sur succès, sur alertes non bloquantes, sur erreur (que ce soit dû à un exit, une fatale, une exception, …) 
 – mails avec logs gzip en pièces jointes
 – mails entièrement personnalisables par un système de redéfinition de fonction par fichier externe dont le nom est à passer en paramètre

 – analyse le canal standard d'erreur (STDERR) et le code de sortie du script : ils doivent rester vide et à 0 pour un succès
 – horodate avec précision (centièmes de seconde) de toute sortie sur le canal standard de sortie (STDOUT), soulageant de cette tâche le script supervisé 
 – chaque supervision de script est accompagnée d'un identifiant unique (<exec_id), permettant de faire le lien avec les différents fichiers de log : supervisor.info.log, supervisor.error.log, <script>_<exec_id>.info.log, <script>_<exec_id>.error.log
 – archivage auto des logs 

 – chaque script supervisé se voit ajoutés à la suite de ses paramètres deux paramètres supplémentaires : l'identifiant unique de supervision ainsi que le fichier enregistrant la sortie standard d'erreur du script lui-même 
 – processus séparé de monitoring du superviseur lui-même, pour alerter par e-mail de toute éventuelle erreur (ce qui serait critique), avec fréquence d'envoi à décroissance exponentielle jusqu'à traitement du problème 
 – la sortie standard de la supervision elle-même comprend la sortie standard et la sortie d'erreur du script supervisé, même si elles sont enregistrées par ailleurs, avec horodatage et maintien des éventuelles couleurs et indentation, plus des informations (colorées pour une meilleur digestion) du superviseur lui-même (nom des fichiers de logs, statut,...)
 – possibilité d'injection de multiples valeurs lors de l'appel au superviseur, par paramètres, et utilisables dans les e-mails 
 – possibilité d'injecter un fichier de configuration lors de l'appel au superviseur, par paramètre, exploité en redéfinition partielle du fichier de configuration par défaut
 – possibilité d'empêcher toute supervision parallèle d'un même script, pratique notamment selon l'approche cron retenue
 – système de tags permettant au script supervisé de remonter des informations particulières au superviseur : alertes non bloquantes, ajout d'un destinataire e-mail, ajout de pièces jointes e-mail, instigateur, …

 – code du superviseur en bash
 – couche de tests unitaires PHP sur le code du superviseur, avancée mais non encore terminée
 – publication open source en approche, avec sa documentation détaillée

 – les tags sont de la forme '[tag]' et doivent toujours commencer en début de ligne ou n'être précédés que de tabulations (SUPERVISOR_LOG_TABULATION)
 – tag DEBUG masque le message dans STDOUT du superviseur, mais le message reste présent dans le log <script>_<exec_id>.info.log
