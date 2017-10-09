import React from 'react';
import {connect} from 'react-redux';

import Modal from 'rootApp/views/wrappers/Modal';
import TopologyRunForm from 'rootApp/views/components/topology/TopologyRunForm';
import processes from 'rootApp/enums/processes';

function mapStateToProps(state, ownProps) {
  return {
    form: ownProps.componentKey,
    processId: processes.nodeRun(ownProps.nodeId)
  };
}

export default connect(mapStateToProps)(Modal(TopologyRunForm, {
  title: 'Run node',
  submitCaption: 'Run',
  closeCaption: 'Cancel',
  size: 'lg'
}));

