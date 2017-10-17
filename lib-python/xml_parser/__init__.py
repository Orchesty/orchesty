"""
Parse from XML
--------------
URL:/api/xml-parser/parse

Field 'data' must contains escaped string of input xml!!!

POST request BODY =>

{
    "data": "<root><item type=\"int\"></item></root>",
    "config": {
        "validator": {
            "type": null,
            "file": null,
            "content": null
        }
    }
}

RESPONSE => 200
{
    "root": {
        "item": {
            "#": null
            "@type": "int"
        }
    }
}

Response has 'application/json' content type if everything is right. In other cases output contains error information.

ERROR => 400
{
    "message": "Opening and ending tag mismatch: rosot line 1 and root, line 1, column 52 (line 1)"
}
ERROR => 400
{
    "message": "None (line 0)"
}

========================================================================================================================

Write from JSON
---------------
URL:/api/xml-parser/decode
URL:/api/xml-parser/write

Field 'data' must contains json object!!!

POST request BODY =>

{
    "data":{"root": {"item": {"#": 1}}},
    "config":{
        "validator":{
            "type":null, "file":null
        }
    }
}

RESPONSE => 200
<?xml version='1.0' encoding='UTF-8' ?>
<root>
    <item />
</root>

Response has 'application/xml' content type if everything is right. In other cases output contains error information.

ERROR => 400
{
    "message": "Expecting ',' delimiter: line 1 column 12 (char 11)"
}
ERROR => 400
{
    "message": "Invalid tag name u'asd#'"
}
"""
import logging
import os
import sys

import yaml

__version__ = '1.00'
__cwd__ = os.path.dirname(os.path.realpath(__file__))

# TODO temporarily, remove after creating own pip repository
sys.path.insert(0, '{}/../'.format(__cwd__))
sys.path.insert(0, '.')

config_file = '{0}/config.yml'.format(__cwd__)

if os.path.exists(config_file):
    with open(config_file, 'rt') as f:
        config = yaml.safe_load(f.read())
    logging.config.dictConfig(config)
else:
    logging.basicConfig(level=logging.DEBUG)

