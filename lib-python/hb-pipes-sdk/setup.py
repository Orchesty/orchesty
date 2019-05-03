from setuptools import setup

setup(
    name='hb_pipes_sdk',
    # todo: add repo
    # url='https://gitlab.hanaboso.net/pipes/pipes',
    author='Pavel Severyn',
    author_email='severyn.p@hanaboso.com',
    maintainer='Hanaboso s.r.o.',
    python_requires='>=3.6',
    packages=['hb_pipes_sdk'],
    include_package_data=True,
    platforms='any',
    install_requires=['flask', 'flask_json', 'PyYAML'],
    version='1.0',
    license='MIT',
    description='Pipes SDK for custom python node',
    long_description=open('README.rst').read(),
)
