import React from 'react'
import {connect} from 'react-redux';

import {stateType} from '../../../types';

import * as topologyActions from '../../../actions/topologyActions';
import * as notificationActions from '../../../actions/notificationActions';

import SimpleState from '../../elements/state/simpleState';
import BpmnIoComponent from '../../elements/bpmn/bpmnIoComponent';

class TopologyScheme extends React.Component {
  constructor(props) {
    super(props);
    this.state = {state: stateType.NOT_LOADED};
  }

  componentWillMount(){
    this.setState({state: stateType.LOADING});
    this.props.loadTopologySchema().then(response => {
      this.setState({state: stateType.SUCCESS});
    });
  }

  render() {
    const {schema, actions} = this.props;

    return (
      <SimpleState state={this.state.state}>
        <BpmnIoComponent
          schema={schema}
          onError={error => {this.props.addNotification('error', error)}}
          onImport={msg => {this.props.addNotification('success', msg)}}
          actions={actions}
        />
      </SimpleState>
    );
  }
}

function mapStateToProps(state, ownProps) {
  const {topology} = state;
  return {
    schema: topology.schemas[ownProps.schemaId]
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    loadTopologySchema: () => dispatch(topologyActions.loadTopologySchema(ownProps.schemaId, false)),
    addNotification: (type, message) => dispatch(notificationActions.addNotification(type, message))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyScheme);