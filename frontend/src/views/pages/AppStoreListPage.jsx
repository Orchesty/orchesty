import React from 'react'
import { connect } from 'react-redux';
import Page from '../wrappers/Page';
import Panel from '../wrappers/Panel';
import * as appStoreActions from '../../actions/appStoreActions';
import AppStoreListTable from '../components/appStore/AppStoreListTable';

const mapStateToProps = ({ appStore }, { componentKey }) => {
  const { elements, lists: { [componentKey]: list, [componentKey]: { state } = { state: undefined } } } = appStore;

  return {
    list,
    state,
    elements,
  }
};

const mapDispatchToProps = (dispatch, { componentKey }) => {
  const needList = () => dispatch(appStoreActions.needAppStoreList(componentKey));

  return {
    needList,
    notLoadedCallback: needList,
    initialize: () => dispatch(appStoreActions.appStoreInitialize()),
  }
};

export default Page(Panel(connect(mapStateToProps, mapDispatchToProps)(AppStoreListTable), { title: 'Application Store' }));
