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
    this.schemaImported = this.schemaImported.bind(this);
  }

  componentWillMount(){
    this._needSchema(this.props);
  }

  componentWillReceiveProps(nextProps){
    this._needSchema(nextProps);
  }

  _needSchema(props){
    if (props.schema === undefined){
      if (this.state.state !== stateType.LOADING) {
        this.setState({state: stateType.LOADING});
        props.loadTopologySchema().then(response => {
          this.setState({state: stateType.SUCCESS});
        });
      }
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

  schemaImported(msg){
    const {addSuccessNotification, onImport} = this.props;
    addSuccessNotification(msg);
    if (onImport){
      onImport();
    }
  }

  render() {
    const {schema, setActions, topology, addErrorNotification, saveProcessId} = this.props;

    return (
      <SimpleState state={this.state.state}>
        <BpmnIoComponent
          schema={schema}
          schemaTitle={topology ? `${topology.name}-${topology.version}` : null}
          onError={addErrorNotification}
          onImport={this.schemaImported}
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
  topology: PropTypes.object,
  saveTopologySchema: PropTypes.func.isRequired,
  onChangeTopology: PropTypes.func.isRequired,
  onImport: PropTypes.func,
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