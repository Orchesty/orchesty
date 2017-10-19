# encoding: utf-8
import httplib
import logging

import hb_metrics.metrics
import response_handler
from errors.bad_request import BadRequest
from model.reader import Reader
from model.request_data import RequestData
from model.validator import Validator
from utils.pipes_headers import PipesHeaders

logger = logging.getLogger(__name__)


class XmlParserParseHandler:
    def __init__(self, request_data, metrics):
        # type: (RequestData, hb_metrics.metrics.Metrics) -> None
        self.request_data = request_data
        self.metrics = metrics

    def handle(self):
        # type: () -> flask.Response
        """Handle request and process input parsing

        :rtype: flask.Response
        """
        # TODO: add right keys :D
        self.metrics.send({})

        if 'data' not in self.request_data.get_body():
            message = 'Missing `data` key'
            logger.error(message)
            raise BadRequest(message, 400)

        if 'config' in self.request_data.get_body() and 'validator' in self.request_data.get_body()['config']:
            validator = Validator().from_request(self.request_data.get_body()['config']['validator'])
        else:
            validator = Validator()

        reader = Reader(validator)

        status = httplib.OK  # 200
        data = reader.parse(self.request_data.get_body()['data'])
        # TODO: add right keys :D
        self.metrics.send({})

        result = response_handler.get_json_content(status=status, body=data, headers=self.create_headers())

        return result

    def create_headers(self):
        """
        :return: dict
        """
        headers = self.request_data.get_headers()
        headers[PipesHeaders.create_key(PipesHeaders.RESULT_CODE)] = 0
        headers[PipesHeaders.create_key(PipesHeaders.RESULT_MESSAGE)] = 'XML to JSON parsing was successful.'

        return headers
