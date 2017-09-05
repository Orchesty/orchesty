import React from 'react'
import { connect } from 'react-redux';

import './vendor/bootstrap/css/bootstrap.css';
import './vendor/font-awesome/css/font-awesome.css';
import './vendor/iCheck/skins/flat/green.css';
import './custom.css';

import LeftSidePanel from './containers/LeftSidePanel';
import TopNavigation from './containers/TopNavigation';
import ActivePage from './containers/ActivePage';
import Toaster from './containers/Toaster';
import ActiveModal from './containers/ActiveModal';

import './App.less';


class App extends React.Component {
  constructor(props) {
    super(props);
  }
  
  render() {
    return (
      <div className={this.props.showMenu ? 'main-app nav-md' : 'nav-sm'}>
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
  }
}

function mapStateToProps(state){
  const {application} = state;

  return {
    showMenu: application.showMenu
  }
}

export default connect(mapStateToProps)(App);