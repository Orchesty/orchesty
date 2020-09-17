import React from 'react'
import { connect } from 'react-redux';

import config from 'rootApp/config';

import * as applicationActions from 'actions/applicationActions';

import GeneralSearch from 'components/search/GeneralSearch';
import TopMainMenu from './TopMainMenu';
import UserMenu from './UserMenu';

import './TopNavigation.less';
import logo from '../../static/logo.png';

class TopNavigation extends React.Component {
  constructor(props) {
    super(props);
    this.mainPageClick = this.mainPageClick.bind(this);
  }

  mainPageClick(e){
    const {openMainPage} = this.props;
    e.preventDefault();
    openMainPage();
  }

  render() {
    return (
      <div className="top-navigation">
        <div className="app-name" onClick={this.mainPageClick}><img src={logo} alt={"logo"}/></div>
        <TopMainMenu />
        <div className="middle-content" />
        <GeneralSearch />
        <UserMenu buttonClassName="top-menu-item"/>
      </div>
    );
  }
}

function mapStateToProps(state){
  return {
  }
}

function mapActionsToProps(dispatch){
  return {
    // toggleMainMenu: id => dispatch(applicationActions.toggleMainMenu()),
    openMainPage: () => dispatch(applicationActions.openPage(config.params.mainPage))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopNavigation);