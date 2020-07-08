import React from 'react';
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import AbstractTable from '../AbstractTable';
import StateComponent from '../../../views/wrappers/StateComponent';
import * as applicationActions from '../../../actions/applicationActions';
import './AppStoreListTable.less';

class AppStoreListTable extends AbstractTable {

  componentDidMount() {
    document.title = 'Application Store | Pipes Manager';
  }

  _renderHead() {
    return (
      <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Webhooks</th>
        <th>Authorization Type</th>
        <th>Status</th>
      </tr>
    );
  }

  _renderRows() {
    const {list, elements, openApplication} = this.props;

    return list && list.items ? list.items.map(id => {
      const {key, realName, description, applicationType, authorizationType, authorized} = elements[id];

      return (
        <tr key={key} className={authorized !== undefined ? (authorized ? 'text-success' : 'text-danger') : null}
            onClick={() => openApplication(key)}>
          <td className="col-md-3">{realName}</td>
          <td className="col-md-3">{description}</td>
          <td className="col-md-2">{this._renderWebhookMark(applicationType)}</td>
          <td
            className="col-md-2">{authorizationType.substr(0, 1).toUpperCase() + authorizationType.substr(1).toLowerCase()}
          </td>
          <td className="col-md-2">{authorized !== undefined ? (authorized ? 'Authorized' : 'Installed') : 'Ready'}</td>
        </tr>
      )
    }) : null;
  }

  _renderWebhookMark(applicationType) {
    if (applicationType === 'webhook') {
      return <i className="fa fa-check" aria-hidden="true"/>
    }

    return <i className="fa fa-minus" aria-hidden="true"/>
  }

  render() {
    let rows = this._renderRows();

    if (!rows) {
      rows = <tr>
        <td colSpan={4}>No items</td>
      </tr>;
    }

    return (
      <div className={this.getClassName()}>
        <div className="table-wrapper">
          <table className="table table-hover app-store">
            <thead>{this._renderHead()}</thead>
            <tbody>{rows}</tbody>
          </table>
        </div>
      </div>
    );
  }
}

AppStoreListTable.propTypes = Object.assign({}, AbstractTable.propTypes, {
  elements: PropTypes.object.isRequired,
  appStore: PropTypes.arrayOf(PropTypes.shape({})).isRequired,
  initialize: PropTypes.func.isRequired,
  openApplication: PropTypes.func.isRequired,
});

const mapStateToProps = ({appStore: {elements, initialize}, auth: {user: {id}}}) => ({
  appStore: Object.values(elements),
  initialized: initialize,
  id
});

const mapDispatchToProps = dispatch => ({
  openApplication: application => dispatch(applicationActions.openPage('app_store_detail', {application})),
});

export default connect(mapStateToProps, mapDispatchToProps)(StateComponent(AppStoreListTable));
