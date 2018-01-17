#!/usr/bin/env python
# encoding: utf-8
import logging

from crontab import CronTab

from model.db import Db

logger = logging.getLogger(__name__)


class CronBuilder:
    def __init__(self, db: Db, cron_tab: CronTab) -> object:
        """
        
        :param db: database class
        :type: Db
        :param cron_tab:
        :type cron_tab: CronTab
        """
        self.db = db
        self.cron_tab = cron_tab

    def build(self) -> bool:
        """
        
        :return:
        :rtype: bool
        """
        logger.debug('start build cron')
        exist = []
        self.cron_tab.remove_all()
        for row in self.db.get_all():
            if row['command'] not in exist:
                job = self.cron_tab.new(row['command'])
                job.setall(row['time'])
                logger.debug('{}'.format(repr(job)))
                exist.append(row['command'])

        self.cron_tab.write()
        del self.cron_tab

        logger.debug('finish build cron')
        return True
