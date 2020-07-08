import React from 'react';
import { connect } from 'react-redux';
import Page from '../wrappers/Page';
import Panel from '../wrappers/Panel';
import * as sdkImplsActions from '../../actions/sdkImplsActions';
import SdkImplsListTable from '../components/sdkImpls/SdkImplsListTable';
import * as applicationActions from '../../actions/applicationActions';

function mapStateToProps({ sdkImpls }, { componentKey }) {
  const { elements, lists: { [componentKey]: list, [componentKey]: { state } = { state: undefined } } } = sdkImpls;

  return {
    list,
    state,
    elements,
  }
}

const mapDispatchToProps = (dispatch, { componentKey }) => {
  const needList = () => dispatch(sdkImplsActions.needSdkImplsList(componentKey));

  return {
    needList,
    notLoadedCallback: needList,
    initialize: () => dispatch(sdkImplsActions.sdkImplsInitialize()),
    onChange: data => dispatch(applicationActions.openModal('sdk_change', { componentKey, data })),
    onRemove: id => dispatch(sdkImplsActions.remove(id, componentKey)),
  }
};

export default Page(Panel(connect(mapStateToProps, mapDispatchToProps)(SdkImplsListTable), { title: 'Services' }));
