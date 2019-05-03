# encoding: utf-8
import logging
import sys
import traceback
from abc import ABC, abstractmethod

import flask
from flask import Response

import hb_pipes_metrics
import hb_pipes_sdk.dto
from .exceptions import HbPipesSdkException
from .output_render import OutputRenderer
from .service_container import ServiceContainer
from .service_container import Services

logger = logging.getLogger('hb_sites_sdk')


class Handlers(ABC):
    """
    Base abstract handler class
    """
    _service_container: ServiceContainer
    _output_renderer: OutputRenderer
    _metrics: hb_pipes_metrics.metrics = None
    _version: str

    def __init__(self, service_container: ServiceContainer,
                 output_renderer: OutputRenderer, version: str = None):
        """
        Init class
        :param service_container:
        :param output_renderer:
        """
        self._version = version
        self._output_renderer = output_renderer
        self._service_container = service_container

    def get_output_renderer(self) -> OutputRenderer:
        """

        :return: OutputRenderer
        """
        return self._output_renderer

    def get_service_container(self) -> ServiceContainer:
        """

        :return: ServiceContainer
        """
        return self._service_container

    def set_output_renderer(self, renderer: OutputRenderer):
        """

        :param renderer: OutputRenderer
        """
        self._output_renderer = renderer

    def set_metrics(self, metrics: hb_pipes_metrics.metrics):
        """Set metrics handler
        :param metrics:
        """
        self._metrics = metrics

    def get_metrics(self) -> hb_pipes_metrics.metrics:
        """Return metrics handler
        :return:  hb_pipes_metrics.metrics
        """
        return self._metrics

    @abstractmethod
    def api_version(self) -> Response:
        pass

    @abstractmethod
    def api_custom_list(self) -> Response:
        pass

    @abstractmethod
    def api_services_process_test(self, name: str) -> Response:
        pass

    @abstractmethod
    def api_services_process(self, name: str) -> Response:
        pass

    @abstractmethod
    def error_not_found(self, error) -> Response:
        pass

    @abstractmethod
    def error_hb_pipes_exception(self, error: HbPipesSdkException) -> Response:
        pass

    @abstractmethod
    def error_exception(self, error) -> Response:
        pass


class HbPipesHandlers(Handlers):

    # route /
    def api_version(self) -> Response:
        """Get api version

        :return: Response
        """
        return self.get_output_renderer().render({'message': self._version})

    # route /custom_node/list
    def api_custom_list(self) -> Response:
        """Return list of all registered services

        :return: Response
        """
        services: list = []
        for key in self.get_service_container().get_services().keys():
            services.append(key)

        return self.get_output_renderer().render(services)

    # route /custom_node/<name>/process
    def api_services_process_test(self, name: str) -> Response:
        """Test whether is service with `name` was registered

        :param name: str
        :return: Response
        """
        logger.info(f'Handler api_services_process_test => param: {name}')
        if self.get_service_container().is_service(name):
            return self.get_output_renderer().render({'message': 'ok'})
        else:
            logger.error({'message': f'service name={name} not found'})
            return self.get_output_renderer().render({'message': f'service name={name} not found'}, 404)

    # route /custom_node/<name>/process
    def api_services_process(self, name: str) -> Response:
        """Process input data base on service name, service have to be registered before start app
        `handlers.get_service_container().add_service('service_name', callable_handler_method)`
        The only first call on the same service name is accepted

        :param name: str
        :return: Response
        """
        if self.get_service_container().is_service(name):
            params = hb_pipes_sdk.dto.get_dto_from_request(flask.request)

            service_class: Services = self.get_service_container().get_service(name)
            result = service_class.process(params)

            if self.get_metrics():
                # todo add right keys
                # self.get_metrics().send({'field': 'name'})
                pass

            headers = {}
            for (key, value) in flask.request.headers.items():
                headers[key] = value
            return self.get_output_renderer().render(result.data, 200, headers=headers)
        else:
            headers = {}
            for (key, value) in flask.request.headers.items():
                headers[key] = value

            logger.error({'message': f'service {name} not registered'})
            return self.get_output_renderer().render({'message': f'service {name} not registered'}, 404)

    # http 404
    def error_not_found(self, error) -> Response:
        """Catch http 404 error raise by flask app
        :param error:
        :return: Response
        """
        logger.error(f'Handler error {error}')
        return self.get_output_renderer().render(data={'message': str(error)}, status=400)

    # exception based on HbPipesSdkException
    def error_hb_pipes_exception(self, error: HbPipesSdkException) -> Response:
        """Catch all app defined exception as input format decode

        :param error: HbPipesSdkException
        :return: Response
        """
        logger.error(f'Handler error {error.to_dict()} {error.get_status()}')
        return self.get_output_renderer().render(data=error.to_dict(), status=error.get_status())

    # rest exceptions
    def error_exception(self, error) -> Response:
        """Catch rest app exceptions and raise http_status 500 at all

        :param error: Exception
        :return: Response
        """
        traceback.print_exc(file=sys.stdout)
        logger.error(f'Handler error {error}')
        return self.get_output_renderer().render(data={'message': str(error)}, status=500)
