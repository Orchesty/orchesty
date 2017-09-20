import commonParams from 'config-common/params';
import envParams from 'config-env/params';
import commonPages from 'config-common/pages';
import envPages from 'config-env/pages';
import commonMainMenu from 'config-common/mainMenu.json';
import envMainMenu from 'config-env/mainMenu.json';


export default {
  params: Object.assign({}, commonParams, envParams),
  pages: Object.assign({}, commonPages, envPages),
  mainMenu: [...commonMainMenu, ...envMainMenu]
};