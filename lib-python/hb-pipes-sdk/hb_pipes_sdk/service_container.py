# encoding: utf-8
import logging
from abc import ABC, abstractmethod
from typing import NamedTuple

from .dto import Dto
from .exceptions import HbPipesProcessException

logger = logging.getLogger('hb_sites_sdk')


class ServiceResult(NamedTuple):
    data: dict
    headers: dict


class Services(ABC):

    @abstractmethod
    def process(self, param: Dto) -> ServiceResult:
        raise NotImplementedError('Implement method `process` first')


class ServiceContainer:
    services: dict = {}

    def add_service(self, name: str, service: Services):
        """

        :param name: str
        :param service: Services
        """
        if not self.is_service(name):
            if not isinstance(service, Services):
                raise HbPipesProcessException(f'Registered service name[{name}] must implement class `Service`')
            self.services[name] = service

    def is_service(self, name: str) -> bool:
        """

        :param name:
        :return: bool
        """
        if name not in self.services:
            return False
        else:
            return True

    def get_services(self) -> dict:
        """
        :return:  dict
        """
        return self.services

    def get_service(self, name) -> Services:
        """Get service from service stack and check if is registered correctly
        :param name: str
        :return: Services
        """
        if self.is_service(name):
            return self.services[name]
        else:
            raise HbPipesProcessException(f'Unregistered service name[{name}]')
