# encoding: utf-8

import unittest

import mock

from model.request_data import RequestData


class RequestDataTests(unittest.TestCase):
    @mock.patch('flask.Request')
    def test_get_body(self, mock_flask_request):
        json = {'body': {'foo': 'bar'}}

        mock_flask_request.json = json
        request_data = RequestData(mock_flask_request)

        self.assertEqual(request_data.get_body(), json)
