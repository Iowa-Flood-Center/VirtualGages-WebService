import json
import os


class Settings:

    _default_settings_file_name = "../../conf/settings.json"
    _attributes = None

    @classmethod
    def get(cls, attribute_id):
        if cls._attributes is None:
            cls._read_file()
        return cls._attributes[attribute_id] if attribute_id in cls._attributes else None

    @classmethod
    def _read_file(cls):
        print("Reading file: {0}".format(cls._default_settings_file_name))
        try:
            with open(cls._default_settings_file_name, "r") as json_file:
                cls._attributes = json.load(json_file)
        except IOError:
            print("Unable to read settings file. Exiting.")
            quit(1)

    def __init__(self):
        return
