# Frontend - Code - Cron - bash

Each *bash* script in this folder is responsible for a different task and has its logical procedures implemented in the ``../php/`` folder with the same respective basename.

Each time one of these scripts is executed, two logs file are written at the ``/[...]/raw/logs/`` folder. One of the new log files with the file extension *"\_o.txt"* (with the standard output) and another ending by *\_e.txt* (with error output). Ideally, the *error* file should be always empty.  

All of the systems use directly or indirectly information present in the ``conf/settings.json`` file. Additionaly, they depend on more specific additional settings files.

## Delay alerts

Implemented by the ``delay_alerts_and_log.sh`` script.

Sends e-mails around every time the output of a model seems to be delayed. It is used for maintenance purposes. 

Different users can be alerted on different models by different 'delay threshold levels' defined in the specific *settings file*.

- Specific settings file: ``conf/settings_alerts_delays.json``
- Should be called: once every hour

## Flood alerts - recent past

Implemented by the ``flood_alerts_past_and_log.sh`` script.

Sends e-mails around every time the output of a model or observation has observed a condition of  *flood*, *moderate flood* or *major flood* in the past 24 hours. It is used so maintainers can check if model outputs were able to predict observed events of interest.

Different users can be alerted by different thresholds as defined in the attribute *"alert_past"* of the specific *settings file*.

- Specific settings file: ``conf/settings_alerts_floods.json``
- Should be called: once per day

## Flood alerts - forecast

Implemented by the ``flood_alerts_state_fore_and_log.sh`` script.

Sends e-mails around every time it detects that a condition of  *flood*, *moderate flood* or *major flood* was forecasted. It is used for potential actions takers.

Different users can be alerted by different thresholds as defined in the attribute *"alert_state_fore"* of the specific *settings file*.

- Specific settings file: ``conf/settings_alerts_floods.json``
- Should be called: once every hour