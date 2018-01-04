import React from 'react'
import { connect } from 'react-redux';

import * as applicationActions from 'actions/applicationActions';
import GeneralSearch from 'rootApp/views/components/search/GeneralSearch';

import './TopNavigation.less';

class TopNavigation extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div className="top_nav">
        <div className="nav_menu">
          <nav>
            <div className="nav toggle">
              <a id="menu_toggle" onClick={this.props.toggleMainMenu}><i className="fa fa-bars" /></a>
            </div>
            <ul className="nav navbar-nav navbar-right">
              <li className="col col-md-5 col-sm-5 col-xs-12">
                <GeneralSearch />
              </li>
            </ul>
          </nav>
        </div>
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