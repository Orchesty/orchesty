import React from 'react';
import {connect} from 'react-redux';

import ConfirmDialog from 'rootApp/views/elements/dialogs/ConfirmDialog';

function mapStateToProps(state, ownProps){
  return {
    message: ownProps.message ? ownProps.message : 'Topology has unsaved changes. Do you want continue and lost them all?',
  };
}

function mapActionsToProps(dispatch, { onClose }){
  return {
    confirmAction: () => onClose()
  }
}


export default connect(mapStateToProps, mapActionsToProps)(ConfirmDialog);
