# encoding: utf-8
import flask
from flask import Response


def get_json_content(status, body, headers=None):
    # type: (int, str, dict) -> Response
    """
    Prepare json response
    :param status: int
    :param body: str
    :param headers: dict
    :return: Response
    """
    if headers is None:
        headers = {}

    response = flask.Response(status=status, content_type='application/json', headers=headers)
    response.set_data(str(body))

    return response


def get_xml_content(status, body, headers=None):
    # type: (int, str, dict) -> Response
    """
    Prepare xml response
    :param status: int
    :param body: str
    :param headers: dict
    :return: Response
    """
    if headers is None:
        headers = {}

    response = flask.Response(status=status, content_type='application/xml', headers=headers)
    response.set_data(str(body))

    return response
