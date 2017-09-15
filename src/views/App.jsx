import React from 'react'
import { connect } from 'react-redux';
import pages from '../config/pages';
import * as applicationActions from '../actions/applicationActions';


import './vendor/bootstrap/css/bootstrap.css';
import './vendor/font-awesome/css/font-awesome.css';
import './vendor/iCheck/skins/flat/green.css';
import './custom.css';

import LeftSidePanel from './containers/LeftSidePanel';
import TopNavigation from './containers/TopNavigation';
import ActivePage from './containers/ActivePage';
import Toaster from './containers/Toaster';
import ActiveModal from './containers/ActiveModal';
import LoginPage from './pages/nonAuth/LoginPage';
import RegistrationPage from './pages/nonAuth/RegistrationPage';
import ResetPasswordPage from './pages/nonAuth/ResetPasswordPage';
import SetPasswordPage from './pages/nonAuth/SetPasswordPage';
import ActivationPage from './pages/nonAuth/ActivationPage';
import Error404Page from './pages/nonAuth/Error404Page';

import './App.less';


class App extends React.Component {
  constructor(props) {
    super(props);
  }
  
  render() {
    const {showMenu, isLogged, page, selectPage} = this.props;
    const pageDef = pages[page.key];
    if (!isLogged && pageDef && pageDef.needAuth){
      selectPage('login');
      return null;
    } else {
      if (isLogged && (!pageDef || pageDef.needAuth)) {
        return (
          <div className={showMenu ? 'main-app nav-md' : 'nav-sm'}>
            <div className="container body">
              <div className="main_container">
                <LeftSidePanel />
                <TopNavigation />
                <ActivePage />
              </div>
              <Toaster />
            </div>
            <ActiveModal />
          </div>
        );
      } else {
        switch (page.key){
          case 'login':
            return <LoginPage {...page.args}/>;
          case 'registration':
            return <RegistrationPage {...page.args} />;
          case 'reset_password':
            return <ResetPasswordPage {...page.args} />;
          case 'set_password':
            return <SetPasswordPage {...page.args} />;
          case 'user_activation':
            return <ActivationPage {...page.args} />;
          default:
            return <Error404Page />;
        }
      }
    }
  }
}

function mapStateToProps(state){
  const {application, auth} = state;

  return {
    showMenu: application.showMenu,
    isLogged: Boolean(auth.user),
    page: application.selectedPage
  }
}

function mapActionsToProps(dispatch){
  return {
    selectPage: (key, args) => dispatch(applicationActions.selectPage(key, args))
  }
}

export default connect(mapStateToProps,mapActionsToProps)(App);