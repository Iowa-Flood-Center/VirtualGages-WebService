import json
import os


class Settings:

    _default_settings_file_name = "../../../conf/settings.json"
    _attributes = None

    @staticmethod
    def get(attribute_id):
        if Settings._attributes is None:
            Settings._read_file()
        return Settings._attributes[attribute_id] if attribute_id in Settings._attributes else None

    @staticmethod
    def _read_file():
        try:
            with open(Settings._default_settings_file_name, "r") as json_file:
                Settings._attributes = json.load(json_file)
        except IOError:
            print("Unable to read settings file. Exiting.")
            quit(1)

    def __init__(self):
        return
