import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as applicationActions from '../../actions/applicationActions';

import Page from '../wrappers/Page';
import TopologyDetail from '../components/topology/TopologyDetail';

function mapActionsToProps(dispatch, ownProps){
  return {
    onChangeTopology: id => dispatch(applicationActions.changePageArgs(Object.assign({}, ownProps, {topologyId: id}))),
    onChangeTab: tabId => dispatch(applicationActions.changePageArgs(Object.assign({}, ownProps, {activeTab: tabId})))
  }
}

var TopologyPage = connect(() => ({}), mapActionsToProps)(Page(TopologyDetail));

TopologyPage.propTypes = {
  topologyId: PropTypes.string.isRequired,
  activeTab: PropTypes.string
};

export default TopologyPage;