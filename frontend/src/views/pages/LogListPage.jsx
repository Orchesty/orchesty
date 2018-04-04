import React from 'react'
import {connect} from 'react-redux';

import * as logActions from 'actions/logActions';

import LogListTable from 'components/log/LogListTable';
import Page from 'wrappers/Page';
import Panel from 'wrappers/Panel';

function mapStateToProps(state, ownProps){
  const {log} = state;
  const list = log.lists[ownProps.componentKey];
  return {
    list: list,
    elements: log.elements,
    state: list && list.state
  }
}

function mapActionsToProps(dispatch, ownProps){
  const needList = forced => dispatch(logActions.needLogList(ownProps.componentKey));
  return {
    needList,
    notLoadedCallback: needList,
    listChangePage: (page) => dispatch(logActions.logListChangePage(ownProps.componentKey, page))
  }
}

export default Page(Panel(connect(mapStateToProps, mapActionsToProps)(LogListTable), {title: 'Log errors'}));
