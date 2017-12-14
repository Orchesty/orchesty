#!/usr/bin/env python
# encoding: utf-8
import logging
import logging.config
import os
import sys

from __init__ import __version__
from errors.bad_request import BadRequest
from handler.response_handler import get_json_content
from handler.xml_parser_error_handler import XmlParserErrorHandler
from handler.xml_parser_output_handler import XmlParserOutputHandler
from handler.xml_parser_parse_handler import XmlParserParseHandler
from handler.xml_parser_version_handler import XmlParserVersionHandler
from hb_metrics.metrics import Metrics
from hb_metrics.service.udp_sender import UdpSender
from model.request_data import RequestData

os.environ.setdefault('PARSER_HOST', '0.0.0.0')
os.environ.setdefault('PARSER_PORT', '80')
os.environ.setdefault('FLASK_DEBUG', '0')
os.environ.setdefault('PARSER_RELOADED', '1')
os.environ.setdefault('METRICS_HOST', '127.0.0.1')
os.environ.setdefault('METRICS_PORT', '5555')

try:
    import flask
    import flask_json
except ImportError as e:
    sys.stderr.write("Import error [{}]".format(e.output))
    sys.exit(0)

app = flask.Flask(__name__)
flask_json.FlaskJSON(app)


@app.route("/", methods=['GET'])
@app.route("/version/", methods=['GET'])
def api_xml_version():
    return XmlParserVersionHandler(__version__).handle()


@app.route("/xml-to-json", methods=['POST'])
def api_from_source():
    request = flask.request
    request_data = RequestData(request)
    handler = XmlParserParseHandler(request_data, metrics)
    return handler.handle()


@app.route("/json-to-xml", methods=['POST'])
def api_to_destination():
    request = flask.request
    request_data = RequestData(request)
    handler = XmlParserOutputHandler(request_data, metrics)
    return handler.handle()


@app.route("/xml-to-json/test", methods=['GET'])
@app.route("/json-to-xml/test", methods=['GET'])
def api_test():
    return get_json_content(200, '')


@app.errorhandler(404)
def page_not_found(error):
    return XmlParserErrorHandler(error).handle()


@app.errorhandler(BadRequest)
def handle_bad_request(error):
    response = flask.jsonify(error.to_dict())
    response.status_code = error.status_code
    return response


if __name__ == "__main__":
    metrics = Metrics(UdpSender(os.environ.get('METRICS_HOST'), os.environ.get('METRICS_PORT')))

    logger = logging.getLogger(__name__)

    app.run(
        host=os.environ.get('PARSER_HOST'),
        port=int(os.environ.get('PARSER_PORT')),
        use_reloader=os.environ.get('PARSER_RELOADED')
    )
