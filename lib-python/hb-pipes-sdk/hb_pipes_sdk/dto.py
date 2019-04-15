# encoding: utf-8
from typing import NamedTuple

import flask
import flask_json

from .exceptions import HbPipesProcessException


class Dto(NamedTuple):
    """
    Data transfer object for putting params just use `dto = Dto(body, headers)`
    """
    body: str
    headers: dict

    def get_body(self) -> str:
        """
        :return: str
        """
        return self.body

    def get_headers(self) -> dict:
        """
        :return: dict
        """
        return self.headers

    pass


def get_dto_from_request(request: flask.Request) -> Dto:
    """
    Create Dto object from request just use `get_dto_from_request(flask.request)`
    :param request: flask.Request
    :return: Dto
    """
    try:
        request_dump = request.get_json()
        headers = {}
        for (key, value) in request.headers.items():
            headers[key] = value

        return Dto(request_dump, headers)
    except flask_json.JsonError as e:
        raise HbPipesProcessException('{}'.format(e.data['description']))
