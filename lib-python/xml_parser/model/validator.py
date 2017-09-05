# encoding: utf-8
import logging
import re
from StringIO import StringIO

from lxml import etree

from errors.bad_request import BadRequest

logger = logging.getLogger(__name__)


class Validator:
    DTD = 'dtd'
    XSD = 'xsd'
    RNG = 'rng'

    def __init__(self, validator_type=None, validator_file=None, validator_content=None):
        self.type = validator_type
        self.file = validator_file
        self.content = self.decode_charset(validator_content)

    @staticmethod
    def from_request(validator):
        validator_type = validator['type'] if 'type' in validator and validator['type'] else None
        validator_file = validator['file'] if 'file' in validator and validator['file'] else None
        validator_content = validator['content'] if 'content' in validator and validator['content'] else None

        return Validator(validator_type=validator_type, validator_file=validator_file,
                         validator_content=validator_content)

    def get_validate_object(self):

        if self.type == self.DTD:
            return self._get_dtd()
        elif self.type == self.XSD:
            return self._get_xsd()
        elif self.type == self.RNG:
            return self._get_ng()
        else:
            return None

    def _get_dtd(self):
        if self.content:
            f = StringIO(self.content)
            return etree.DTD(f)
        elif self.file:
            return etree.DTD(self.file)
        else:
            message = 'DTD validation is bad configured'
            logger.error(message)
            raise BadRequest(message, 400)

    def _get_xsd(self):
        if self.content:
            f = StringIO(self.content)
            parsed = etree.parse(f)
            return etree.XMLSchema(parsed)
        elif self.file:
            parsed = etree.parse(self.file)
            return etree.XMLSchema(parsed)
        else:
            message = 'XSD validation is bad configured'
            logger.error(message)
            raise BadRequest(message, 400)

    def _get_ng(self):
        if self.content:
            f = StringIO(self.content)
            parsed = etree.parse(f)
            return etree.RelaxNG(parsed)
        elif self.file:
            parsed = etree.parse(self.file)
            return etree.RelaxNG(parsed)
        else:
            message = 'RelaxNG validation is bad configured'
            logger.error(message)
            raise BadRequest(message, 400)

    @staticmethod
    def decode_charset(content, encoding='utf-8'):
        if content:
            content = re.sub(r'\bencoding="[-\w]+"', '', content, count=1)
            return unicode(content, encoding)
        else:
            return content
