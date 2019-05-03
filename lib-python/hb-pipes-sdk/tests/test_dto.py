# encoding: utf-8
import unittest

import flask_json
import mock
from flask import json

from hb_pipes_sdk.dto import get_dto_from_request
from hb_pipes_sdk.exceptions import HbPipesProcessException


def se_bad_body():
    raise flask_json.JsonError()


class ServiceContainerTests(unittest.TestCase):

    @mock.patch('flask.Request')
    def test_get_dto_from_request(self, mock_flask_request):
        """Test prepare Dto from request
        :param mock_flask_request: flask.Request
        """
        body = {'body': {'foo': 'bar'}}
        headers = {'Accept': 'application/json'}

        mock_flask_request.get_json.return_value = json.dumps(body)
        mock_flask_request.headers = headers

        dto = get_dto_from_request(mock_flask_request)

        self.assertDictEqual(body, json.loads(dto.get_body()))
        self.assertDictEqual(headers, dto.get_headers())

    def test_get_dto_from_request_bad_body(self):
        """Test prepare Dto from request
        """

        def get_json():
            error = flask_json.JsonError(**{'description': 'error description'})
            raise error

        request = mock.Mock()
        request.get_json.side_effect = get_json
        request.headers = {}

        with self.assertRaises(HbPipesProcessException):
            get_dto_from_request(request)
