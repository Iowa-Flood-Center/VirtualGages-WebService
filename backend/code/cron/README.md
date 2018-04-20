# Backend - Code - Cron

The scripts in this folder are expected to be executed on a regular basis by a tool for job scheduling such as Cron.

A typical Crontab configuration would be:

    SHELL=/bin/bash
    
    #minute (0-59)
    #|   hour (0-23)
    #|   |    day of the month (1-31)
    #|   |    |   month of the year (1-12 or Jan-Dec)
    #|   |    |   |   day of the week (0-6 with 0=Sun or Sun-Sat)
    #|   |    |   |   |   commands
    #|   |    |   |   |   |
    
    ## Vitural Gages - Generate data
    32 * * * * bash <THIS_FOLDER_PATH>generate_data_and_log.sh

**Note:** ```<THIS_FOLDER_PATH>``` must be replaced by an absolute folder path