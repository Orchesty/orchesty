# encoding: utf-8
import flask
from flask import Response


def get_json_content(status, body):
    # type: (int, str) -> Response
    """
    Prepare json response
    :param status: int
    :param body: str
    :return: Response
    """
    response = flask.Response(status=status, content_type='application/json')
    response.set_data(str(body))

    return response


def get_xml_content(status, body):
    # type: (int, str) -> Response
    """
    Prepare xml response
    :param status: int
    :param body: str
    :return: Response
    """
    response = flask.Response(status=status, content_type='application/xml')
    response.set_data(str(body))

    return response
