
class Debug:
    _pixel_x = 515
    _pixel_y = 530

    def __init__(self):
        return

    @staticmethod
    def db(txt_message, debug):
        """

        :param txt_message:
        :param debug:
        :return:
        """
        if debug:
            print(txt_message)

    @staticmethod
    def dl(txt_message, dbl_message, debug_level):
        """

        :param txt_message: Text to be presented
        :param dbl_message: Debug level of the message
        :param debug_level: Current debug level of the system
        :return: None
        """
        if dbl_message <= debug_level:
            print(txt_message)

    @classmethod
    def x(cls):
        return cls._pixel_x

    @classmethod
    def y(cls):
        return cls._pixel_y
