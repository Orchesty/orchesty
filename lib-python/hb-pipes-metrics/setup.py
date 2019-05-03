from setuptools import setup

setup(
    name='hb_pipes_metrics',
    # todo: add repo
    # url='https://gitlab.hanaboso.net/pipes/pipes',
    author='Pavel Severyn',
    author_email='severyn.p@hanaboso.com',
    maintainer='Hanaboso s.r.o.',
    python_requires='>=3.6',
    packages=['hb_pipes_metrics'],
    include_package_data=True,
    platforms='any',
    install_requires=['psutil'],
    version='1.1',
    license='MIT',
    description='Pipes metrics package',
    long_description=open('README.rst').read(),
)
