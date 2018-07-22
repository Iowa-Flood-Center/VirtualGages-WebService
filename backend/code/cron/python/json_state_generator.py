from libs.classes.FileSystemDefinitions import FileSystemDefinitions
import libs.json_state_generator_lib as libs
from libs.classes.Debug import Debug
import sys

the_timestamp = None
debug_level = 10

# ####################################################### ARGS ####################################################### #

if '-h' in sys.argv:
    print("Call format: python json_state_generator.py [TIMESTAMP]")
    print("  TIMESTAMP: If None, assumes most recent one")
    quit()

try:
    the_timestamp = int(sys.argv[1]) if len(sys.argv) > 1 else None
except ValueError:
    Debug.dl("json_state_generator: Provided timestamp ({0}) is not an integer.".format(sys.argv[1]), debug_level, 1)
    quit()

# ####################################################### CALL ####################################################### #

# define best timestamp
if the_timestamp is None:
    best_timestamp = FileSystemDefinitions.get_current_rounded_timestamp_from_hdf5_files()
else:
    best_timestamp = the_timestamp

# process for such best timestamp
if best_timestamp is None:
    Debug.dl("json_state_generator: Unable to define a proper timestamp.", debug_level, 1)
else:
    file_raw_content = libs.read_filesystem(the_timestamp=best_timestamp)
    disch_stage_dict = libs.convert_dist_to_stage(file_raw_content)
    libs.add_observed_stage_from_ws(disch_stage_dict, best_timestamp)

    libs.write_file(disch_stage_dict, the_timestamp=best_timestamp, debug_lvl=debug_level)
