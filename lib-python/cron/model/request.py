# encoding: utf-8
import logging

from flask.wrappers import Request

logger = logging.getLogger(__name__)


class Request:
    """
    
    """
    
    def __init__(self, request: Request):
        # type: (Request) -> None
        
        self.body = request.json
        self.headers = request.headers
        
        logger.debug('request body {}'.format(repr(self.body)))
        logger.debug('request header {}'.format(repr(self.headers)))
    
    def get_body(self):
        # type: () -> json
        return self.body
    
    def get_headers(self):
        # type: () -> dict
        return self.headers
