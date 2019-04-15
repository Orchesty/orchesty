# encoding: utf-8
import logging
from abc import ABC, abstractmethod

import flask
from flask import json

logger = logging.getLogger('hb_sites_sdk')


class OutputRenderer(ABC):
    """Abstract class for generate output response from registered methods
    `renderer = Renderer()
    handlers.set_output_renderer(renderer)`
    Default renderer is HbPipesOutputRenderer

    """

    @abstractmethod
    def render(self, data: any, status: int = 200, content_type: str = 'application/json',
               headers: dict = None) -> flask.Response:
        raise NotImplementedError('Implement method `render` first')


class HbPipesOutputRenderer(OutputRenderer):
    def render(self, data: any, status: int = 200, content_type: str = 'application/json',
               headers: dict = None) -> flask.Response:
        """Render json pipes format

        :param data:
        :param status:
        :param content_type:
        :param headers:
        :return:
        """
        if headers is None:
            headers = {}

        response = flask.Response(status=status, content_type=content_type, headers=headers)
        response.set_data(json.dumps(data))

        return response
