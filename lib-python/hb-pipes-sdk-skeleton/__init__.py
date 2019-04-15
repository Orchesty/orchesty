import logging.config
import os

import yaml

from __init__ import logger

__logger_name__ = 'hb_pipes_sdk'

config_file = f'{os.path.dirname(os.path.realpath(__file__))}/config.yml'

if os.path.exists(config_file):
    with open(config_file, 'rt') as f:
        config = yaml.safe_load(f.read())
        logging.config.dictConfig(config)
else:
    logging.basicConfig(level=logging.DEBUG)

logger = logging.getLogger(__logger_name__)

__all__ = ['logger', '__logger_name__']
