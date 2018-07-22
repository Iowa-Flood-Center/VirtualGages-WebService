from scipy.interpolate import interp1d


class RatingCurve:

    _convert_functs = {}

    @staticmethod
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

        if link_id not in RatingCurve._convert_functs.keys():
            # converter_func = interp1d(all_disch, all_stage, kind='cubic')
            converter_func = interp1d(all_disch, all_stage, kind='linear')
            RatingCurve._convert_functs[link_id] = converter_func
        else:
            converter_func = RatingCurve._convert_functs[link_id]
        stage = float(converter_func(discharge))

        return stage

    def __init__(self):
        return
