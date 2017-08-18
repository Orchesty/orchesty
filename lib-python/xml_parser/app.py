#!/usr/bin/env python
import logging.config
import os
import sys
import yaml

from __init__ import __version__, __cwd__
from errors.bad_request import BadRequest
from handler.xml_parser_error_handler import XmlParserErrorHandler
from handler.xml_parser_parse_handler import XmlParserParseHandler
from handler.xml_parser_version_handler import XmlParserVersionHandler
from hb_metrics.metrics import Metrics
from hb_metrics.service.udp_sender import UdpSender
from model.request_data import RequestData

os.environ.setdefault('PARSER_HOST', '127.0.0.1')
os.environ.setdefault('PARSER_PORT', '5000')
os.environ.setdefault('FLASK_DEBUG', '0')
os.environ.setdefault('PARSER_RELOADED', '1')
os.environ.setdefault('ANALYTICS_HOST', '127.0.0.1')
os.environ.setdefault('ANALYTICS_PORT', '5555')

try:
    import flask
    import flask_json
except ImportError as e:
    sys.stderr.write("Import error [{}]".format(e.output))
    sys.exit(0)

app = flask.Flask(__name__)
flask_json.FlaskJSON(app)


@app.route("/", methods=['GET'])
@app.route("/api/xml_parser/version/", methods=['GET'])
def api_xml_version():
    return XmlParserVersionHandler(__version__).handle()


@app.route("/api/xml_parser/parse", methods=['POST'])
def api_from_source():
    request = flask.request
    request_data = RequestData(request)
    handler = XmlParserParseHandler(request_data, analytics)
    return handler.handle()


@app.errorhandler(404)
def page_not_found(error):
    return XmlParserErrorHandler(error).handle()


@app.errorhandler(BadRequest)
def handle_bad_request(error):
    response = flask.jsonify(error.to_dict())
    response.status_code = error.status_code
    return response


if __name__ == "__main__":
    analytics = Metrics(UdpSender(os.environ.get('ANALYTICS_HOST'), os.environ.get('ANALYTICS_PORT')))

    config_file = '{}/config.yml'.format(__cwd__)
    
    if os.path.exists(config_file):
        with open(config_file, 'rt') as f:
            config = yaml.safe_load(f.read())
        logging.config.dictConfig(config)
    else:
        logging.basicConfig(level=logging.DEBUG)
    
    logger = logging.getLogger(__name__)
    app.run(host=os.environ.get('PARSER_HOST'), port=int(os.environ.get('PARSER_PORT')),
            use_reloader=os.environ.get('PARSER_RELOADED'))
