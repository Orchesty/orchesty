# encoding: utf-8

import unittest

import mock

from model.cron_builder import CronBuilder


class CronBuilderTest(unittest.TestCase):
    @mock.patch('model.db.Db')
    @mock.patch('crontab.CronTab')
    def test_build(self, mock_db, mock_cron_tab):
        builder = CronBuilder(mock_db, mock_cron_tab)
