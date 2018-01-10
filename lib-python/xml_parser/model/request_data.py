# encoding: utf-8
import logging

import flask
import logging
import flask_json

from errors.bad_request import BadRequest
from utils.pipes_headers import PipesHeaders

logger = logging.getLogger(__name__)


class RequestData:
    """
    
    """
    request_dump = {}
    
    def __init__(self, request):
        # type: (flask.request) -> None
        
        try:
            logger.debug('request body {}'.format(repr(request.get_data())))
        except Exception:
            pass
        
        try:
            self.request_dump = request.json
        except flask_json.JsonError as e:
            raise BadRequest('{}'.format(e.data['description']), 400)
        
        self.headers = PipesHeaders.clear(request.headers)
        logger.debug('request body {}'.format(repr(self.request_dump)))
        logger.debug('request header {}'.format(repr(self.headers)))
    
    def get_body(self):
        # type: () -> json
        return self.request_dump
    
    def get_headers(self):
        # type: () -> dict
        return self.headers
