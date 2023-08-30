import React from 'react';
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import * as topologyActions from 'actions/topologyActions';
import * as metricsActions from 'actions/metricsActions';
import * as nodeActions from 'actions/nodeActions';

import 'diagram-js/assets/diagram-js.css';
import 'bpmn-js/dist/assets/bpmn-font/css/bpmn-embedded.css';
import './BpmnIoComponent.less';
import './custom-modeler/assets/elements.css';

import emptySchema from './empty-schema.bpmn';

import CustomBPMNModeler from './custom-modeler';
import download from 'utils/download';
import {menuItemType} from 'rootApp/types';
import SidebarNodeMetrics from 'rootApp/views/components/metrics/SidebarNodeMetrics';
import SidebarTopologyMetrics from 'rootApp/views/components/metrics/SidebarTopologyMetrics';
import SidebarStartingPointUrl from 'rootApp/views/components/node/SidebarStartingPointUrl';
import serverRequest from 'services/apiGatewayServer';

class BpmnIoComponent extends React.Component {
  constructor(props) {
    super(props);
    this._modeler = null;
    this._changed = false;
    this._saved = false;
    this._fileInputElement = null;
    this._canvasBpmnElement = null;
    this._propertiesElement = null;
    this.setFileInput = this.setFileInput.bind(this);
    this.setCanvasElement = this.setCanvasElement.bind(this);
    this.setPropertiesElement = this.setPropertiesElement.bind(this);
    this.selectionChanged = this.selectionChanged.bind(this);
    this.propPanelToggle = this.propPanelToggle.bind(this);
    this.changed = this.changed.bind(this);
    this.state = {
      selectedId: null
    }
  }

  _sendActions() {
    this.props.setActions([
      {
        caption: 'Save',
        type: menuItemType.ACTION,
        action: this.saveBPMN.bind(this),
        disabled: !this._changed,
        processId: this.props.saveProcessId
      },
      {
        caption: 'Import / Export',
        type: menuItemType.SUB_MENU,
        items: [
          {
            type: menuItemType.ACTION,
            caption: 'Import BPMN',
            action: this.importBPMN.bind(this)
          },
          {
            type: menuItemType.ACTION,
            caption: 'Export BPMN',
            action: this.exportBPMN.bind(this)
          },
          {
            type: menuItemType.ACTION,
            caption: 'Export SVG',
            action: this.exportSVG.bind(this)
          }
        ]
      }
    ]);
    this.props.setPanelActions([
      {
        type: menuItemType.ACTION,
        caption: 'Toggle info box',
        icon: 'fa fa-info dark',
        action: () => this.props.onPropPanelToggle()
      },
    ]);
  }

  componentDidMount() {
    this.createBpmn();
    this._sendActions();
  }

  componentWillUnmount() {
    this.props.setActions(null);
    if (this._modeler) {
      this._modeler.detach();
    }
  }

  componentDidUpdate(prevProps) {
    if (prevProps.schema !== this.props.schema) {
      this.loadXML();
    }
  }

  createBpmn() {
    const {metrics} = this.props;

    this._modeler = new CustomBPMNModeler({
      propertiesPanel: {
        parent: this._propertiesElement
      },
    });
    this._modeler.attachTo(this._canvasBpmnElement);
    this.loadXML();
    this._modeler.get('eventBus').on('commandStack.changed', this.changed);
    this._modeler.get('eventBus').on('selection.changed', this.selectionChanged);

    this._modeler.get('eventBus').on('shape.added', (event) => {
      let element = event.element;

      if (element.labelTarget || !element.businessObject.$instanceOf('bpmn:FlowNode')) {
        return;
      }

      if (element.businessObject.pipesType === 'user') {
        const node = Object.values(this.props.node.elements).filter(n => n.topology_id === this.props.topologyId && n.schema_id === element.businessObject.id)[0];
        const req = node
          ? `/longRunning/id/topology/${this.props.topologyId}/node/${node._id}/getTasks`
          : `/longRunning/name/topology/${this.props.topologyName}/node/${element.businessObject.name}/getTasks`;

        serverRequest(() => {
        }, 'GET', req, null).then(response => {
          this._modeler.get('overlays').add(element, 'bubbles', {
            position: {top: -25, right: 10},
            html: `<div onClick="window.location.href = '/ui/human_tasks?topology=${this.props.topologyId}${node ? `&node=${node._id}` : ""}'"><span class="badge" title="Waiting tasks">` + response.total + '</span></div>'
          });
        });

        return;
      }

      let errors = '';

      if (metrics[element.id] && metrics[element.id].data.process.errors > 0) {
        errors = metrics[element.id].data.process.errors;
      }

      this._modeler.get('overlays').add(element, 'bubbles', {
        position: {top: -25, right: 10},
        html: '<div><span class="badge badge-error" title="Failed processes">' + errors + '</span></div>'
      });
    });
  }

  selectionChanged(event) {
    const {selectedId} = this.state;
    if (event.newSelection.length > 0) {
      if (selectedId !== event.newSelection[0].id) {
        this.setState({selectedId: event.newSelection[0].id});
      }
    } else if (selectedId) {
      this.setState({selectedId: null});
    }
  }

  setFileInput(element) {
    if (element !== this._fileInputElement) {
      this._fileInputElement = element;
      if (this._fileInputElement) {
        this._fileInputElement.addEventListener('change', e => {
          const reader = new FileReader();
          const file = e.target.files[0];
          reader.onload = response => {
            this.openBPMN({file, content: response.target.result});
          };
          reader.readAsText(file);
        });
      }
    }
  }

  setCanvasElement(element) {
    this._canvasBpmnElement = element;
  }

  setPropertiesElement(element) {
    this._propertiesElement = element;
  }

  changed() {
    const {onChange, topologyId} = this.props;

    if (!this._saved) {
      if (!this._changed) {
        this._changed = true;
        onChange(topologyId);
        this._sendActions();
      }
    }

    this._saved = false;
  }

  propPanelToggle(e) {
    const {onPropPanelToggle} = this.props;
    e.preventDefault();
    onPropPanelToggle();
  }

  async loadXML() {
    if (this._modeler) {
      try {
        const schema = (this.props.schema && this.props.schema !== '') ? this.props.schema : emptySchema;
        await this._modeler.importXML(schema);
      } catch (err) {
        this.props.onError(String(err));
      }
    }
  }

  async exportBPMN() {
    if (this._modeler) {
      try {
        const result = await this._modeler.saveXML({format: true});
        const {xml} = result;
        const {topologyName} = this.props;
        download(xml, (topologyName ? topologyName : 'export') + '.tplg', 'application/bpmn+xml');
      } catch (err) {
        this.props.onError(String(err));
      }
    }
  }

  async exportSVG() {
    if (this._modeler) {
      try {
        const result = await this._modeler.saveSVG(options);
        const {svg} = result;
        const {topologyName} = this.props;
        download(svg, (topologyName ? topologyName : 'export') + '.svg', 'image/svg+xml');
      } catch (err) {
        this.props.onError(String(err));
      }
    }
  }

  async saveBPMN() {
    if (this._modeler) {
      try {
        const result = await this._modeler.saveXML({format: true});
        const {xml} = result;
        if (this.props.onSave) {
          this.props.onSave(xml);
          this._changed = false;
          this._sendActions();
          this._saved = true;
        }
      } catch (err) {
        this.props.onError(String(err));
      }
    }
  }

  importBPMN() {
    if (this._fileInputElement) {
      this._fileInputElement.click();
    }
  }

  async openBPMN(data) {
    if (this._modeler) {
      try {
        await this._modeler.importXML(data.content);
        this.props.onImport(`File [${data.file.name}] imported.`);
      } catch (err) {
        this.props.onError(String(err));
      }
    }
  }

  setSelf(self) {
    this._self = self;
  }

  render() {
    const {topologyId, topologyName, userId, metricsRange, showEditorPropPanel} = this.props;
    const {selectedId} = this.state;
    return (
      <div ref={self => {
        this.setSelf(self)
      }} className="bpmn-io-component">
        <div ref={this.setCanvasElement} className="bpmn-io-canvas"/>
        <div className={'node-box' + (showEditorPropPanel ? '' : ' hidden')}>
          <a href="#" className="close-link" onClick={this.propPanelToggle}><i className="fa fa-times"/></a>
          <div ref={this.setPropertiesElement} className="bpmn-io-properties"/>
          {topologyId && selectedId &&
          <SidebarStartingPointUrl topologyId={topologyId} topologyName={topologyName} schemaId={selectedId}
                                   user={userId}/>}
          {topologyId && selectedId &&
          <SidebarNodeMetrics topologyId={topologyId} schemaId={selectedId} metricsRange={metricsRange}/>}
          {topologyId && !selectedId && <SidebarTopologyMetrics topologyId={topologyId} metricsRange={metricsRange}/>}
        </div>
        <input ref={this.setFileInput} className="open-file-dialog" type="file"/>
      </div>
    );
  }
}

BpmnIoComponent.propTypes = {
  onSave: PropTypes.func,
  onChange: PropTypes.func,
  schema: PropTypes.string,
  topologyName: PropTypes.string,
  onError: PropTypes.func.isRequired,
  onImport: PropTypes.func.isRequired,
  setActions: PropTypes.func.isRequired,
  setPanelActions: PropTypes.func.isRequired,
  saveProcessId: PropTypes.string,
  topologyId: PropTypes.string,
  metricsRange: PropTypes.object,
  onPropPanelToggle: PropTypes.func.isRequired,
  showEditorPropPanel: PropTypes.bool.isRequired,
  nodesMetrics: PropTypes.object,
  userId: PropTypes.string
};

function mapStateToProps(state, ownProps) {
  const {node, metrics, auth: {user: {id}}} = state;

  let nodesMetrics = {};

  const topologyKey = ownProps.metricsRange ? `${ownProps.topologyId}[${ownProps.metricsRange.since}-${ownProps.metricsRange.till}]` : ownProps.topologyId;
  const topologyMetricsElement = metrics.topologies[topologyKey];

  if (topologyMetricsElement && topologyMetricsElement.state === 'success') {
    topologyMetricsElement.items.forEach((nodeId) => {
      const nodeKey = ownProps.metricsRange ? `${nodeId}[${ownProps.metricsRange.since}-${ownProps.metricsRange.till}]` : nodeId;
      const nodeSchemaId = Object.values(node.elements).filter(node => node.topology_id === ownProps.topologyId && node._id === nodeId);

      if (nodeSchemaId.length > 0) {
        nodesMetrics[nodeSchemaId[0].schema_id] = metrics.elements[nodeKey];
      }
    });
  }

  return {
    metrics: nodesMetrics,
    node: node,
    userId: id,
  }
}

function mapActionsToProps(dispatch, ownProps) {
  const needNodeList = forced => dispatch(nodeActions.needNodesForTopology(ownProps.topologyId, forced));
  const needMetricsList = forced => dispatch(metricsActions.needTopologyMetrics(ownProps.topologyId, ownProps.metricsRange, forced));
  return {
    onChange: id => dispatch(topologyActions.onChange(id)),
    needNodeList,
    needMetricsList,
    notLoadedCallback: () => {
      needNodeList(false);
      needMetricsList(false);
    }
  }
}

const BpmnIoComponentConnected = connect(mapStateToProps, mapActionsToProps)(BpmnIoComponent);

BpmnIoComponentConnected.propTypes = {
  topologyId: PropTypes.string.isRequired,
  metricsRange: PropTypes.object
};

export default BpmnIoComponentConnected;