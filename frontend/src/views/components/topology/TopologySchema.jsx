import React from 'react';
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import {stateType} from 'rootApp/types';

import processes from 'enums/processes';
import * as topologyActions from 'actions/topologyActions';
import * as notificationActions from 'actions/notificationActions';
import * as applicationActions from 'actions/applicationActions';

import SimpleState from 'elements/state/SimpleState';
import BpmnIoComponent from 'elements/bpmn/BpmnIoComponent';

class TopologySchema extends React.Component {
  constructor(props) {
    super(props);
    this.state = {state: stateType.NOT_LOADED};
    this.save = this.save.bind(this);
    this.schemaImported = this.schemaImported.bind(this);
  }

  componentDidMount(){
    this._needSchema(this.props);
  }

  UNSAFE_componentWillReceiveProps(nextProps){
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
    const {schema, setActions, setPanelActions, topology, addErrorNotification, saveProcessId, metricsRange, showEditorPropPanel, onPropPanelToggle} = this.props;
    return (
      <SimpleState state={this.state.state}>
        <BpmnIoComponent
          schema={schema}
          topologyName={topology ? topology.name : null}
          onError={addErrorNotification}
          onImport={this.schemaImported}
          setActions={setActions}
          setPanelActions={setPanelActions}
          onSave={this.save}
          saveProcessId={saveProcessId}
          topologyId={topology._id}
          metricsRange={metricsRange}
          showEditorPropPanel={showEditorPropPanel}
          onPropPanelToggle={onPropPanelToggle}
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
  setPanelActions: PropTypes.func.isRequired,
  schema: PropTypes.string,
  schemaId: PropTypes.string,
  topology: PropTypes.object,
  saveTopologySchema: PropTypes.func.isRequired,
  onChangeTopology: PropTypes.func.isRequired,
  onImport: PropTypes.func,
  saveProcessId: PropTypes.string,
  metricsRange: PropTypes.object
};

function mapStateToProps(state, ownProps) {
  const {topology, application} = state;
  return {
    showEditorPropPanel: application.showEditorPropPanel,
    schema: topology.schemas[ownProps.schemaId],
    saveProcessId: processes.topologySaveScheme(ownProps.schemaId)
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    loadTopologySchema: () => dispatch(topologyActions.loadTopologySchema(ownProps.schemaId, false)),
    addSuccessNotification: message => dispatch(notificationActions.addNotification('success', message)),
    addErrorNotification: error => dispatch(notificationActions.addNotification('error', error)),
    saveTopologySchema: schema => dispatch(topologyActions.saveTopologySchema(ownProps.schemaId, schema)),
    onPropPanelToggle: () => dispatch(applicationActions.editorPropPanelToggle())
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologySchema);