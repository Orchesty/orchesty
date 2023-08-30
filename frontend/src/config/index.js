import deepmerge from 'deepmerge';

import commonParams from 'config-common/params';
import envParams from 'config-env/params';
import commonPages from 'config-common/pages';
import envPages from 'config-env/pages';
import commonServers from 'config-common/servers';
import envServers from 'config-env/servers';

export default {
  params: deepmerge(commonParams, envParams),
  pages: deepmerge(commonPages, envPages),
  servers: deepmerge(commonServers, envServers),
};
