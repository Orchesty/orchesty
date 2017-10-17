# # encoding: utf-8
import unittest

from utils.pipes_headers import PipesHeaders
from flask import Request
from werkzeug.test import EnvironBuilder


class PipesHeaderTests(unittest.TestCase):

    def test_clear(self):
        """
        :return: None
        """
        builder = EnvironBuilder(method='POST', headers={"pf-token": "123", "content-type": "application/json"})
        env = builder.get_environ()
        request = Request(env)

        self.assertEquals({"pf-token": "123", "content-type": "application/json"}, PipesHeaders.clear(request.headers))

    def test_create_key(self):
        """
        :return: None
        """
        self.assertEquals("pf-token", PipesHeaders.create_key('token'))

    def test_get(self):
        """
        :return: None
        """

        self.assertEquals("123", PipesHeaders.get(PipesHeaders.NODE_ID, {"pf-node-id": "123"}))

    def test_debug_info(self):
        """
        :return: None
        """
        builder = EnvironBuilder(
            method='POST',
            headers={
                "pf-token": "123",
                "content-type": "application/json",
                "pf-correlation-id": "123",
                "pf-node-id": "456"
            }
        )
        env = builder.get_environ()
        request = Request(env)

        debug = {
            "pf-correlation-id": "123",
            "pf-node-id": "456"
        }

        self.assertEquals(debug, PipesHeaders.debug_info(request.headers))
