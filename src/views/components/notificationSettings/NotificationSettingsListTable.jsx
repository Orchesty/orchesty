import React from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux';
import AbstractTable from '../AbstractTable';
import StateComponent from '../../../views/wrappers/StateComponent';
import './NotificationSettingsListTable.less';

class NotificationSettingsListTable extends AbstractTable {

  componentWillMount() {
    document.title = 'Notification Settings | Pipes Manager';
  }

  _renderHead() {
    const { list, elements, changeNotificationSettings } = this.props;

    const head = list && list.items ? list.items.map(id => {
      const item = elements[id];

      return (
        <th
          key={item.customName}
          className="notification-settings-head"
          onClick={() => changeNotificationSettings(item.id, item)}>
          {item.customName}
        </th>
      );
    }) : [];

    head.unshift(<th key="event">Event / Handler</th>);

    return <tr>{head}</tr>;
  }

  _renderRows() {
    const { list, elements, notificationSettingsEvents } = this.props;

    return notificationSettingsEvents && notificationSettingsEvents.map(({ key, value }) => {
      return (
        <tr key={key}>
          <td><strong>{value}</strong></td>
          {list && list.items ? list.items.map(id => {
            const hasEvent = elements[id].events.includes(key);

            return (
              <td key={id}>
                <i className={hasEvent ? 'fa fa-check' : 'fa fa-times'} style={{ color: hasEvent ? "#080" : "#F00" }} />
              </td>
            );
          }) : null}
        </tr>
      );
    });
  }

  render() {
    let rows = this._renderRows();

    if (!rows) {
      rows = <tr>
        <td colSpan={3}>No items</td>
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

NotificationSettingsListTable.propTypes = Object.assign({}, AbstractTable.propTypes, {
  elements: PropTypes.object.isRequired,
  notificationSettings: PropTypes.arrayOf(PropTypes.shape({})).isRequired,
  notificationSettingsEvents: PropTypes.arrayOf(PropTypes.shape({})).isRequired,
  changeNotificationSettings: PropTypes.func.isRequired,
  initialize: PropTypes.func.isRequired,
});

const mapStateToProps = ({ notificationSettings: { elements, initialize, events } }) => ({
  notificationSettings: Object.values(elements),
  notificationSettingsEvents: Object.entries(events).map(([key, value]) => ({ key, value })),
  initialized: initialize,
});

export default connect(mapStateToProps)(StateComponent(NotificationSettingsListTable));
