from classes.Settings import Settings
import psycopg2
import json
import os


def create_rc_dict():
    """
    Creates an empty dictionary object in the format of the respective rating curve
    :return:
    """

    return_dict = {
        "ifis_id": None,
        "stage": [],
        "discharge": []
    }
    return return_dict


def write_file(all_records):
    """
    Write the respective JSON file
    :param all_records:
    :return:
    """

    dest_folder_path = Settings.get("raw_data_folder_path")
    dest_folder_path = os.path.join(dest_folder_path, "anci")
    dest_file_name = "dot_ratingcurves.json"
    dest_file_path = os.path.join(dest_folder_path, dest_file_name)

    all_rc = {}
    cur_rc = None
    last_link_id = None

    print("Got {0} records.".format(len(all_records)))
    for cur_record in all_records:
        cur_rc = create_rc_dict() if cur_rc is None else cur_rc
        last_link_id = cur_record[3] if last_link_id is None else last_link_id
        print(" Processing {0}.".format(cur_record))
        if last_link_id != cur_record[3]:
            all_rc[last_link_id] = cur_rc
            print("  Created dictionary for {0} with {1}.".format(last_link_id, len(cur_rc["stage"])))
            cur_rc = create_rc_dict()
        cur_rc["ifis_id"] = cur_record[0]
        cur_rc["stage"].append(cur_record[1])
        cur_rc["discharge"].append(cur_record[2])
        last_link_id = cur_record[3]
    all_rc[last_link_id] = cur_rc
    print("  Created dictionary for {0} with {1}.".format(last_link_id, len(cur_rc["stage"])))

    # forces the list to be in a dictionary
    all_rc_dict = {
        "all_rcs": all_rc
    }

    with open(dest_file_path, "w+") as dest_file:
        json.dump(all_rc_dict, dest_file)

    print("Wrote {0} rating curves at {1}".format(len(all_rc.keys()), dest_file_path))
