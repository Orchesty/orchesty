# encoding: utf-8
import json
import os
import unittest
from collections import defaultdict

import lxml

from model.reader import Reader


class ReaderTests(unittest.TestCase):
    """

    """

    def test_is_multiple(self):
        """
        Test whether has nod is multiple
        """
        xml = (
            ('<root><item><id></id><rows><row>1</row><row>2</row></rows></item></root>', 'root', False),
            ('<item><id></id><rows><row>1</row><row>2</row></rows></item>', 'rows', False),
            ('<rows><row>1</row><row>2</row></rows>', 'row', True),
            ('<rows><row>1</row><row>2</row></rows>', 'row', True),
        )
        for src, tag, result in xml:
            element = lxml.etree.fromstring(src)
            is_multiple = Reader.is_multiple(element, tag)
            self.assertEquals(is_multiple, result)

    def test_add_attributes(self):
        """
        Test adding attributes
        """
        parsed = defaultdict(list)
        parsed['root'] = {'item': 1}

        Reader.add_attributes(parsed, ['a', 'b', 'c'], ['1', '2', '3'])
        self.assertEquals(json.dumps(parsed), '{"@b": "2", "@c": "3", "@a": "1", "root": {"item": 1}}')

    def test_parse(self):
        """
        Test parse input file
        """
        for xml, assert_file in self.get_test_parse_data():
            if xml and assert_file:
                reader = Reader()
                result = reader.parse(xml)
                self.assertDictEqual(json.loads(result), assert_file)
            else:
                raise TypeError('Bad input file definition')

    @staticmethod
    def get_test_parse_data():
        """
        Data provider for test_parse
        """
        p = os.path.dirname(os.path.realpath(__file__))
        files = (
            (
                '{0}/samples/mock_data_empty.xml'.format(p),
                '{0}/samples/mock_data_empty.json'.format(p)
            ),
            (
                '{0}/samples/mock_data_plain.xml'.format(p),
                '{0}/samples/mock_data_plain.json'.format(p)
            ),
            (
                '{0}/samples/mock_data_attributes.xml'.format(p),
                '{0}/samples/mock_data_attributes.json'.format(p)
            ),
            (
                '{0}/samples/mock_data_multiple.xml'.format(p),
                '{0}/samples/mock_data_multiple.json'.format(p)
            ),
            (
                '{0}/samples/mock_data_multiple2.xml'.format(p),
                '{0}/samples/mock_data_multiple2.json'.format(p)
            ),
        )
        for _xml, _json in files:
            try:
                with open(_xml, 'r') as xml_source, open(_json, 'r') as json_result:
                    yield xml_source.read(), json.loads(json_result.read())

            except IOError:
                yield None, None
