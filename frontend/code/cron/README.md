# Frontend - Code - Cron

The codes in this directory are expected to be triggered by *crontab* calls. The ```cron``` call are expected to call, specifically, the scripts in the *cron/bash/* folder.

Suppose this *cron* folder has the absolute path ```[ABS]/cron/```. An example of a recommended *crontab* configuration would contain the following lines:


    ## VIRTUAL GAGES WEB SERVICE CHECKER
    15 * * * * bash [ABS]/cron/bash/delay_alerts_and_log.sh
    25 1 * * * bash [ABS]/cron/bash/flood_alerts_and_log.sh
