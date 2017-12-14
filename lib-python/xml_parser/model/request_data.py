# encoding: utf-8
import flask
import logging

from utils.pipes_headers import PipesHeaders

logger = logging.getLogger(__name__)


class RequestData:
    """
    
    """

    def __init__(self, request):
        # type: (flask.request) -> None

        self.request_dump = request.json
        self.headers = PipesHeaders.clear(request.headers)
        logger.debug('request body {}'.format(repr(self.request_dump)))
        logger.debug('request header {}'.format(repr(self.headers)))

    def get_body(self):
        # type: () -> json
        return self.request_dump

    def get_headers(self):
        # type: () -> dict
        return self.headers
