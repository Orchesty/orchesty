import React from 'react'
import PropTypes from 'prop-types'
import Moment from 'react-moment';
import JSONTree from 'react-json-tree'
import { connect } from 'react-redux';
import AbstractTable from '../AbstractTable';
import SortTh from 'elements/table/SortTh';
import ActionButtonPanel from 'elements/actions/ActionButtonPanel';
import ListPagination from '../../../views/elements/table/ListPagination';
import StateComponent from '../../../views/wrappers/StateComponent';
import './HumanTasksListTable.less'

class HumanTasksListTable extends AbstractTable {
  constructor(props) {
    super(props);

    this.onTopologyChange = this.onTopologyChange.bind(this);
    this.onNodeChange = this.onNodeChange.bind(this);
    this.onLogsChange = this.onLogsChange.bind(this);
    this.onLogsPress = this.onLogsPress.bind(this);
    this.onApply = this.onApply.bind(this);
  }

  componentWillMount() {
    document.title = 'Human Tasks | Pipes Manager';
  }

  componentDidMount() {
    let params = {};
    const { topologies, initialize, initialized } = this.props;

    location.search.substr(1).split("&").forEach(function (item) {
      const parts = item.split("=");
      params[parts[0]] = parts[1];
    });

    if (params.topology) {
      const { listChangeFilter, list: { filter } } = this.props;

      if (params.node) {
        listChangeFilter(Object.assign({}, filter, {
          topologyId: params.topology,
          nodeId: params.node,
          apply: true,
        }));
      } else {
        listChangeFilter(Object.assign({}, filter, {
          topologyId: params.topology,
          apply: true,
        }));
      }

      initialize();
      window.history.replaceState('', '', '/ui/human_tasks');
    } else {
      if (topologies.length > 0 && !initialized) {
        this.onTopologyChange({ target: { value: topologies[0]._id } });
        initialize();
      }
    }
  }

  onTopologyChange({ target: { value } }) {
    const { listChangeFilter, list: { filter } } = this.props;

    if (filter && filter.nodeId) {
      delete filter.nodeId;
    }

    listChangeFilter(Object.assign({}, filter, { topologyId: value, apply: true }));
  }

  onNodeChange({ target: { value } }) {
    const { listChangeFilter, list: { filter } } = this.props;
    const data = Object.assign({}, filter, { nodeId: value, apply: true });

    if (value === 'all') {
      delete data.nodeId;
    }

    listChangeFilter(data);
  }

  onLogsChange({ target: { value } }) {
    const { listChangeFilter, list: { filter } } = this.props;

    listChangeFilter(Object.assign({}, filter, { auditLogs: value, apply: false }));
  }

  onLogsPress(event) {
    if (event.charCode === 13) {
      event.preventDefault();
      event.stopPropagation();
      this.onApply();
    }
  }

  onApply() {
    const { listChangeFilter, list: { filter } } = this.props;

    listChangeFilter(Object.assign({}, filter, { apply: true }));
  }

  _renderHead() {
    const { list: { sort }, listChangeSort } = this.props;
    return (
      <tr>
        <SortTh name="nodeName" state={sort} onChangeSort={listChangeSort}>Node</SortTh>
        <SortTh name="created" state={sort} onChangeSort={listChangeSort}>Created</SortTh>
        <th>Data</th>
        <th>Logs</th>
        <th>Actions</th>
      </tr>
    );
  }

  _renderRows() {
    const { list, elements, process, approveHumanTask, changeHumanTask } = this.props;

    return list && list.items ? list.items.map(id => {
      const item = elements[id];
      const menuItems = [{
        caption: 'Approve',
        customClass: 'btn btn-success',
        action: () => approveHumanTask(item.topologyId, item.nodeId, item.processId, true)
      }, {
        caption: 'Change',
        customClass: 'btn btn-warning',
        action: () => changeHumanTask(item.id, item.data),
      }, {
        caption: 'Decline',
        customClass: 'btn btn-danger',
        action: () => process(item.topologyId, item.nodeId, item.processId, false)
      }];

      return (
        <tr key={item.name}>
          <td className="col-md-2">{item.nodeName}</td>
          <td className="col-md-2"><Moment format="DD. MM. YYYY HH:mm:ss">{item.created}</Moment></td>
          <td className="json">
            <JSONTree
              data={JSON.parse(item.data || '{}')}
              shouldExpandNode={() => false}
            />
          </td>
          <td className="json">
            <JSONTree
              data={JSON.parse(item.auditLogs || '{}')}
              shouldExpandNode={() => false}
            />
          </td>
          <td className="col-md-2">
            <ActionButtonPanel
              items={menuItems}
              right={true}
              buttonClassName={({ customClass }) => customClass || 'btn btn-primary'}
            />
          </td>
        </tr>
      )
    }) : null;
  }

  render() {
    const { list, list: { filter: { nodeId, topologyId, auditLogs } = {} }, listChangePage, topologies, nodes } = this.props;
    let rows = this._renderRows();

    if (!rows) {
      rows = <tr>
        <td colSpan={4}>No items</td>
      </tr>;
    }

    return (
      <div className={event => this.getClassName(event)}>
        <form className="filter form-horizontal">
          <div className="form-group col-lg-3 col-md-3 col-sm-3 col-xs-12">
            <label className="control-label">Topology</label>
            <div className="input-prepend input-group">
              <span className="add-on input-group-addon"><i className="fa fa-eye"></i></span>
              <select onLoad={this.onTopologyChange}
                      onChange={this.onTopologyChange}
                      value={topologyId}
                      className="form-control"
                      placeholder="Topology"
              >
                {topologies.map(({ _id, name, version }) => <option key={_id} value={_id}>{name}.v{version}</option>)}
              </select>
            </div>
          </div>
          <div className="form-group col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <label className="control-label">Node</label>
            <div className="input-prepend input-group">
              <span className="add-on input-group-addon"><i className="fa fa-eye"></i></span>
              <select onChange={this.onNodeChange} value={nodeId} className="form-control" placeholder="Topology">
                <option key="all" value="all">All nodes</option>
                {nodes.map(({ _id, customName }) => <option key={_id} value={_id}>{customName}</option>)}
              </select>
            </div>
          </div>
          <div className="form-group col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <label className="control-label">Search</label>
            <div className="input-prepend input-group">
              <span className="add-on input-group-addon"><i className="fa fa-eye"></i></span>
              <input onChange={this.onLogsChange}
                     onKeyPress={this.onLogsPress}
                     value={auditLogs}
                     className="form-control"
                     placeholder="Search"
              />
            </div>
          </div>
          <button
            type="button"
            className="btn btn-primary"
            onClick={this.onApply}
            style={{ marginTop: '27px' }}>
            <i className="fa fa-check" /> Apply
          </button>
        </form>
        <div className="table-wrapper">
          <table className="table table-hover">
            <thead>{this._renderHead()}</thead>
            <tbody>{rows}</tbody>
          </table>
        </div>
        <ListPagination list={list} onPageChange={listChangePage} />
      </div>
    );
  }
}

HumanTasksListTable.propTypes = Object.assign({}, AbstractTable.propTypes, {
  elements: PropTypes.object.isRequired,
  topologies: PropTypes.arrayOf(PropTypes.shape({})).isRequired,
  nodes: PropTypes.arrayOf(PropTypes.shape({})).isRequired,
  process: PropTypes.func.isRequired,
  approveHumanTask: PropTypes.func.isRequired,
  changeHumanTask: PropTypes.func.isRequired,
  initialize: PropTypes.func.isRequired,
});

const mapStateToProps = ({ topology: { elements } = {}, humanTask: { nodes, initialize } }) => ({
  topologies: Object.values(elements),
  nodes: Object.values(nodes),
  initialized: initialize,
});

export default connect(mapStateToProps)(StateComponent(HumanTasksListTable));
