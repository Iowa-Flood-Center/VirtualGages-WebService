<?php

    /**
	 * Definitions/Constants for this web service
	 */
	class WSDefinitions{
		const MIN_TIMESTAMP_ID = "min_timestamp";
		const MAX_TIMESTAMP_ID = "max_timestamp";
		const MIN_DATETIME_ID = "min_datetime";
		const MAX_DATETIME_ID = "max_datetime";
		const SHOW_ID = "show_me";
		const SHOW_REAL_ID = "the_truth";
		const SHOW_GLUE_ID = "the_glue";
		const FORECAST_ID = "forecast_id";
		const ONLY_FORECAST = "only_forecast";
		const BACK_DAYS_DEFAULT = 10;
		const HUMAN_DATE_FORMAT_ARG = "%m_%d_%Y";       // e.g. 03_22_2016
		const HUMAN_DATE_FORMAT_OUT = "'Y-m-d H:i P'";  // e.g. 2016-03-22 18:55
		const JSON_FILE_SUFIX = "_dot_stages.json";
		const IFIS_ID_ONLY = "ifis_id";
		const LINKID_ID = "show_linkid";
		const SHOW_LINKID_ID = "yes";
	}

?>