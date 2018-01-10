from handler.cron_validator import CronValidator
from model.db import Db


class CronHandlerBase:
    """
    
    """

    def __init__(self, db: Db) -> object:
        """
        :param db:
        """
        self.db = db
    
    @staticmethod
    def valid_time(time: str) -> bool:
        """
        :param time: str:
        :return bool:
        """
        return CronValidator.is_time_valid(time)
