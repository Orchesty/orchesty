import React from 'react';
import PropTypes from 'prop-types';

import ListPagination from 'elements/table/ListPagination';
import SortTh from 'elements/table/SortTh';
import StateComponent from 'wrappers/StateComponent';
import BoolValue from 'elements/BoolValue';
import ActionButtonPanel from 'rootApp/views/elements/actions/ActionButtonPanel';
import MetricsTable from 'rootApp/views/components/metrics/MetricsTable';

class NodeMetricsListTable extends React.Component {
  constructor(props) {
    super(props);
    this._checkList(props);
  }

  _checkList(props){
    if (!props.list){
      props.needList();
    }
  }

  componentWillReceiveProps(nextProps){
    this._checkList(nextProps);
  }

  _renderHead(){
    const {listChangeSort, withTopology, withNodeTest, list: {sort}} = this.props;
    return (
      <tr>
        {withTopology && <th>Topology</th>}
        <SortTh name="name" state={sort} onChangeSort={listChangeSort}>Name</SortTh>
        <th>Metrics</th>
      </tr>
    );
  }

  render() {
    const {list, elements, topologyElements, metricsElements, withTopology, onlyEvents, listChangePage} = this.props;
    const rows = list && list.items ? list.items.map(id => {
      const item = elements[id];
      if (!onlyEvents || item.handler == 'event') {
        return (
          <tr key={item._id}>
            {withTopology && <td>{item.topology_id}</td>}
            <td>{item.name}</td>
            <td><MetricsTable metrics={metricsElements[item._id]} /></td>
          </tr>
        )
      } else {
        return undefined;
      }
    }) : <tr>
      <td colSpan={2}>No items</td>
    </tr>;
    return (
      <div className="node-list-table">
        <table className="table table-hover">
          <thead>
            {this._renderHead()}
          </thead>
          <tbody>
            {rows}
          </tbody>
        </table>
        <ListPagination list={list} onPageChange={listChangePage} />
      </div>
    );
  }
}

NodeMetricsListTable.defaultProps = {
  withTopology: true,
  onlyEvents: false,
  withNodeTest: false
};

NodeMetricsListTable.propTypes = {
  list: PropTypes.object,
  elements: PropTypes.object.isRequired,
  topologyElements: PropTypes.object.isRequired,
  withTopology: PropTypes.bool.isRequired,
  onlyEvents: PropTypes.bool.isRequired,
  needList: PropTypes.func.isRequired,
  listChangeSort: PropTypes.func,
  listChangePage: PropTypes.func,
};

export default StateComponent(NodeMetricsListTable);