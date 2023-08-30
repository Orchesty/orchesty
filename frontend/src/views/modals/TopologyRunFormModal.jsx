import React from 'react';
import {connect} from 'react-redux';

import Modal from 'rootApp/views/wrappers/Modal';
import TopologyRunForm from 'rootApp/views/components/topology/TopologyRunForm';
import processes from 'rootApp/enums/processes';
import { getNodeRunUrl } from '../../actions/nodeActions';
import { makeStartingPointUrl } from '../../services/apiGatewayServer';

function mapStateToProps({ auth: { user: { id: userId } } }, { nodeId, nodeName, nodeType, topologyId, topologyName, componentKey }) {
  return {
    form: componentKey,
    processId: processes.nodeRun(nodeId),
    subTitle: makeStartingPointUrl(getNodeRunUrl(nodeId, nodeName, nodeType, topologyId, topologyName, userId))
  };
}

export default connect(mapStateToProps)(Modal(TopologyRunForm, {
  title: 'Run node',
  submitCaption: 'Run',
  closeCaption: 'Cancel',
  size: 'lg'
}));

