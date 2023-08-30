import React from 'react';
import {connect} from 'react-redux';
import config from '../config';
import * as applicationActions from 'actions/applicationActions';

import './vendor/bootstrap/css/bootstrap.css';
import './vendor/font-awesome/css/font-awesome.css';
import './vendor/iCheck/skins/flat/green.css';
import './custom.css';
import './App.less';

import LeftSideBar from 'containers/LeftSideBar';
import TopNavigation from 'containers/TopNavigation';
import ActivePage from 'containers/ActivePage';
import Toaster from 'containers/Toaster';
import ActiveModal from 'containers/ActiveModal';
import ActiveContextMenu from 'containers/ActiveContextMenu';
import LoginPage from 'pages/nonAuth/LoginPage';
import RegistrationPage from 'pages/nonAuth/RegistrationPage';
import ResetPasswordPage from 'pages/nonAuth/ResetPasswordPage';
import SetPasswordPage from 'pages/nonAuth/SetPasswordPage';
import ActivationPage from 'pages/nonAuth/ActivationPage';
import Error404Page from 'pages/nonAuth/Error404Page';

class App extends React.Component {
  constructor(props) {
    super(props);
    this.checkAuthPage(props);
  }

  componentDidUpdate(nextProps) {
    this.checkAuthPage(nextProps);
  }

  checkAuthPage(props) {
    const {isLogged, page, openPage} = props;
    if (page) {
      const pageDef = config.pages[page.key];
      if (!isLogged && pageDef && pageDef.needAuth) {
        openPage('login');
      }
    }
  }

  render() {
    const {isLogged, page} = this.props;
    const pageDef = page ? config.pages[page.key] : null;
    if (isLogged && (!pageDef || pageDef.needAuth)) {
      return (
        <div className="main-app">
          <TopNavigation/>
          <div className="content-area">
            <LeftSideBar/>
            <div className="content-container">
              <ActivePage/>
            </div>
          </div>
          <Toaster/>
          <ActiveModal/>
          <ActiveContextMenu/>
        </div>
      );
    } else {
      switch (page.key) {
        case 'login':
          return <LoginPage componentKey={page.key} {...page.args}/>;
        case 'registration':
          return <RegistrationPage componentKey={page.key} {...page.args} />;
        case 'reset_password':
          return <ResetPasswordPage componentKey={page.key} {...page.args} />;
        case 'set_password':
          return <SetPasswordPage componentKey={page.key} {...page.args} />;
        case 'user_activation':
          return <ActivationPage componentKey={page.key} {...page.args} />;
        default:
          return <Error404Page/>;
      }
    }
  }
}

function mapStateToProps(state) {
  const {application, auth} = state;

  return {
    isLogged: Boolean(auth.user),
    page: application.pages[application.selectedPage]
  }
}

function mapActionsToProps(dispatch) {
  return {
    openPage: (key, args) => dispatch(applicationActions.openPage(key, args))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(App);