import React from 'react'
import { connect } from 'react-redux';
import * as authActions from '../../actions/authActions';

import SideMenuPanel from './SideMenuPanel';


class LeftSidePanel extends React.Component {
  constructor(props) {
    super(props);
    this.logout = this.logout.bind(this);
  }

  logout(e){
    e.preventDefault();
    this.props.logout();
  }

  render() {
    return (
      <div className="col-md-3 left_col">
        <div className="left_col scroll-view">
          <div className="navbar nav_title" style={{border: 0}}>
            <a href="#" className="site_title"><i className="fa fa-connectdevelop" /> <span>Pipes manager</span></a>
          </div>
          <div className="clearfix"></div>
          <SideMenuPanel />

          <div className="sidebar-footer hidden-small">
            <a data-toggle="tooltip" data-placement="top" title="" data-original-title="Settings">
              <span className="glyphicon glyphicon-cog" aria-hidden="true" />
            </a>
            <a data-toggle="tooltip" data-placement="top" title="" data-original-title="FullScreen">
              <span className="glyphicon glyphicon-fullscreen" aria-hidden="true" />
            </a>
            <a data-toggle="tooltip" data-placement="top" title="" data-original-title="Lock">
              <span className="glyphicon glyphicon-eye-close" aria-hidden="true" />
            </a>
            <a data-toggle="tooltip" data-placement="top" title="" onClick={this.logout} data-original-title="Logout">
              <span className="glyphicon glyphicon-off" aria-hidden="true" />
            </a>
          </div>
        </div>
      </div>
    );
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    logout: () => dispatch(authActions.logout())
  }
}

export default connect(null, mapActionsToProps)(LeftSidePanel);
