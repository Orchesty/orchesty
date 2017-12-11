#!/usr/bin/env python
import logging
import time
from threading import Thread

from crontab import CronTab

from model.cron_builder import CronBuilder
from model.db import Db

logger = logging.getLogger(__name__)


class CronService(Thread):
    """
    
    """
    run = True
    
    def __init__(self, db: Db, period: int = 1000):
        """
        
        :param db:
        :type db: Db
        :param period:
        :type period: int
        """
        super(CronService, self).__init__()
        self.db = db
        self.period = period
    
    def set_period(self, period: int):
        """
        
        :param period:
        :type period: int
        :return:
        """
        self.period = period
    
    def run(self):
        """
        
        :return:
        :rtype None
        """
        try:
            interval = int(int(self.period) / 1000)
        except TypeError as e:
            interval = 1
            logger.error("{}".format(e))
        
        logger.debug('Set Interval:{}s'.format(interval))
        
        while self.run:
            builder = CronBuilder(self.db, CronTab(user=True))
            builder.build()
            time.sleep(interval)
    
    def stop(self):
        self.run = False
