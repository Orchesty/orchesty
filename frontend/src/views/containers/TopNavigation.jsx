import React from 'react'
import { connect } from 'react-redux';

import * as applicationActions from 'actions/applicationActions';
import GeneralSearch from 'components/search/GeneralSearch';
import TopMainMenu from './TopMainMenu';
import UserMenu from './UserMenu';

import './TopNavigation.less';

class TopNavigation extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div className="top-navigation">
        <div className="app-name"><i className="fa fa-connectdevelop" /> Pipes</div>
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
    toggleMainMenu: id => dispatch(applicationActions.toggleMainMenu())
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopNavigation);