# encoding: utf-8
import unittest

from hb_pipes_sdk.dto import Dto
from hb_pipes_sdk.exceptions import HbPipesProcessException
from hb_pipes_sdk.service_container import ServiceContainer, Services, ServiceResult


class ServiceContainerTests(unittest.TestCase):
    """Test ServiceContainer
    """
    service_container: ServiceContainer

    def setUp(self):
        class Test(Services):

            def process(self, param: Dto) -> ServiceResult:
                return ServiceResult({}, {})

        self.service_container = ServiceContainer()
        self.service_container.add_service('test', Test())
        self.service_container.add_service('test2', Test())

    def test_add_service(self):
        """Test adding service
        """
        self.assertEqual(2, len(self.service_container.get_services()))

    def test_is_service_true(self):
        """Test getting service True
        """
        self.assertTrue(self.service_container.is_service('test'))
        self.assertTrue(self.service_container.is_service('test2'))

    def test_is_service_false(self):
        """Test getting service False
        """
        self.assertFalse(self.service_container.is_service('test4'))

    def test_get_services_add_bad_class(self):
        """Test add service with bad registered service class
        """

        class TestNotService:

            @property
            def process(self):
                return None

        with self.assertRaises(HbPipesProcessException):
            self.service_container.add_service('test3', TestNotService())

    def test_get_services_right_class(self):
        """Test getting service with right registered service class
        """
        self.assertIsInstance(self.service_container.get_service('test'), Services)

    def test_get_services(self):
        """Test get all services from container
        """
        services = self.service_container.get_services()
        self.assertListEqual(list(services.keys()), ['test', 'test2'])
