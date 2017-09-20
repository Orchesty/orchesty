import React from 'react'
import PropTypes from 'prop-types';

import AbstractTable from '../AbstractTable';

import StateComponent from 'wrappers/StateComponent';
import SortTh from 'elements/table/SortTh';
import BoolValue from 'elements/BoolValue';
import ActionButtonPanel from 'elements/actions/ActionButtonPanel';

class AuthorizationListTable extends AbstractTable {
  constructor(props) {
    super(props);
  }

  _renderHead(){
    const {list: {sort}, listChangeSort} = this.props;
    return (
      <tr>
        <SortTh name="name" state={sort} onChangeSort={listChangeSort}>Name</SortTh>
        <SortTh name="description" state={sort} onChangeSort={listChangeSort}>Description</SortTh>
        <SortTh name="type" state={sort} onChangeSort={listChangeSort}>Type</SortTh>
        <SortTh name="is_authorized" state={sort} onChangeSort={listChangeSort}>Authorized</SortTh>
        <th>Actions</th>
      </tr>
    );
  }

  _renderRows() {
    const {list, elements, editSettings, authorize, getAuthorizeProcessState} = this.props;
    return list && list.items ? list.items.map(id => {
      const item = elements[id];
      const menuItems = [];
      if (editSettings) {
        menuItems.push({
          caption: 'Edit settings',
          action: () => {editSettings(item.name)}
        });
      }
      if (authorize && item.type != 'base'){
        menuItems.push({
          caption: 'Authorize',
          disabled: !item.can_authorize,
          state: getAuthorizeProcessState(item.name),
          action: () => {authorize(item.name)}
        });
      }
      return (
        <tr key={item.name}>
          <td>{item.name}</td>
          <td>{item.description}</td>
          <td>{item.type}</td>
          <td><BoolValue value={item.is_authorized}/></td>
          <td><ActionButtonPanel items={menuItems} right={true}/></td>
        </tr>
      )
    }) : null;
  }
}

AuthorizationListTable.propTypes = Object.assign({}, AbstractTable.propTypes, {
  elements: PropTypes.object.isRequired,
  editSettings: PropTypes.func,
  authorize: PropTypes.func,
  getAuthorizeProcessState: PropTypes.func
});

export default StateComponent(AuthorizationListTable);