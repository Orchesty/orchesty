Version 1.1
===========

Example of use:
---------------
* define sender params

.. code-block:: python

    sender = UdpSender('127.0.0.1', 10000)

* instate Metric class

.. code-block:: python

    metric = Metrics(sender)

* send metrics data

.. code-block:: python

    metric.send({'test': 1234}, 'host')



Build package
-------------
python3 setup.py bdist_wheel

Install package
---------------
pip install -e <path> - from source

pip install ??? - from github

