import React from 'react';
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as topologyActions from 'rootApp/actions/topologyActions';

import ConfirmDialog from 'rootApp/views/elements/dialogs/ConfirmDialog';

function mapStateToProps(state, ownProps){
  return {
    message: ownProps.message ? ownProps.message : 'Are you really sure?',
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    confirmAction: () => dispatch(topologyActions.topologyDelete(ownProps.topologyId, ownProps.redirectToList))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(ConfirmDialog);


