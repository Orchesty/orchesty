# encoding: utf-8
import json
import logging

from lxml import etree

from errors.bad_request import BadRequest
from model.validator import Validator

logger = logging.getLogger(__name__)


class Writer:
    def __init__(self, validator=None, encoding='utf-8'):
        # type: (Validator) -> None
        """
        :param validator: Validator
        :param encoding: str
        """
        if validator is None:
            validator = Validator()
        self.validator = validator
        self.encoding = str.upper(encoding)

    def write(self, src, pretty_print=False, xml_declaration=True):
        # type: (dict, bool, bool) -> str
        """
        input writer method
        :param src: dict
        :param pretty_print: bool
        :param xml_declaration: bool
        """
        for root, items in src.iteritems():
            root_tag = etree.Element(root)
            try:
                self.inner_decode(items, root_tag, self.encoding)
            except (ValueError, AttributeError) as e:
                logger.error(e)
                raise BadRequest('{}'.format(e), 400)

        tree = etree.ElementTree(root_tag)
        if self.validator.type == Validator.DTD and self.validator.file:
            tree.docinfo.system_url = self.validator.file

        return etree.tostring(tree, pretty_print=pretty_print, xml_declaration=xml_declaration, encoding=self.encoding)

    @staticmethod
    def inner_decode(items, root_tag, encoding):
        # type: (dict, lxml.etree.Element, str) -> None
        """
        write xml elements
        :param items: dict
        :param root_tag: lxml.etree.Element
        :param encoding: str
        """
        for tag, items in items.iteritems():
            if Writer.is_attribute(tag):
                a_tag = Writer.remove_attribute_mark(tag)
                root_tag.set(a_tag, items)
            else:
                if Writer.is_value(tag):
                    items = str(items) if type(items) == int else items
                    root_tag.text = unicode(items.encode(encoding), encoding) if items else ''
                else:
                    if type(items) == list:
                        for item in items:
                            multiple_root = etree.SubElement(root_tag, tag)
                            Writer.inner_decode(item, multiple_root, encoding)
                    else:
                        root = etree.SubElement(root_tag, tag)
                        Writer.inner_decode(items, root, encoding)

    @staticmethod
    def is_attribute(tag):
        # type: (str) -> str
        """
        Check whether tag is attribute
        :param tag: str
        :return: str
        """
        if tag and tag[0] == '@':
            return True
        else:
            return False

    @staticmethod
    def is_value(tag):
        # type: (str) -> str
        """
        Check whether tag is value
        :param tag: str
        :return: str
        """
        if tag and tag[0] == '#':
            return True
        else:
            return False

    @staticmethod
    def remove_attribute_mark(tag):
        # type: (str) -> str
        """
        Prepare tag name, remove attribute mark from start of tag
        :param tag: str
        :return: str
        """
        if Writer.is_attribute(tag):
            return tag[1:]
        else:
            return tag
