import os
import sys

__version__ = '1.00'
__cwd__ = os.path.dirname(os.path.realpath(__file__))

# TODO temporarily, remove after creating own pip repository
sys.path.insert(0, '{}/../'.format(__cwd__))
sys.path.insert(0, '.')

"""
{
   "data":"",
   "validator":{
      "type":null,
      "file":null,
      "content":null
   }
}
"""
