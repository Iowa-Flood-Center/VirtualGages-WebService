# Frontend - Code - Cron

## Overview

The codes in this directory are expected to be triggered by *crontab* calls. The *crontab triggers* are expected to call, specifically, the scripts in the *bash/* folder.

The folder *php/* folder contains *php scripts* with all the effective logic of the procedures. They are not expected to be called by *crontab triggers*, but by the scripts *bash* scripts in the *bash/* folder.

For more information concerning each specific script, see documentation in the *bash/* folder.

## Crontab configuration

A typical *crontab* configuration would be:

    ## Folder location

    CRON_SCRIPTS_FOLDER=/[...]/frontend/code/cron/bash/

    ## Script calls

    22 * * * * bash ${CRON_SCRIPTS_FOLDER}delay_alerts_and_log.sh
    41 1 * * * bash ${CRON_SCRIPTS_FOLDER}flood_alerts_past_and_log.sh
    25 * * * * bash ${CRON_SCRIPTS_FOLDER}flood_alerts_state_fore_and_log.sh 

**NOTE:** It is necessary to replace ```[...]``` by the proper folder path to where the scripts were copied. 