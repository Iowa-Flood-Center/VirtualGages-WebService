from libs.classes.FileSystemDefinitions import FileSystemDefinitions
import libs.json_forecast_generator_lib as libs
from libs.classes.Debug import Debug
import sys

debug_level = 10

# ####################################################### ARGS ####################################################### #

if '-h' in sys.argv:
    print("Call format: python json_forecast_generator.py [SC_MODEL_ID]")
    print("  with SC_MODEL_ID: 'fc254ifc01norain', 'fc254ifc01qpf' or 'fc254ifc01w2in24h'")
    quit()

if len(sys.argv) == 1:
    print("Missing argument.")
    quit()

sc_model_id = sys.argv[1]

# ####################################################### CALL ####################################################### #

h5_fname_prefix = FileSystemDefinitions.get_h5_file_name_prefix(sc_model_id)
most_recent_file_path = FileSystemDefinitions.get_most_recent_file_with_prefix(h5_fname_prefix, debug_lvl=debug_level)

Debug.dl("json_forecast_generator: Most recent file path: '{0}'.".format(most_recent_file_path), debug_level, 1)

if most_recent_file_path is None:
    Debug.dl("json_forecast_generator: Unable to define a proper timestamp.", debug_level, 1)
else:
    raw_content, file_timestamp = libs.read_h5file_content(most_recent_file_path, debug_lvl=debug_level)
    disch_stage_dict = libs.convert_dist_to_stage(raw_content, file_timestamp, debug_lvl=debug_level)
    libs.write_file(disch_stage_dict, sc_model_id, debug_lvl=debug_level)
