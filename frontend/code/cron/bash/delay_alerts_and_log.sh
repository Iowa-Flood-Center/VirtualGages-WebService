#!/bin/bash

cd "$( dirname "${BASH_SOURCE[0]}" )"
source "libs/call_and_log.shlib"
source "libs/json.shlib"

# ############################### DEFS ############################### #

# read 'settings.json' file
JSON.load "$(< ../../../conf/settings.json)" settings_data

# define variables
CALL_CMD="php realtime_delay_alerts.php"
NAME_CMD="realtime_delay_alerts"
LOGS_DIR=$(JSON.get /raw_data_folder_path settings_data | tr -d \")"logs/"

# ############################### CALL ############################### #

# go to php folder and perform call
cd  "../php/"
call_and_log "${CALL_CMD}" "${NAME_CMD}" "${LOGS_DIR}"
