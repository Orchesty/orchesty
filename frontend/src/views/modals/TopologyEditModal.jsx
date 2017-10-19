import React from 'react'
import {connect} from 'react-redux';

import TopologyForm from 'components/topology/TopologyForm';
import processes from "rootApp/enums/processes";
import Modal from 'rootApp/views/wrappers/Modal';

function mapStateToProps(state, ownProps) {
  const {componentKey, addNew, topologyId} = ownProps;
  return {
    form: componentKey + (addNew ? 'new' : topologyId),
    processId: addNew ? processes.topologyCreate(componentKey) : processes.topologyUpdate(topologyId),
    title: (addNew ? 'New' : 'Edit') + ' topology'
  };
}

export default connect(mapStateToProps)(Modal(TopologyForm, {
  size: 'md'
}));