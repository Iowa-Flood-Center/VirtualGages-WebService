from classes.FileSystemDefinitions import FileSystemDefinitions
from scipy.interpolate import interp1d
from classes.Settings import Settings
from classes.Debug import Debug
import numpy as np
import datetime
import psycopg2
import urllib2
import math
import time
import json
import h5py
import os


def read_filesystem(the_timestamp=None):
    """
    Read the discharge content of the most recent HDF5 file in the system
    :param the_timestamp:
    :return: Numpy List of floats with the modeled discharge of all link ids in Iowa, no restriction.
    """

    if the_timestamp is None:
        hdf5_file_path = FileSystemDefinitions.get_most_recent_file_path()
    else:
        hdf5_file_path = FileSystemDefinitions.the_file_path_frame.format(FileSystemDefinitions.h5_file_prefix,
                                                                          the_timestamp)

    # read file
    cur_file_data = FileSystemDefinitions.get_index_data_from_hdf5_file_v2(hdf5_file_path)

    # create empty receiving vector and fill it
    vect_iq = np.zeros(FileSystemDefinitions.max_link_id + 1, dtype=np.float)
    for cur_i, cur_item in enumerate(cur_file_data):
        cur_linkid = cur_item[0]
        vect_iq[cur_linkid] = cur_item[1]

    return vect_iq


def get_stage(all_rcs, link_id, discharge):
    """

    :param all_rcs: Dictionary object directly read from JSON file
    :param link_id: String - Link_id
    :param discharge: Double value
    :return: Double value related to conversion if possible to interpolate, None otherwise.
    """

    link_dict = all_rcs['all_rcs'][link_id]
    all_disch = link_dict["discharge"]
    all_stage = link_dict["stage"]

    converter_func = interp1d(all_disch, all_stage, kind='cubic')
    stage = float(converter_func(discharge))

    return stage


def convert_dist_to_stage(all_disc_records_vect):
    """
    Converts all possible discharges into stages (restriction: available DOT rating curves at ancillary file)
    :param all_disc_records_vect: Numpy List of floats with the discharge of all linkids in Iowa.
    :return: Dictionary in the form of [ifis_id]={"stage":stage_value, "discharge":discharge_value}
    """

    with open(FileSystemDefinitions.rc_file, "r") as dest_file:
        rcs_dict = json.load(dest_file)

    return_dict = {}
    vect_stg = all_disc_records_vect
    for cur_linkid, cur_discharge in enumerate(vect_stg):
        if cur_discharge > 0:
            cur_str_linkid = str(cur_linkid)
            if cur_str_linkid in rcs_dict['all_rcs'].keys():
                # return_dict[rcs_dict['all_rcs'][cur_str_linkid]['ifis_id']] = cur_discharge
                cur_sub_dict = {
                    "stage": format(get_stage(rcs_dict, cur_str_linkid, cur_discharge * 35.3147), '.2f'),  # disc from cms to cfs
                    "discharge": cur_discharge,
                    "link_id": int(cur_str_linkid)
                }
                if cur_sub_dict["link_id"] in (174456, 108498):
                    print("json_state_generator_libs: For link {0}, convert from {1} cfs to {2} ft.".format(
                        cur_sub_dict["link_id"], cur_sub_dict["discharge"], cur_sub_dict["stage"]))
                    print("                            key: {0}".format(rcs_dict['all_rcs'][cur_str_linkid]['ifis_id']))
                return_dict[rcs_dict['all_rcs'][cur_str_linkid]['ifis_id']] = cur_sub_dict

    return return_dict


def add_observed_stage_from_ws(all_disc_records_vect, ref_timestamp):
    """
    Search for observed data and add it to the respective objects. Requires http web access.
    :param all_disc_records_vect: Dictionary with all modeled stages and discharges
    :return: None. Changes are performed inside dictionary object
    """

    # load real gages
    with open(FileSystemDefinitions.real_gages_file) as gages_file:
        json_gages_id_dict = json.load(gages_file)

    # read real gages ids from file
    real_ifis_ids = []
    for cur_virt_gage_id in json_gages_id_dict.keys():
        cur_virt_gage = json_gages_id_dict[cur_virt_gage_id]
        real_ifis_ids.append(cur_virt_gage["gage_ifis_id"])

    # retrieve raw data from web service, remove header
    ws_url = Settings.get("observed_stages_webservice_url")
    http_content = urllib2.urlopen(ws_url).read()
    all_recs = http_content.split("\n")[1:]

    # get info from settings file
    idx_ifisid = Settings.get("observed_stages_webservice_ifisid_index")
    idx_stageid = Settings.get("observed_stages_webservice_stage_index")
    ws_div = Settings.get("observed_stages_webservice_div")

    # iterates over tuples filling the objects
    count_added = 0
    for cur_rec_line in all_recs:
        cur_rec = cur_rec_line.split(ws_div)
        try:
            cur_gage_id = int(cur_rec[idx_ifisid])
            for cur_virt_gage in json_gages_id_dict.values():
                cur_virt_gage_id = cur_virt_gage["gage_ifis_id"]
                if cur_virt_gage_id == cur_gage_id:
                    cur_obs_stage = float(cur_rec[idx_stageid])/12
                    all_disc_records_vect[cur_virt_gage["virtual_ifis_id"]]["obs_stage"] = cur_obs_stage
                    count_added += 1
                    continue
        except ValueError:
            continue


def write_file(all_stage_records_dict, the_timestamp=None, debug_lvl=0):
    """

    :param all_stage_records_dict:
    :param the_timestamp:
    :param debug_lvl:
    :return:
    """

    # define output file timestamp and file path
    if the_timestamp is None:
        the_rounded_timestamp = FileSystemDefinitions.get_current_rounded_timestamp_from_hdf5_files(debug_lvl=debug_lvl)
    else:
        the_rounded_timestamp = the_timestamp
    the_file_path = FileSystemDefinitions.output_json_file_path_frame.format(the_rounded_timestamp)

    # create folder structure if necessary
    the_folder_path = os.path.dirname(the_file_path)
    if not os.path.exists(the_folder_path):
        os.makedirs(the_folder_path)

    # write file
    with open(the_file_path, "w+") as dest_file:
        json.dump(all_stage_records_dict, dest_file)
        print("json_state_generator_libs: Wrote '{0}'.".format(the_file_path))
        print("                             keys: '{0}'.".format(all_stage_records_dict.keys()))

    return None
