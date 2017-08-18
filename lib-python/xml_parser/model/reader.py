# encoding: utf-8
import json
import logging
import re
from collections import defaultdict

from lxml import etree
from lxml.etree import XMLSyntaxError

from errors.bad_request import BadRequest
from model.validator import Validator

logger = logging.getLogger(__name__)


class Reader:
    def __init__(self, validator=None):
        # type: (Validator) -> None
        if validator is None:
            validator = Validator()
        self.validator = validator

    def parse(self, src):
        try:
            # TODO: try find better way
            xml = re.sub(r'\bencoding="[-\w]+"', '', src, count=1)
            src = xml.encode('utf-8')
            xml = etree.fromstring(src, parser=etree.XMLParser(encoding='utf-8'))
        except XMLSyntaxError as e:
            logger.error(e)
            raise BadRequest('{}'.format(e), 400)

        validate_object = self.validator.get_validate_object()
        if validate_object:
            if not validate_object.validate(xml):
                logger.error(validate_object.error_log.last_error)
                raise BadRequest('{}'.format(validate_object.error_log.last_error), 400)()

        parsed = self.parse_inner_element(xml)
        if len(xml.keys()):
            self.add_attributes(parsed, xml.keys(), xml.values())

        return json.dumps({xml.tag: parsed})

    def parse_inner_element(self, element):
        # type: (lxml.etree.Element) -> object
        """

        :param element: lxml.etree.Element
        """
        result = defaultdict(list)

        elements = list(element)
        if len(elements):
            for item in elements:
                if self.is_multiple(element, item.tag):
                    parsed = self.parse_inner_element(item)

                    if len(item.keys()):
                        self.add_attributes(parsed, item.keys(), item.values())
                    result[item.tag].insert(0, parsed)
                else:
                    parsed = self.parse_inner_element(item)

                    if len(item.keys()):
                        self.add_attributes(parsed, item.keys(), item.values())
                    result[item.tag] = parsed

        else:
            if len(element.keys()):
                self.add_attributes(result, element.keys(), element.values())
            result['#'] = element.text

        return result

    @staticmethod
    def is_multiple(element, name):
        # type: (lxml.etree.Element, str) -> bool

        """
        :type element: lxml.etree.Element
        :type name: str
        :rtype: bool
        """
        tags = [item.tag for item in list(element) if item.tag == name]
        return len(tags) > 1

    @staticmethod
    def add_attributes(parsed, keys, values):
        # type: (defaultdict(list), list, list) -> None

        """
        :param parsed: defaultdict(list)
        :param item: Element
        """
        attributes = dict(zip(keys, values))
        for key, value in attributes.iteritems():
            parsed['@{}'.format(key)] = value
