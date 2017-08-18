# encoding: utf-8
import httplib

import response_handler


class XmlParserErrorHandler:
    def __init__(self, error):
        self.error = error

    def handle(self):
        return response_handler.get_json_content(status=httplib.NOT_FOUND, body=self.error)
