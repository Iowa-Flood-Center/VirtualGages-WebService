# Settings Files

All files in this folder are just templates that need to be copied to the ```conf```, renamed in order to remove the *"-TEMPLATE"* suffix and have the internal fields properly set up.

**Example:** the file ```.../conf-TEMPLATE/settings-TEMPLATE.json``` should be copied to ```.../conf/settings.json```.

Each resulting file will be described as follows.

## settings-TEMPLATE.json

Fields:

- **raw_data_folder_path**
  - Description: Path of the folder where the raw data of the system is stored
  - Format: String
  - Example: "/data/raw/"
- **model_data_folder_path**
  - Description: Path of the folder where the output files from Asynch are stored
  - Format: String
  - Example: "/data/asynch\_output/"
- **model_state_file_prefix**
  - Description: Name prefix for the output files used as current state
  - Format: String
  - Example: "state\_model1\_"
- **model_forecasts_file_modelids**
  - Description: List of model ids for forecast files
  - Format: Array of string
  - Example: ["model1\_norain", "model1\_qpf"]
- **model_forecasts_file_prefixes**
  - Description: List of name prefix for forecast files
  - Format: Array of strings. Same size as *model\_forecasts\_file\_modelids*
  - Example: ["mdl1\_nor\_", "mdl1\_qpf\_"]
- **max_link_id**
  - Description: Maximum link ID value in the system
  - Format: Integer
  - Example: 620119
- **observed_stages_webservice_url**
  - Description: URL of the web service that provides realtime observed data
  - Format: String
  - Example: "http://someurl.somedomain.php?get=id,stage"
- **observed_stages_webservice_div**
  - Description: Character that separates columns in the Web Service retrieved data
  - Format: String
  - Example: "|"
- **observed_stages_webservice_ifisid_index**
  - Description: Index of the *ifis id* column in the Web Service retrieved data
  - Format: Integer
  - Example: 0
- **observed_stages_webservice_stage_index**
 - Description: Index of the *stage depth* column in the Web Service retrieved data
 - Format: Integer
 - Example: 1

## settings-database-TEMPLATE.json

Fields:

- **db_host**
  - Description: Address of the computer hosting a PostGreSQL database that contains the rating curves of the virtual gages
  - Format: String
  - Example: "s-iihr123.(...).edu"
- **db_port**
  - Description: Port used for connecting to the PostGreSQL database
  - Format: String
  - Example: "5432"
- **db_name**
  - Description: Name of the PostGreSQL database that contains the rating curves of the virtual gages
  - Format: String
  - Example: "all\_rating\_curves"
- **db_user**
  - Description: Username to connect to the PostGreSQL database that contains the rating curves of the virtual gages
  - Format: String
  - Example: "automated\_user"
- **db_pass**
  - Description: Password of the database user defined in *Username* to connect to the PostGreSQL database that contains the rating curves of the virtual gages
  - Format: String
  - Example: "p@$$w0Rd"
- **db_rc_query**
  - Description: *SQL select* query for retriving the rating curves data of the virtual gages from the database(\*)
  - Format: String
  - Example: "SELECT obj\_id, stage, discharge, link\_id FROM vitrgage\_ratingcurve"


** Note(\*): ** The expected format of retrieved rating curves has the following sorted collumns:
- pois\_id (integer, unique identifier of the virtual gage)
- stage (float, stage [in feet] of a mark in the rating curve)
- discharge (float, discharge [in m^3/s] of a mark in the rating curve)
- link\_id (integer, unique identifier of the link to which the virtual gages is associated)

Example:

| obj\_id | stage | discharge | link\_id |
| ------- | ----- | --------- | -------- |
|   ...   |  ...  |    ...    |   ...    |
|  2006   | 644.6 |  5669.77  |  819902  |
|  2006   | 644.8 |  6791.01  |  819902  |
|  2007   | 927.4 |     0.00  |  350352  |
|  2007   | 929.2 |    50.00  |  350352  |
|   ...   |  ...  |    ...    |   ...    |
