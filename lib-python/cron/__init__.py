import logging.config
import os

import sys

__version__ = '1.0.0'
__cwd__ = os.path.dirname(os.path.realpath(__file__))
__database__ = 'cron'
__collection__ = 'cron_table'

config_file = '{0}/config.yml'.format(__cwd__)

sys.path.insert(0, '.')

if os.path.exists(config_file):
    import yaml
    
    with open(config_file, 'rt') as f:
        config = yaml.safe_load(f.read())
    logging.config.dictConfig(config)
else:
    logging.basicConfig(level=logging.DEBUG)
