# encoding: utf-8
import logging
import unittest

import mock
from lxml import etree

from errors.bad_request import BadRequest
from model.validator import Validator


class ValidatorTests(unittest.TestCase):
    def setUp(self):
        self.validators = [
            [{'type': '', 'file': '', 'content': ''}, {'type': None, 'file': None, 'content': None}, None],
            [{'type': 'dtd', 'file': '', 'content': None}, {'type': 'dtd', 'file': None, 'content': None}, etree.DTD],
            [{'type': 'xsd', 'file': '/path/to', 'content': ''},
             {'type': 'xsd', 'file': '/path/to', 'content': None}, etree.XMLSchema],
            [{'type': 'ng', 'file': '', 'content': '<a></a>'}, {'type': 'ng', 'file': None, 'content': '<a></a>'},
             etree.RelaxNG],
        ]

        self.validators2 = [
            [
                {'type': '', 'file': '', 'content': ''},
                None
            ],
            [
                {'type': 'dtd', 'file': '', 'content': "<!ELEMENT root EMPTY><!ATTLIST root xmlns CDATA #FIXED ''>"},
                etree.DTD
            ],
            [
                {'type': 'xsd', 'file': None,
                 'content': '<?xml version="1.0" encoding="UTF-8"?><xs:schema attributeFormDefault="unqualified" elementFormDefault="qualified" xmlns:xs="http://www.w3.org/2001/XMLSchema"><xs:element name="root" type="xs:string"/></xs:schema>'},
                etree.XMLSchema
            ],
            [
                {'type': 'rng', 'file': '',
                 'content': '<?xml version="1.0" encoding="UTF-8"?> <grammar ns="" xmlns="http://relaxng.org/ns/structure/1.0"><start><element name="root"><empty/></element></start> </grammar>'},
                etree.RelaxNG
            ],
        ]

    def test_from_request(self):
        for item, result, _ in self.validators:
            validator = Validator.from_request(item)
            self.assertEqual(validator.type, result['type'])
            self.assertEqual(validator.file, result['file'])
            self.assertEqual(validator.content, result['content'])

    def test_decode_charset(self):
        result = Validator.decode_charset("ěščřžýáíéĚŠČŘŽÝÁÍÉ")
        self.assertEquals(u"ěščřžýáíéĚŠČŘŽÝÁÍÉ", result)

        xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?> <index generated=\"2017-08-09\"></index>"
        assert_xml = u"<?xml version=\"1.0\" ?> <index generated=\"2017-08-09\"></index>"

        result = Validator.decode_charset(xml)
        self.assertEquals(assert_xml, result)

    def test_get_validate_object(self):
        for item, validator_class in self.validators2:
            result = Validator.from_request(item).get_validate_object()
            if validator_class is None:
                self.assertIsNone(result)
            else:
                self.assertIsInstance(result, validator_class)

    def test_exception_get_validate_object(self):
        for item, validator_class in self.validators2:
            if validator_class is not None:
                item['content'] = None

                with self.assertRaises(BadRequest):
                    logger = logging.getLogger('model.validator')
                    with mock.patch.object(logger, 'error'):
                        Validator.from_request(item).get_validate_object()
