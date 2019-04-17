Hanaboso Pipes SDK Skeleton app
===============================

Install steps
-------------
* install package hb_pipes_sdk (*pip3 install -e <path>*)
* install package hb_pipes_metrics (*pip3 install -e <path>*)
* define service (must extend class hb_pipes_sdk.Services)
* define metrics handler (optional)
* define log target (__init__.py) (optional)

Sample node code
----------------
.. code-block:: python
    # import HB SDK
    from hb_pipes_sdk import apps, handlers, Dto, Services, ServiceResult

    # import metrics
    from hb_pipes_metrics import metrics, udp_sender


    # define service, must implement ABC class Services
    class Test(Services):
        def process(self, param: Dto) -> ServiceResult:
            res = {'status': 'ok'}
            return ServiceResult(res, param.headers)


    # define other service with custom service
    # from my_module.some_model_or_service import some_model_or_service
    class Test2(Services):
        def __init__(self, some_model_or_service):
            self.some_model_or_service = some_model_or_service

        def process(self, param: Dto) -> ServiceResult:
            res = self.some_model_or_service.do_something(param.body)
            return ServiceResult(res, param.headers)


    # register service
    handlers.get_service_container().add_service('custom', Test())

    # set metrics handler
    sender = udp_sender('127.0.0.1', 10000)
    metric = metrics(sender)
    handlers.set_metrics(metric)

    # run application
    # you can define host, port,debug and more choices in `run()` method or you can define environment (see bellow)
    # apps.run(host=192.168.0.1, port=8080, debug=True)
    apps.run()


Production server
-----------------
* run only on uWSGI server

Environment:
------------
.. code-block:: text

    FLASK_HOST=127.0.0.1
    FLASK_PORT=5000
    FLASK_ENV=production [development|production]
    FLASK_DEBUG=off [on|off]
