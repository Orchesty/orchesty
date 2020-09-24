import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import TopologyTreeView from 'components/topologyTreeView/TopologyTreeView';

import './LeftSideBar.less';

class LeftSideBar extends React.Component {

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
};

function mapStateToProps(state) {
  const {application} = state;
  return {
    showSideBar: application.showSideBar
  }
}

export default connect(mapStateToProps)(LeftSideBar);
