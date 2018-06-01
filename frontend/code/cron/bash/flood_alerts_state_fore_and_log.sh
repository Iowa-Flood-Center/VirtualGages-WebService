#!/bin/bash

cd "$( dirname "${BASH_SOURCE[0]}" )"
source "libs/call_and_log.shlib"
source "libs/json.shlib"

# ############################### DEFS ############################### #

SETTINGS_GENERAL='../../../conf/settings.json'
SETTINGS_ALERTS='../../../conf/settings_alerts_floods.json'

# read settings files
JSON.load "$(< ${SETTINGS_GENERAL})" settings_g_data
JSON.load "$(< ${SETTINGS_ALERTS})" settings_a_data

# define variables
CALL_CMD="php realtime_flood_alerts_state_fore.php"
NAME_CMD="realtime_flood_alerts_state_fore"
RAW_DATA_KEY='/raw_data_folder_path'
LOGS_DIR=$(JSON.get ${RAW_DATA_KEY} settings_g_data | tr -d \")"logs/"

# ############################### CALL ############################### #

# go to php folder and perform call
cd  "../php/"
call_and_log "${CALL_CMD}" "${NAME_CMD}" "${LOGS_DIR}"

