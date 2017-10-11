import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as applicationActions from 'actions/applicationActions';
import * as topologyActions from 'actions/topologyActions';

import Page from 'wrappers/Page';
import TopologyDetail from 'components/topology/TopologyDetail';
import StateComponent from 'rootApp/views/wrappers/StateComponent';
import {stateType} from 'rootApp/types';
import processes from 'rootApp/enums/processes';

function mapStateToProps(state, ownProps) {
  const {topology, process} = state;
  const topologyEntity = topology.elements[ownProps.topologyId];
  return {
    state: topologyEntity ? stateType.SUCCESS : process[processes.topologyLoad(ownProps.topologyId)],
    pageTitle: topologyEntity ? `${topologyEntity.name} - ${topologyEntity.version}` : null,
    pageSubtitle: topologyEntity ? topologyEntity.descr : null,
    topology: topologyEntity
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    notLoadedCallback: () => dispatch(topologyActions.needTopology(ownProps.topologyId)),
    onChangeTopology: id => dispatch(applicationActions.changePageArgs(Object.assign({}, ownProps, {topologyId: id}))),
    onChangeTab: tabId => dispatch(applicationActions.changePageArgs(Object.assign({}, ownProps, {activeTab: tabId})))
  }
}

const TopologyPage = connect(mapStateToProps, mapActionsToProps)(StateComponent(Page(TopologyDetail)));

TopologyPage.propTypes = {
  topologyId: PropTypes.string.isRequired,
  activeTab: PropTypes.string
};

export default TopologyPage;