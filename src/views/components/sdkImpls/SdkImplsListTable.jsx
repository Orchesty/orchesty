import React from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux';
import AbstractTable from '../AbstractTable';
import StateComponent from '../../../views/wrappers/StateComponent';
import ActionButtonPanel from '../../elements/actions/ActionButtonPanel';

class SdkImplsListTable extends AbstractTable {

  componentWillMount() {
    document.title = 'SDK Implementations | Pipes Manager';
  }

  _renderHead() {
    const { onChange } = this.props;

    return (
      <tr>
        <th className="col-md-4">Key</th>
        <th className="col-md-4">Value</th>
        <th className="col-md-4">
          <button
            className="btn btn-primary"
            onClick={() => onChange()}
          >
            Create
          </button>
        </th>
      </tr>
    );
  }

  _renderRows() {
    const { list, elements, onChange, onRemove } = this.props;

    return list && list.items ? list.items.map(id => {
      const { id: _id, key, value } = elements[id];

      const menuItems = [{
        caption: 'Change',
        customClass: 'btn btn-success',
        action: () => onChange({ _id, key, value }),
      }, {
        caption: 'Delete',
        customClass: 'btn btn-danger',
        action: () => onRemove(_id),
      }];

      return (
        <tr key={key}>
          <td className="col-md-4">{key}</td>
          <td className="col-md-4">{value}</td>
          <td className="col-md-4">
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
    let rows = this._renderRows();

    if (!rows) {
      rows = <tr>
        <td colSpan={4}>No items</td>
      </tr>;
    }

    return (
      <div className={event => this.getClassName(event)}>
        <div className="table-wrapper">
          <table className="table table-hover">
            <thead>{this._renderHead()}</thead>
            <tbody>{rows}</tbody>
          </table>
        </div>
      </div>
    );
  }
}

SdkImplsListTable.propTypes = Object.assign({}, AbstractTable.propTypes, {
  elements: PropTypes.object.isRequired,
  appStore: PropTypes.arrayOf(PropTypes.shape({})).isRequired,
  initialize: PropTypes.func.isRequired,
  onChange: PropTypes.func.isRequired,
  onRemove: PropTypes.func.isRequired,
});

const mapStateToProps = ({ appStore: { elements, initialize }, auth: { user: { id } } }) => ({
  appStore: Object.values(elements),
  initialized: initialize,
  id
});

export default connect(mapStateToProps)(StateComponent(SdkImplsListTable));
