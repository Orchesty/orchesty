import React from 'react'
import PropTypes from 'prop-types';
import StateComponent from 'rootApp/views/wrappers/StateComponent';

import './MetricsTable.less';

class MetricsTable extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {metrics: {data}} = this.props;
    return (
      <div className="metrics-table">
        <table className="table table-hover">
          <thead>
            <tr>
              <th>Queue depth</th>
              <th>Waiting time</th>
              <th>Process time</th>
              <th>CPU time</th>
              <th>Error</th>
              <th>Request time</th>
            </tr>
          </thead>
          <tbody>
              <tr>
                <td>Max: {data.queue_depth.max}</td>
                <td>Max: {data.waiting_time.max}</td>
                <td>Max: {data.process_time.max}</td>
                <td>Max: {data.cpu_time.max}</td>
                <td>{data.error.total}</td>
                <td>Max: {data.request_time.max}</td>
              </tr>
              <tr>
                <td>Min: {data.queue_depth.min}</td>
                <td>Min: {data.waiting_time.min}</td>
                <td>Min: {data.process_time.min}</td>
                <td>Min: {data.cpu_time.min}</td>
                <td></td>
                <td>Min: {data.request_time.min}</td>
              </tr>
              <tr>
                <td></td>
                <td>Avg: {data.waiting_time.avg}</td>
                <td>Avg: {data.process_time.avg}</td>
                <td>Avg: {data.cpu_time.avg}</td>
                <td></td>
                <td>Avg: {data.request_time.avg}</td>
              </tr>
          </tbody>
        </table>
      </div>
    );
  }
}

MetricsTable.propTypes = {
  metrics: PropTypes.object
};

export default StateComponent(MetricsTable, props => props.metrics && props.metrics.state);