import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import {stateType} from 'rootApp/types';

import processes from 'enums/processes';
import * as topologyActions from 'actions/topologyActions';
import * as notificationActions from 'actions/notificationActions';

import SimpleState from 'elements/state/SimpleState';
import BpmnIoComponent from 'elements/bpmn/BpmnIoComponent';

class TopologySchema extends React.Component {
  constructor(props) {
    super(props);
    this.state = {state: stateType.NOT_LOADED};
    this.save = this.save.bind(this);
  }

  componentWillMount(){
    this._needSchema(this.props);
  }

  componentWillReceiveProps(nextProps){
    this._needSchema(nextProps);
  }

  _needSchema(props){
    if (props.schema === undefined && this.state.state == stateType.NOT_LOADED){
      this.setState({state: stateType.LOADING});
      props.loadTopologySchema().then(response => {
        this.setState({state: stateType.SUCCESS});
      });
    } else {
      this.setState({state: stateType.SUCCESS});
    }
  }

  save(xml){
    this.props.saveTopologySchema(xml).then(topology => {
      if (topology){
        if (topology._id !== this.props.schemaId){
          this.props.onChangeTopology(topology._id);
        }
      }
    });
  }

  render() {
    const {schema, setActions, addErrorNotification , addSuccessNotification, saveProcessId} = this.props;

    return (
      <SimpleState state={this.state.state}>
        <BpmnIoComponent
          schema={schema}
          onError={addErrorNotification}
          onImport={addSuccessNotification}
          setActions={setActions}
          onSave={this.save}
          saveProcessId={saveProcessId}
        />
      </SimpleState>
    );
  }
}

TopologySchema.propTypes = {
  loadTopologySchema: PropTypes.func.isRequired,
  addSuccessNotification: PropTypes.func.isRequired,
  addErrorNotification: PropTypes.func.isRequired,
  setActions: PropTypes.func.isRequired,
  schema: PropTypes.string,
  schemaId: PropTypes.string,
  saveTopologySchema: PropTypes.func.isRequired,
  onChangeTopology: PropTypes.func.isRequired,
  saveProcessId: PropTypes.string
};

function mapStateToProps(state, ownProps) {
  const {topology} = state;
  return {
    schema: topology.schemas[ownProps.schemaId],
    saveProcessId: processes.topologySaveScheme(ownProps.schemaId)
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    loadTopologySchema: () => dispatch(topologyActions.loadTopologySchema(ownProps.schemaId, false)),
    addSuccessNotification: message => dispatch(notificationActions.addNotification('success', message)),
    addErrorNotification: error => dispatch(notificationActions.addNotification('error', error)),
    saveTopologySchema: schema => dispatch(topologyActions.saveTopologySchema(ownProps.schemaId, schema))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologySchema);