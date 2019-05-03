# encoding: utf-8

"""Hanaboso Pipes SDK

example of use:
===============
# import HB SDK
from hb_pipes_sdk import apps, handlers, Dto, Services, ServiceResult

# define service, have to implement ABC class Services
class Test(Services):
    def process(self, param: Dto) -> ServiceResult:
        res = {'status': 'ok'}
        return ServiceResult(res, param.headers)

# define other service with custom service
from my_module.some_model_or_service import some_model_or_service

class Test2(Services):
    def __init__(self, some_model_or_service):
        self.some_model_or_service = some_model_or_service

    def process(self, param: Dto) -> ServiceResult:
        res = self.some_model_or_service.do_something(param.body)
        return ServiceResult(res, param.headers)


# register service
handlers.get_service_container().add_service('custom', Test())

# run application
# you can define host, port,debug and more choices in `run()` method or you can define environment (see bellow)
# apps.run(host=192.168.0.1, port=8080, debug=True)
apps.run()

Environment:
===========
FLASK_HOST=127.0.0.1
FLASK_PORT=5000
FLASK_ENV=production [development|production]
FLASK_DEBUG=off [on|off]


"""

import hb_pipes_sdk.app
import hb_pipes_sdk.dto
import hb_pipes_sdk.exceptions
import hb_pipes_sdk.handlers
import hb_pipes_sdk.output_render
import hb_pipes_sdk.service_container

__version__ = '0.2'
__logger__ = 'hb_pipes_sdk'

Dto = hb_pipes_sdk.dto.Dto
Services = hb_pipes_sdk.service_container.Services
ServiceResult = hb_pipes_sdk.service_container.ServiceResult

service_container = hb_pipes_sdk.service_container.ServiceContainer()
output_renderer = hb_pipes_sdk.output_render.HbPipesOutputRenderer()

handlers = hb_pipes_sdk.handlers.HbPipesHandlers(service_container, output_renderer, version=__version__)
apps = hb_pipes_sdk.app.App()

# register base routes
# / get api version
# /custom_node/list - get list of registered services
# /custom_node/<name>/process/test - test whether service is registered
# /custom_node/<name>/process - process node function
#
# is possible add next route rules, just insert code `apps.add_route('/', view_func=handlers.api_version, methods=['GET'])`

apps.add_route('/', view_func=handlers.api_version, methods=['GET'])
apps.add_route('/custom_node/list', view_func=handlers.api_custom_list, methods=['GET'])
apps.add_route('/custom_node/<name>/process/test', view_func=handlers.api_services_process_test, methods=['GET'])
apps.add_route('/custom_node/<name>/process', view_func=handlers.api_services_process, methods=['POST'])

# register error handlers
# 404 - catch flask exception if called url doesn't registered
# HbPipesSdkException - all app defined exception
# Exception - rest app exceptions like socket error, TypeError etc.
# is possible to change this handlers, just call `apps.register_error_handler(404, other_handler)`

apps.register_error_handler(404, handlers.error_not_found)
apps.register_error_handler(hb_pipes_sdk.exceptions.HbPipesSdkException, handlers.error_hb_pipes_exception)
apps.register_error_handler(Exception, handlers.error_exception)

__all__ = ['app', 'dto', 'service_container', 'output_render', 'handlers', 'exceptions', 'apps', 'handlers',
           'Dto', 'Services', 'ServiceResult', '__version__', '__logger__']
