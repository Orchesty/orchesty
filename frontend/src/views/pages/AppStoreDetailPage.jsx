import React from 'react'
import { connect } from 'react-redux';
import Page from '../wrappers/Page';
import Panel from '../wrappers/Panel';
import { stateType } from '../../types';
import * as appStoreActions from '../../actions/appStoreActions';
import AppStoreDetail from '../components/appStore/AppStoreDetail';

const mapStateToProps = () => ({ state: stateType.SUCCESS });

const mapActionsToProps = dispatch => ({
  getApplication: (application, user) => dispatch(appStoreActions.getApplication(application, user)),
  changeApplication: (application, user, data, settings) => dispatch(appStoreActions.changeApplication(application, user, data, settings)),
  installApplication: (application, user) => dispatch(appStoreActions.installApplication(application, user)),
  uninstallApplication: (application, user) => dispatch(appStoreActions.uninstallApplication(application, user)),
  authorizeApplication: (application, user, redirect) => dispatch(appStoreActions.authorizeApplication(application, user, redirect)),
  subscribeApplication: (application, user, data) => dispatch(appStoreActions.subscribeApplication(application, user, data)),
  unsubscribeApplication: (application, user, data) => dispatch(appStoreActions.unsubscribeApplication(application, user, data)),
});

export default Page(Panel(connect(mapStateToProps, mapActionsToProps)(AppStoreDetail), { title: 'Application Store' }));
