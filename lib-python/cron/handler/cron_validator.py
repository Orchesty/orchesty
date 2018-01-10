# encoding: utf-8

from crontab import CronSlices


class CronValidator:
    @staticmethod
    def is_time_valid(time: str):
        """
        
        :param time:
        :type time: str
        :return:
        """
        return CronSlices.is_valid(time)
