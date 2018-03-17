# Backend - Code - Tool

The files in this filder are scripts that are expected to be called eventually for maintenance purposes.

Their basic functionality is to materialize the content of a database into JSON files. The objective of such materialization is to reduce the number of live systems to which the realtime service relies on.

## json\_ratingcurves\_generator.py

Materializes rating curves into a JSON file. It depends on both  ```backend/conf/settings.json``` and ```backend/conf/settings-database.json``` properly set up and access to the database that contains the rating curve table to be materialized.

**Input:** data available in the database described by the connections in the ```backend/conf/settings-database.json``` file

**Output:** a new file ```[RAW_FOLDER_PATH]/anci/dot_ratingcurves.json``` is writen. After proper human review, it must be renamed to ```dot_ratingcurves_2.json``` to be accessed by the other scripts.

