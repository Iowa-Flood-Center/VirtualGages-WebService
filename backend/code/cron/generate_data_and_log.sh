 #!/bin/bash

# head to the file directory
cd "$(dirname "$0")"

# ############################### DEFS ############################### #

# define config file reader
JQ="./bash/third_party/jq-linux64"                    # JSON reader tool
CFG_FILE="../../conf/settings.json"

# static file names and suffix
LOG_FILENAME_STD_NOW="generate_realtime_data_o.txt"
LOG_FILENAME_STD_LAST="generate_realtime_data_o_last.txt"
LOG_FILENAME_ERR_NOW="generate_realtime_data_e.txt"
LOG_FILENAME_ERR_LAST="generate_realtime_data_e_last.txt"
SH_FILENAME="generate_data.sh"

# static folder paths
SRC_FOLDER=$(dirname "$0")'/bash/'
LOC_RAW_FOLDER=$(${JQ} -r '.raw_data_folder_path' ${CFG_FILE})
LOC_LOG_FOLDER=${LOC_RAW_FOLDER}'logs/generate_realtime_data/'

# ############################### CALL ############################### #

# set up absolute paths
LOG_FILEPATH_STD_NOW=${LOC_LOG_FOLDER}${LOG_FILENAME_STD_NOW}
LOG_FILEPATH_STD_LAST=${LOC_LOG_FOLDER}${LOG_FILENAME_STD_LAST}
LOG_FILEPATH_ERR_NOW=${LOC_LOG_FOLDER}${LOG_FILENAME_ERR_NOW}
LOG_FILEPATH_ERR_LAST=${LOC_LOG_FOLDER}${LOG_FILENAME_ERR_LAST}

SH_FILEPATH=${SRC_FOLDER}${SH_FILENAME}


### SET UP LOGS ####################

# remove old files if they exist
if [ -f $LOG_FILEPATH_STD_LAST ]; then
  rm $LOG_FILEPATH_STD_LAST
fi
if [ -f $LOG_FILEPATH_ERR_LAST ]; then
  rm $LOG_FILEPATH_ERR_LAST
fi

# make the most recent ones the last ones
if [ -f $LOG_FILEPATH_STD_NOW ]; then
  mv $LOG_FILEPATH_STD_NOW $LOG_FILEPATH_STD_LAST
fi
if [ -f $LOG_FILEPATH_ERR_NOW ]; then
  mv $LOG_FILEPATH_ERR_NOW $LOG_FILEPATH_ERR_LAST
fi


### RUN DATA GENERATOR #############

# call script - realtime state
echo "Calling "${SH_FILEPATH}
bash $SH_FILEPATH 1> $LOG_FILEPATH_STD_NOW 2> $LOG_FILEPATH_ERR_NOW 

####################################

echo "Done."
