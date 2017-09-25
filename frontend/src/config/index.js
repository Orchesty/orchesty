import deepmerge from 'deepmerge';

import commonParams from 'config-common/params';
import envParams from 'config-env/params';
import commonPages from 'config-common/pages';
import envPages from 'config-env/pages';
import commonMainMenu from 'config-common/mainMenu';
import envMainMenu from 'config-env/mainMenu';
import commonServers from 'config-common/servers';
import envServers from 'config-env/servers';

export default {
  params: deepmerge(commonParams, envParams),
  pages: deepmerge(commonPages, envPages),
  mainMenu: [...commonMainMenu, ...envMainMenu],
  servers: deepmerge(commonServers, envServers)
};
