# encoding: utf-8
import unittest
from unittest import mock

from flask import json

from hb_pipes_sdk import Services
from hb_pipes_sdk.dto import Dto
from hb_pipes_sdk.handlers import HbPipesHandlers
from hb_pipes_sdk.output_render import HbPipesOutputRenderer
from hb_pipes_sdk.service_container import ServiceContainer, ServiceResult


class HbPipesHandlersTest(unittest.TestCase):
    service_container: ServiceContainer

    def setUp(self) -> None:
        super().setUp()

        class Test(Services):

            def process(self, param: Dto) -> ServiceResult:
                data = json.loads(param.get_body())
                return ServiceResult(data, param.get_headers())

        self.service_container = ServiceContainer()
        self.service_container.add_service('test', Test())
        self.service_container.add_service('test2', Test())

    @mock.patch('hb_pipes_sdk.output_render.HbPipesOutputRenderer.render')
    def test_api_version(self, output_renderer):
        """Test call api_version handler
        :param output_renderer:
        """
        handler = HbPipesHandlers(self.service_container, HbPipesOutputRenderer(), '1.0.0')
        handler.api_version()

        output_renderer.assert_called_once_with({'message': '1.0.0'})

    @mock.patch('hb_pipes_sdk.output_render.HbPipesOutputRenderer.render')
    def test_api_custom_list(self, output_renderer):
        """ Test call api_custom_list
        :param output_renderer:
        """
        handler = HbPipesHandlers(self.service_container, HbPipesOutputRenderer(), '1.0.0')
        handler.api_custom_list()

        output_renderer.assert_called_once_with(['test', 'test2'])

    @mock.patch('hb_pipes_sdk.output_render.HbPipesOutputRenderer.render')
    def test_api_services_process_test(self, output_renderer):
        """Test call api_services_process_test
        :param output_renderer:
        """
        handler = HbPipesHandlers(self.service_container, HbPipesOutputRenderer(), '1.0.0')

        handler.api_services_process_test('test')
        output_renderer.assert_called_once_with({'message': 'ok'})

        handler.api_services_process_test('test3')
        output_renderer.assert_called_with({'message': f'service name=test3 not found'}, 404)

    @mock.patch('hb_pipes_sdk.output_render.HbPipesOutputRenderer.render')
    @mock.patch('hb_pipes_sdk.dto.get_dto_from_request', return_value=Dto('{"foo":"bar"}', {}))
    def test_api_services_process(self, dto_from_request, output_renderer):
        """Test call api_services_process
        :type output_renderer:
        """
        with mock.patch('flask.request'):
            handler = HbPipesHandlers(ServiceContainer(), HbPipesOutputRenderer(), '1.0.0')
            handler.api_services_process('test')

            dto_from_request.assert_called_once()
            output_renderer.assert_called_once_with({'foo': 'bar'}, 200, headers={})
