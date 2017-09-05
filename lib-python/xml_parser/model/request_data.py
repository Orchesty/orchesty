# encoding: utf-8
import flask


class RequestData:
    """
    
    """
    def __init__(self, request):
        # type: (flask.request) -> None
        
        self.request_dump = request.json

    def get_body(self):
        # type: () -> json
        return self.request_dump
