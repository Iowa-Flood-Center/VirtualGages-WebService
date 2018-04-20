from libs.classes.FileSystemDefinitions import FileSystemDefinitions
# from scipy.interpolate import interp1d
from classes.RatingCurve import RatingCurve
from classes.Settings import Settings
from classes.Debug import Debug
import numpy as np
import psycopg2
import json
import time
import h5py
import os


# ####################################################### DEFS ####################################################### #


def read_h5file_content(hdf5_file_path, debug_lvl=0):
    """

    :param hdf5_file_path:
    :param debug_lvl:
    :return:
    """

    file_timestamp = int((hdf5_file_path.split("_")[-1]).split(".")[0])

    if not os.path.exists(hdf5_file_path):
        Debug.dl("json_forecast_generator_lib: File '{0}' does not exist.".format(hdf5_file_path), 1, debug_lvl)
    else:
        Debug.dl("json_forecast_generator_lib: File '{0}' exists.".format(hdf5_file_path), 1, debug_lvl)

    with h5py.File(hdf5_file_path, 'r') as hdf_file:
        hdf_data = np.array(hdf_file.get('outputs'))

    Debug.dl("json_forecast_generator_lib: Got from '{0}':".format(hdf5_file_path), 1, debug_lvl)

    return hdf_data, file_timestamp


def convert_dist_to_stage(all_disc_records_vect, the_timestamp, debug_lvl=0):
    """
    Converts all possible discharges into stages (restriction: available DOT rating curves at ancillary file)
    :param all_disc_records_vect: List with 'link_id', 'unix_time', 'discharge'
    :param the_timestamp:
    :param debug_lvl:
    :return: Dictionary in the form of [ifis_id]={"stage":stage_value, "discharge":discharge_value}
    """

    # basic check
    if all_disc_records_vect is None:
        return None

    with open(FileSystemDefinitions.rc_file, "r") as dest_file:
        rcs_dict = json.load(dest_file)

    Debug.dl("json_forecast_generator_lib: Converting {0} values (discharge/stage).".format(len(all_disc_records_vect)),
             1, debug_lvl)

    return_dict = {}
    all_rcs_keys = rcs_dict['all_rcs'].keys()
    for cur_disc_records_vect in all_disc_records_vect:
        cur_str_linkid = str(cur_disc_records_vect[0])
        if cur_str_linkid in all_rcs_keys:
            cur_timestamp = int(the_timestamp + (cur_disc_records_vect[1] * 60))
            cur_discharge = cur_disc_records_vect[2] * 35.3147  # converting from cms to cfs

            raw_stg = RatingCurve.get_stage(rcs_dict, cur_str_linkid, cur_discharge)
            cur_sub_dict = {
                "stage": format(raw_stg, '.2f'),
                "discharge": format(cur_discharge, '.4f'),
                "timestamp": int(cur_timestamp),
                "link_id": int(cur_str_linkid)
            }
            if rcs_dict['all_rcs'][cur_str_linkid]['ifis_id'] not in return_dict.keys():
                return_dict[rcs_dict['all_rcs'][cur_str_linkid]['ifis_id']] = []

            return_dict[rcs_dict['all_rcs'][cur_str_linkid]['ifis_id']].append(cur_sub_dict)

    Debug.dl("json_forecast_generator_lib: Conversion completed.".format(len(all_disc_records_vect)), 1, debug_lvl)

    return return_dict


def write_file(all_stage_records_dict, sc_model_id, debug_lvl=0):
    """

    :param all_stage_records_dict:
    :param sc_model_id:
    :param debug_lvl:
    :return:
    """

    if sc_model_id is None:
        return None

    the_file_path = FileSystemDefinitions.output_forecast_file_path_frame.format(sc_model_id)

    # create folder structure if necessary
    the_folder_path = os.path.dirname(the_file_path)
    if not os.path.exists(the_folder_path):
        os.makedirs(the_folder_path)

    # write file
    with open(the_file_path, "w+") as dest_file:
        json.dump(all_stage_records_dict, dest_file)
        Debug.dl("json_forecast_generator_lib: Wrote '{0}'.".format(the_file_path), 1, debug_lvl)

    return None
