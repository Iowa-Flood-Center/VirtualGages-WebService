from Settings import Settings
from Debug import Debug
import numpy as np
import datetime
import time
import h5py
import os


class FileSystemDefinitions:
    h5_file_modelids                = Settings.get("model_forecasts_file_modelids")
    h5_file_prefixes                = Settings.get("model_forecasts_file_prefixes")
    h5_file_prefix                  = Settings.get("model_state_file_prefix")
    the_file_path_frame             = os.path.join(Settings.get("model_data_folder_path"), "{0}{1}.h5")
    rc_file                         = os.path.join(Settings.get("raw_data_folder_path"), "anci",
                                                   "dot_ratingcurves_2.json")
    real_gages_file                 = os.path.join(Settings.get("raw_data_folder_path"), "anci",
                                                   "dot_gages_reference.json")
    output_json_file_path           = os.path.join(Settings.get("raw_data_folder_path"), "data", "realtime",
                                                   "dot_stages_true.json")
    output_json_file_path_frame     = os.path.join(Settings.get("raw_data_folder_path"), "data", "realtime",
                                                   "{0}_dot_stages.json")
    output_forecast_file_path_frame = os.path.join(Settings.get("raw_data_folder_path"), "data", "forecast/",
                                                   "{0}_dot_stages.json")
    max_link_id                     = Settings.get("max_link_id")

    @staticmethod
    def get_index_data_from_hdf5_file_v1(hdf5_file_path, debug_lvl=0):
        """
        Read HDF5 file
        :param hdf5_file_path:
        :param debug_lvl:
        :return: Array of linkids and array parameters matrix
        """

        # basic check
        if (hdf5_file_path is None) or (not os.path.exists(hdf5_file_path)):
            Debug.dl("bingen_asynchmodel254_hdf5: File '{0}' does not exist.".format(hdf5_file_path), debug_lvl, 1)
            return None, None

        # read file content
        with h5py.File(hdf5_file_path, 'r') as hdf_file:
            hdf_indexes = np.array(hdf_file.get('index'))
            hdf_data = np.array(hdf_file.get('state'))

        return hdf_indexes, hdf_data

    @staticmethod
    def get_index_data_from_hdf5_file_v2(hdf5_file_path, debug_lvl=0):
        """
        Read HDF5 file
        :param hdf5_file_path:
        :param debug_lvl:
        :return: Array of linkids and array parameters matrix
        """

        # basic check
        if (hdf5_file_path is None) or (not os.path.exists(hdf5_file_path)):
            Debug.dl("bingen_asynchmodel254_hdf5: File '{0}' does not exist.".format(hdf5_file_path), debug_lvl, 1)
            return None, None

        # read file content
        with h5py.File(hdf5_file_path, 'r') as hdf_file:
            hdf_data = np.array(hdf_file.get('snapshot'))

        return hdf_data

    @staticmethod
    def get_most_recent_file_path():
        """

        :return:
        """

        most_recent_timestamp = FileSystemDefinitions.get_current_timestamp_from_hdf5_files()
        return FileSystemDefinitions.the_file_path_frame.format(FileSystemDefinitions.h5_file_prefix,
                                                                most_recent_timestamp)

    @staticmethod
    def get_current_timestamp_from_hdf5_files(debug_lvl=0):
        """

        :param output_folder_path:
        :param debug_lvl:
        :return:
        """

        # basic check

        if Settings.get("model_data_folder_path") is None:
            return None
        elif not os.path.exists(Settings.get("model_data_folder_path")):
            return None

        all_file_names = os.listdir(Settings.get("model_data_folder_path"))

        # basic check - must have at least one file
        if len(all_file_names) == 0:
            return None

        all_file_names.sort(reverse=True)

        print("FileSystemDefinitions: Scanning folder {0}.".format(Settings.get("model_data_folder_path")))
        return FileSystemDefinitions.retrieve_timestamp_from_hdf5_state_filename(all_file_names[0], debug_lvl=debug_lvl)

    @staticmethod
    def get_current_rounded_timestamp_from_hdf5_files(debug_lvl=0):
        """

        :param debug_lvl:
        :return:
        """

        # basic check
        model_output_folder_path = Settings.get("model_data_folder_path")
        if model_output_folder_path is None:
            Debug.dl("FileSystemDefinitions: Invalid output folder path.".format(output_folder_path), debug_lvl, 1)
            return None
        elif not os.path.exists(model_output_folder_path):
            Debug.dl("FileSystemDefinitions: Not found '{0}'.".format(model_output_folder_path), debug_lvl, 1)
            return None

        all_file_names_raw = os.listdir(model_output_folder_path)

        # basic check - must have at least one file
        if len(all_file_names_raw) == 0:
            Debug.dl("FileSystemDefinitions: Not found '{0}'.".format(model_output_folder_path), debug_lvl, 1)
            return None

        Debug.dl("FileSystemDefinitions: Scanning folder '{0}'.".format(model_output_folder_path), debug_lvl, 1)
        all_file_names = []
        for cur_file_name in all_file_names_raw:
            if cur_file_name.startswith(FileSystemDefinitions.h5_file_prefix):
                all_file_names.append(cur_file_name)

        # basic check
        if len(all_file_names) <= 0:
            print("FileSystemDefinitions: No flies with '{0}' prefix on '{1}' folder.".format(
                FileSystemDefinitions.h5_file_prefix, model_output_folder_path))
            return
        else:
            print("FileSystemDefinitions: Found {0} files with prefix '{1}' at '{2}'.".format(
                len(all_file_names), FileSystemDefinitions.h5_file_prefix, model_output_folder_path))

        all_file_names.sort(reverse=True)

        # define most recent round timestamp
        most_recent_file_name = all_file_names[0]
        most_recent_timestamp = FileSystemDefinitions.retrieve_timestamp_from_hdf5_state_filename(most_recent_file_name,
                                                                                                  debug_lvl=debug_lvl)
        most_recent_datetime = datetime.datetime.fromtimestamp(most_recent_timestamp)
        most_recent_minute = most_recent_datetime.minute
        if most_recent_minute >= 30:
            return most_recent_timestamp

        replaced = most_recent_datetime.replace(minute=0, second=0, microsecond=0)
        most_recent_round_timestamp = time.mktime(replaced.timetuple())

        # identify the closest available timestamp to most recent rounded timestamp
        closest_timestamp = None
        min_dist_to_round = 100000000
        for cur_file_name in all_file_names:
            cur_file_timestamp = FileSystemDefinitions.retrieve_timestamp_from_hdf5_state_filename(cur_file_name,
                                                                                                   debug_lvl=debug_lvl)
            if cur_file_timestamp is None:
                continue
            cur_dist_to_round = abs(cur_file_timestamp - most_recent_round_timestamp)
            if cur_dist_to_round < min_dist_to_round:
                min_dist_to_round = cur_dist_to_round
                closest_timestamp = cur_file_timestamp
        return closest_timestamp

    @staticmethod
    def retrieve_timestamp_from_hdf5_state_filename(hdf5_filename, debug_lvl=0):
        """

        :param hdf5_filename:
        :param debug_lvl:
        :return:
        """

        # basic check
        if hdf5_filename is None:
            return None

        # process filename
        try:
            return int(hdf5_filename.replace(FileSystemDefinitions.h5_file_prefix, "").replace(".h5", ""))
        except ValueError:
            print("bingen_asynchmodel254_hdf5: Wrong HDF5 file name '{0}'.".format(hdf5_filename))
            return None

    @staticmethod
    def get_output_json_file_path(hdf5_timestamp):
        """

        :param hdf5_timestamp:
        :return:
        """

        return FileSystemDefinitions.output_json_file_path_frame.format(hdf5_timestamp)

    @staticmethod
    def get_h5_file_name_prefix(sc_model_id):
        """

        :param sc_model_id:
        :return:
        """

        # basic check
        if sc_model_id not in FileSystemDefinitions.h5_file_modelids:
            Debug.dl("FileSystemDefinitions: Folder '{0}' has no files starting with '{1}'.".format(
                Settings.get("model_data_folder_path"), file_prefix), 1, debug_lvl)
            return None

        sc_model_idx = FileSystemDefinitions.h5_file_modelids.index(sc_model_id)
        return FileSystemDefinitions.h5_file_prefixes[sc_model_idx]

    @staticmethod
    def get_most_recent_file_with_prefix(file_prefix, debug_lvl=0):
        """

        :param file_prefix:
        :param debug_lvl:
        :return:
        """

        all_files = os.listdir(Settings.get("model_data_folder_path"))
        if len(all_files) <= 0:
            Debug.dl("FileSystemDefinitions: Folder {0} is empty.".format(
                Settings.get("model_data_folder_path")), 1, debug_lvl)
            return None

        all_relevant_files = []
        for cur_raw_file in all_files:
            if cur_raw_file.startswith(file_prefix):
                all_relevant_files.append(cur_raw_file)

        if len(all_relevant_files) <= 0:
            Debug.dl("FileSystemDefinitions: Folder '{0}' has no files starting with '{1}'.".format(
                Settings.get("model_data_folder_path"), file_prefix), 1, debug_lvl)
            return None

        most_recent_filename = sorted(all_relevant_files, reverse=True)[0]
        Debug.dl("FileSystemDefinitions: Most recent file is '{0}'.".format(most_recent_filename), 1, debug_lvl)
        return os.path.join(Settings.get("model_data_folder_path"), most_recent_filename)

    def __init__(self):
        return
