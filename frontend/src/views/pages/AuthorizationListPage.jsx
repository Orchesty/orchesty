import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as authorizationActions from '../../actions/authorizationActions';
import * as applicationActions from '../../actions/applicationActions';

import AuthorizationListTable from '../components/authorization/AuthorizationListTable';
import Page from '../wrappers/Page';


function mapStateToProps(state, ownProps){
  const {authorization} = state;
  const list = authorization.lists[ownProps.pageKey];
  return {
    list: list,
    elements: authorization.elements,
    state: list && list.state
  }
}

function mapActionsToProps(dispatch, ownProps){
  const needList = forced => dispatch(authorizationActions.needAuthorizationList(ownProps.pageKey));
  return {
    needList,
    notLoadedCallback: needList,
    listChangePage: (page) => dispatch(authorizationActions.authorizationsListChangePage(ownProps.pageKey, page)),
    editSettings: authorizationId => dispatch(applicationActions.openModal('authorization_settings_edit', {authorizationId})),
    authorize: authorizationId => dispatch(authorizationActions.authorize(authorizationId))
  }
}

export default Page(connect(mapStateToProps, mapActionsToProps)(AuthorizationListTable));