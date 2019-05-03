# encoding: utf-8
import logging
import os
import socket
import sys

try:
    import flask
    import flask_json
except ImportError as e:
    sys.stderr.write(f'Import error [{e}]\n')
    sys.exit(0)

logger = logging.getLogger('hb_sites_sdk')


class App:

    def __init__(self):
        """
        Init flask app
        """
        self.app = flask.Flask(__name__)
        flask_json.FlaskJSON(self.app)

    def run(self, host=None, port=None, debug=None, load_dotenv=True, **options):
        """
        Run flask server
        :param host:
        :param port:
        :param debug:
        :param load_dotenv:
        :param options:
        """
        if os.environ.get('FLASK_HOST') is not None:
            host = os.environ.get('FLASK_HOST')

        if os.environ.get('FLASK_PORT') is not None:
            port = os.environ.get('FLASK_PORT')

        try:
            self.app.run(host=host, port=port, debug=debug, load_dotenv=load_dotenv, **options)
        except PermissionError as error:
            sys.stderr.write(f'App error: {error}\n')
            sys.exit(1)
        except socket.gaierror as error:
            sys.stderr.write(f'App error[{host}:{port}]: {error}\n')
            sys.exit(1)

    def add_route(self, rule: str, endpoint: str = None, view_func: callable = None,
                  provide_automatic_options: bool = None, **options):
        """
        Add route rule to flask application
        :param rule:
        :param endpoint:
        :param view_func:
        :param provide_automatic_options:
        :param options:
        :return:
        """
        self.app.add_url_rule(rule, endpoint, view_func, provide_automatic_options, **options)

    def register_error_handler(self, code_or_exception, f):
        """
        Add error handler specified by param `code_or_exception`
        :param code_or_exception: int|Exception type of error
        :param f: callable handler
        """
        self.app.register_error_handler(code_or_exception, f)
