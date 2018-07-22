#!/bin/bash

# head to the file directory
cd "$(dirname "$0")"

# ############################### DEFS ############################### #

# define config file reader
JQ="./third_party/jq-linux64"   # JSON reader tool
CFG_FILE="../../../conf/settings.json"

# define folder paths
PYTHON_FOLDER='../python/'
LOC_RAW_FOLDER=$(${JQ} -r '.raw_data_folder_path' ${CFG_FILE})
LOCAL_DATA_FOLDER=${LOC_RAW_FOLDER}"data/"

# get forecast models ids
FRT_MODELS_IDS_RAW=$(${JQ} '.model_forecasts_file_modelids' ${CFG_FILE})
FRT_MODELS_IDS=$(echo $FRT_MODELS_IDS_RAW | ${JQ} -r '.[]')

LOCAL_DATA_FILES_SUFFIX=$(${JQ} -r '.local_files_suffix' ${CFG_FILE})

# basic check
# chaos ON

DISTRIBUTION_METHOD=$(${JQ} -r '.distribution_method' ${CFG_FILE})
if [ "$DISTRIBUTION_METHOD" = "null" ]; then
  echo "No 'distribution_method' on settings file. No distribution."
else

  # check if should push into server for distributing
  DISTR_SCP_PUSH=$(${JQ} -r '.distribution_method.scp_push' ${CFG_FILE})
  if [ "$DISTR_SCP_PUSH" != "null" ]; then
    # get
    ATTR='.distribution_method.scp_push.server_realtime_folder_path'
    SRV_REALTIME_FOLDER=$(${JQ} -r ${ATTR} ${CFG_FILE})
    ATTR='.distribution_method.scp_push.server_forecast_folder_path'
    SRV_FORECAST_FOLDER=$(${JQ} -r ${ATTR} ${CFG_FILE})
    ATTR='.distribution_method.scp_push.server_address'
    SERVER_LOCATION=$(${JQ} -r ${ATTR} ${CFG_FILE})
    # check
    if [ "$SRV_REALTIME_FOLDER" = "null" ]; then
      echo "Server realtime folder missing in settings file. No push."
      DISTR_SCP_PUSH="null"
    elif [ "$SRV_FORECAST_FOLDER" = "null" ]; then
      echo "Server forecast folder missing in settings file. No push."
      DISTR_SCP_PUSH="null"
    elif [ "$SERVER_LOCATION" = "null" ]; then
      echo "Server location missing in settings file. No push."
      DISTR_SCP_PUSH="null"
    fi
  fi
  
  # check if there is at least one distribution method
  if [ "$DISTR_SCP_PUSH" = "null" ]; then
    echo "No ditribution procedure set up."
  fi
fi

# chaos OFF

# keep the following definitions here
LOCAL_REALTIME_CALL="python json_state_generator.py"
LOCAL_FORECAST_CALL="python json_forecast_generator.py"

LOCAL_DATA_REALTIME_FOLDER=${LOCAL_DATA_FOLDER}"realtime/"
LOCAL_DATA_FORECAST_FOLDER=${LOCAL_DATA_FOLDER}"forecast/"


# ############################### CALL ############################### #

echo "Started on "$(date)
echo ""

## generate realtime situation
echo "##### REALTIME ##################################################"
echo ""

pwd
cd $PYTHON_FOLDER
echo "SH: Calling '"$LOCAL_REALTIME_CALL"'"
$LOCAL_REALTIME_CALL

## looks for the current maximum timestamp available among file names
MAX_TIMESTAMP=0
for cur_file in ${LOCAL_DATA_REALTIME_FOLDER}*${LOCAL_DATA_FILES_SUFFIX} ;
do
  CUR_BASENAME=$(basename "$cur_file")
  IFS='_' read -ra CUR_TIMESTAMP <<< "$CUR_BASENAME"
  if [ $MAX_TIMESTAMP -lt $CUR_TIMESTAMP ]
  then
    MAX_TIMESTAMP=$CUR_TIMESTAMP
  fi
done

## copy only the most recent file
if [ "$DISTR_SCP_PUSH" != "null" ] ; then
  COPY_FILE=${LOCAL_DATA_REALTIME_FOLDER}
  COPY_FILE=${COPY_FILE}${MAX_TIMESTAMP}${LOCAL_DATA_FILES_SUFFIX}
  echo "Sending '"$COPY_FILE"'..."
  echo "  ...to '"$SERVER_LOCATION":"$SRV_REALTIME_FOLDER"'."
  scp $COPY_FILE $SERVER_LOCATION:$SRV_REALTIME_FOLDER
fi

### generate forecasts and upload them

for cur_model_id in `echo $FRT_MODELS_IDS`
do
  echo ""
  echo ""
  echo "##### FORECAST : "${cur_model_id}" ############################"
  echo ""

  # generate
  NEXT_CALL=$LOCAL_FORECAST_CALL" "$cur_model_id" "$MAX_TIMESTAMP
  echo "SH: Calling '"$NEXT_CALL"'"
  $NEXT_CALL

  # upload
  if [ "$DISTR_SCP_PUSH" != "null" ] ; then
    COPY_FILE=${LOCAL_DATA_FORECAST_FOLDER}
    COPY_FILE=${COPY_FILE}${cur_model_id}${LOCAL_DATA_FILES_SUFFIX}
    echo "Sending '"$COPY_FILE"'..."
    echo "  ...to '"$SERVER_LOCATION":"$SRV_FORECAST_FOLDER"'."
    scp $COPY_FILE $SERVER_LOCATION:$SRV_FORECAST_FOLDER
  fi
done

echo ""
echo ""
echo "##### DONE ######################################################"

echo ""
echo "Finished on "$(date)
