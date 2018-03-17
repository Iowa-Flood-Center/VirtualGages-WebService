# Frontend - Settings Files (templates)

All files in this folder are just templates that need to be copied to the ```conf```, renamed in order to remove the "*-TEMPLATE*" suffix and have the internal fields properly set up.

**Example**: the file ```.../conf-TEMPLATE/settings-TEMPLATE.json``` should be copied to ```.../conf/settings.json```.

Each resulting file will be described as follows.

## settings-TEMPLATE.json

Fields:

- url\_state
  - Description: URL of the Virtual Gages Web Service that provides only current state model output
  - Format: String.
  - Example: "http://.../ws\_realtime\_summary.php"
- url\_forecast\_frame
  - Description: Beggining of the URL of the Virtual Gages Web Service that provides only a forecast model output
  - Format: String
  - Example: "http://.../ws\_realtime\_summary.php?only\_forecast&forecast\_id="
- timestamp\_index
  - Description: Index of the timestamp column in the Web service retrieved data
  - Format: Integer
  - Example: 1
- vg\_webservice\_div
  - Description: Character that separates columns in the Web Service retrieved data
  - Format: String
  - Example: ","
- forecast\_models
  - Description: All forecast model ids accepted by the Virtual Gages Web Service
  - Format: Array of strings.
  - Example: ["fc254ifc01norain", "fc254ifc01qpf", "fc254ifc01w2in24h"]
- alerts\_minutes
  - Description: All minute thresholds for alerts, in minutes
  - Format: Array of integers.
  - Example: [0, 90, 150, 240, 480, 1000]
- alerts\_labels
  - Description: All labels for triggered alerts
  - Format: Array of strings. Same size as **alerts\_minutes**
  - Example: ["green", "yellow", "orange", "red", "dark\_red", "extreme"]
- receivers
  - Description: Dictionary relating receivers and personal alert thersholds
  - Format: Dictionary of "email"(string) -> "color label threshold"(string)
  - Example: {"me@here.com":"green", "you@there.org":"red"}
- smtp\_host
  - Description: Address for SMTP host (used for sending email)
  - Format: String
  - Example: "smtp.(...).com"
- smtp\_port
  - Description: Port used for SMTP communication (used for sending email)
  - Format: String
  - Example: "587"
- server\_name
  - Description: Name of the current local server
  - Format: String
  - Example: "IIHR-Server 123"
- smtp\_from\_mail
  - Description: Email address used for sending email
  - Format: String
  - Example: "iihr.server.alert@(...).com"
- smtp\_from\_name
  - Description: Name of the email sender
  - Format: String
  - Example: "IIHR Server Alert"
- smtp\_from\_pass
  - Description: Password for *smtp\_from\_mail* user at *smtp\_host*
  - Format: String
  - Example: "p@$$w0rd"