import json
import os
import unittest

from model.writer import Writer


class WriterTest(unittest.TestCase):
    """

    """

    def test_is_attribute(self):
        """
        Test whether tag is attribute
        """
        tags = (
            ('@attr', True),
            ('@', True),
            ('attr@', False),
            ('a@ttr', False),
        )

        for tag, result in tags:
            self.assertEquals(result, Writer.is_attribute(tag))

    def test_is_value(self):
        """
        Test whether tag is tag value
        """
        tags = (
            ('#attr', True),
            ('#', True),
            ('attr#', False),
            ('a#ttr', False),
        )

        for tag, result in tags:
            self.assertEquals(result, Writer.is_value(tag))

    def test_remove_attribute_mark(self):
        """
        Test remove attribute char
        """
        tags = (
            ('@attr', 'attr'),
            ('@', ''),
            ('attr@', 'attr@'),
            ('a@ttr', 'a@ttr'),
        )

        for tag, result in tags:
            self.assertEquals(result, Writer.remove_attribute_mark(tag))

    def test_write(self):
        """
        Test write xml from json
        """
        for output_data, input_data in WriterTest.get_test_parse_data():
            if input_data and output_data:

                writer = Writer()
                result = writer.write(input_data)
                self.assertEquals(result, output_data)
            else:
                raise TypeError('Bad input file definition')

    @staticmethod
    def get_test_parse_data():
        """
        Data provider for test_write
        """
        p = os.path.dirname(os.path.realpath(__file__))
        files = (
            (
                '{0}/samples/out_data_empty.xml'.format(p),
                '{0}/samples/mock_data_empty.json'.format(p),
            ),
            (
                '{0}/samples/out_data_plain.xml'.format(p),
                '{0}/samples/mock_data_plain.json'.format(p)
            ),
            (
                '{0}/samples/out_data_attributes.xml'.format(p),
                '{0}/samples/mock_data_attributes.json'.format(p)
            ),
            (
                '{0}/samples/out_data_multiple.xml'.format(p),
                '{0}/samples/mock_data_multiple.json'.format(p)
            ),
            (
                '{0}/samples/out_data_multiple2.xml'.format(p),
                '{0}/samples/mock_data_multiple2.json'.format(p)
            ),
        )

        for _xml, _json in files:
            try:
                with open(_xml, 'r') as xml_source, open(_json, 'r') as json_result:
                    yield xml_source.read(), json.loads(json_result.read())
            except IOError:
                yield None, None
