import React from 'react';
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as topologyActions from 'actions/topologyActions';
import * as applicationActions from 'actions/applicationActions';

import Page from 'wrappers/Page';
import TopologyListTable from 'components/topology/TopologyListTable';

class TopologyListPage extends React.Component {
  constructor(props) {
    super(props);
  }

  componentDidMount(){
    this._sendActions();
  }

  _sendActions(){
    const {setActions, openNewTopology} = this.props;
    const pageActions = [];
    if (openNewTopology) {
      pageActions.push({
        caption: 'Create topology',
        action: openNewTopology
      });
    }
    setActions(pageActions);
  }

  render() {
    return <TopologyListTable {...this.props} />;
  }
}

TopologyListPage.propTypes = {
  listId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  openTopologyList: PropTypes.func,
  closeTopologyList: PropTypes.func,
  openNewTopology: PropTypes.func
};

function mapStateToProps(state, ownProps){
  const {topology} = state;
  const list = topology.lists[ownProps.componentKey];
  return {
    list: list,
    elements: topology.elements,
    state: list && list.state
  }
}

function mapActionsToProps(dispatch, ownProps){
  const needList = (forced = false) => dispatch(topologyActions.needTopologyList(ownProps.componentKey));
  return {
    needList,
    notLoadedCallback: needList,
    listChangeSort: (sort) => dispatch(topologyActions.topologyListChangeSort(ownProps.componentKey, sort)),
    listChangePage: (page) => dispatch(topologyActions.topologyListChangePage(ownProps.componentKey, page)),
    listChangeFilter: (filter) => dispatch(topologyActions.topologyListChangeFilter(ownProps.componentKey, filter)),
    openModal: (id, data) => dispatch(applicationActions.openModal(id, data)),
    openNewTopology: () => dispatch(applicationActions.openModal('topology_edit', {addNew: true})),
    openPage: (key, args) => dispatch(applicationActions.openPage(key, args)),
    clone: id => dispatch(topologyActions.cloneTopology(id)),
    topologyDelete: id => dispatch(applicationActions.openModal('topology_delete_dialog', {topologyId: id})),
    publish: id => dispatch(topologyActions.publishTopology(id))
  }
}

export default Page(connect(mapStateToProps, mapActionsToProps)(TopologyListPage));