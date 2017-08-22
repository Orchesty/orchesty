import React from 'react'
import { connect } from 'react-redux';

import './vendor/bootstrap/css/bootstrap.css';
import './vendor/font-awesome/css/font-awesome.css';
import './custom.css';

import LeftSidePanel from './containers/left_side_panel';
import TopNavigation from './containers/top_navigation';
import ActivePage from './containers/active_page';
import Toaster from './containers/toaster';
import ActiveModal from './containers/active_modal';

import './app.less';


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