import React from 'react'
import {connect} from 'react-redux';

import * as authorizationActions from 'actions/authorizationActions';
import * as applicationActions from 'actions/applicationActions';

import AuthorizationListTable from 'components/authorization/AuthorizationListTable';
import Page from 'wrappers/Page';
import Panel from 'wrappers/Panel';

function mapStateToProps(state, ownProps){
  const {authorization, process} = state;
  const list = authorization.lists[ownProps.componentKey];
  return {
    list: list,
    elements: authorization.elements,
    state: list && list.state
  }
}

function mapActionsToProps(dispatch, ownProps){
  const needList = forced => dispatch(authorizationActions.needAuthorizationList(ownProps.componentKey));
  return {
    needList,
    notLoadedCallback: needList,
    listChangePage: (page) => dispatch(authorizationActions.authorizationsListChangePage(ownProps.componentKey, page)),
    editSettings: authorizationId => dispatch(applicationActions.openModal('authorization_settings_edit', {authorizationId})),
    authorize: authorizationId => dispatch(authorizationActions.authorize(authorizationId))
  }
}

export default Page(Panel(connect(mapStateToProps, mapActionsToProps)(AuthorizationListTable), {title: 'Authorizations'}));