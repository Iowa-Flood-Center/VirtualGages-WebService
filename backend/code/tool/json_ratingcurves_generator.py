from libs.json_ratingcurves_generator_lib import write_file

from libs.classes.Settings import Settings
from libs.classes.DatabaseSettings import DatabaseSettings
from libs.classes.DatabaseProvider import DatabaseProvider

write_file(DatabaseProvider.get_virtualgages_ratingcurve())

print("Done.")
