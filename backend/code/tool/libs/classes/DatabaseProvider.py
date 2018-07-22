from DatabaseSettings import DatabaseSettings
import psycopg2


class DatabaseProvider:

    _db_conn = None

    @staticmethod
    def get_virtualgages_ratingcurve():
        # open connection if needed
        if DatabaseProvider._db_conn is None:
            DatabaseProvider.open_db_connection()
            close_connection = True
        else:
            close_connection = False

        # perform SELECT query
        db_cur = DatabaseProvider._db_conn.cursor()
        db_cur.execute(DatabaseSettings.get("db_rc_query"))
        all_recs = db_cur.fetchall()
        db_cur.close()

        # close connection if needed
        if close_connection:
            DatabaseProvider.close_db_connection()

        # return
        return all_recs

    @staticmethod
    def open_db_connection():

        print("Connecting to:")
        print(" NAME: {0}".format(DatabaseSettings.get("db_name")))
        print(" USER: {0}".format(DatabaseSettings.get("db_user")))
        print(" PASS: {0}".format(DatabaseSettings.get("db_pass")))
        print(" HOST: {0}".format(DatabaseSettings.get("db_host")))
        print(" PORT: {0}".format(DatabaseSettings.get("db_port")))

        DatabaseProvider._db_conn = psycopg2.connect(
            database=DatabaseSettings.get("db_name"),
            user=DatabaseSettings.get("db_user"),
            password=DatabaseSettings.get("db_pass"),
            host=DatabaseSettings.get("db_host"),
            port=DatabaseSettings.get("db_port"))

        return

    @staticmethod
    def close_db_connection():
        if DatabaseProvider._db_conn is not None:
            DatabaseProvider._db_conn.close()
        return

    def __init__(self):
        return
