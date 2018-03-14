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