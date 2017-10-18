# encoding: utf-8
import logging

from flask import Response

from errors.bad_request import BadRequest
from handler.response_handler import get_xml_content
from model.request_data import RequestData
from model.validator import Validator
from model.writer import Writer

logger = logging.getLogger(__name__)


class XmlParserOutputHandler:
    def __init__(self, request_data, metrics):
        # type: (RequestData, hb_metrics.metrics.Analytics) -> None
        self.request_data = request_data
        self.metrics = metrics

    def handle(self):
        # type: () -> Response

        # todo: add right keys :D
        self.metrics.send({})

        if 'data' not in self.request_data.get_body():
            message = 'Missing `data` key'
            logger.error(message)
            raise BadRequest(message, 400)

        if 'config' in self.request_data.get_body() and 'validator' in self.request_data.get_body()['config']:
            validator = Validator().from_request(self.request_data.get_body()['config']['validator'])
        else:
            validator = Validator()

        writer = Writer(validator)

        # todo: add right keys :D
        self.metrics.send({})

        result = writer.write(self.request_data.get_body()['data'])
        response = get_xml_content(status=200, body=result, headers=self.request_data.get_headers())

        return response
