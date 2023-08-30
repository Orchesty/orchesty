import commonMainMenu from 'config-common/mainMenu';
import envMainMenu from 'config-env/mainMenu';

export default (dispatch => [...commonMainMenu(dispatch), ...envMainMenu(dispatch)]);
