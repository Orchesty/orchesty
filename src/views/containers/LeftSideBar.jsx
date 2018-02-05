import React from 'react'
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import * as authActions from 'actions/authActions';

import TopologyTreeView from 'components/topologyTreeView/TopologyTreeView';

import './LeftSideBar.less';

class LeftSideBar extends React.Component {
  constructor(props) {
    super(props);
    this.logout = this.logout.bind(this);
  }

  logout(e){
    e.preventDefault();
    this.props.logout();
  }

  render() {
    const {showSideBar} = this.props;
    if (showSideBar) {
      return (
        <div className="left-side-bar">
          <div className="left-side-bar-content">
            <h3>Topologies</h3>
            <div className="main-tree-view">
              <TopologyTreeView componentKey="left-side-bar"/>
            </div>
          </div>
          <div className="side-bar-footer">
            <a className="footer-btn" title="Settings">
              <span className="glyphicon glyphicon-cog"/>
            </a>
            <a className="footer-btn" title="FullScreen">
              <span className="glyphicon glyphicon-fullscreen"/>
            </a>
            <a className="footer-btn" title="Lock">
              <span className="glyphicon glyphicon-eye-close"/>
            </a>
            <a className="footer-btn" onClick={this.logout} title="Logout">
              <span className="glyphicon glyphicon-off"/>
            </a>
          </div>

        </div>
      );
    } else {
      return null;
    }
  }
}

LeftSideBar.propTypes = {
  showSideBar: PropTypes.bool.isRequired,
  logout: PropTypes.func.isRequired,
};

function mapStateToProps(state){
  const {application} = state;
  return {
    showSideBar: application.showSideBar
  }
}

function mapActionsToProps(dispatch){
  return {
    logout: () => dispatch(authActions.logout())
  }
}

export default connect(mapStateToProps, mapActionsToProps)(LeftSideBar);
