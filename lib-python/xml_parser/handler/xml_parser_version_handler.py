# encoding: utf-8
import httplib

from flask_json import json_response


class XmlParserVersionHandler:
    def __init__(self, version):
        self.version = version

    def handle(self):
        return json_response(status=httplib.OK, version=self.version)
