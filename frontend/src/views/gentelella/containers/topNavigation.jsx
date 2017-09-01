import React from 'react'
import { connect } from 'react-redux';

import * as applicationActions from '../../../actions/applicationActions';

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
              <a id="menu_toggle" onClick={this.props.toggleMainMenu}><i className="fa fa-bars"></i></a>
            </div>
            <ul className="nav navbar-nav navbar-right">
              <li>
                <a href="javascript:;" className="dropdown-toggle info-number">
                  <i className="fa fa-envelope-o"></i>
                </a>
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