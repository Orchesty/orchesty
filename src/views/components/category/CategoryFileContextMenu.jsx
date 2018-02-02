import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import ContextMenu from 'elements/menu/ContextMenu';
import {menuItemType} from 'rootApp/types';
import * as applicationActions from 'actions/applicationActions';
import * as categoryActions from 'actions/categoryActions';

function mapActionsToProps(dispatch, ownProps) {
  const {categoryId} = ownProps;
  return {
    items: [
      {
        type: menuItemType.ACTION,
        caption: 'New topology',
        action: () => dispatch(applicationActions.openModal('topology_edit', {addNew: true, categoryId}))
      },
      {
        type: menuItemType.ACTION,
        caption: 'New category',
        action: () => dispatch(applicationActions.openModal('category_edit', {addNew: true, parentId: categoryId}))
      },
      {
        type: menuItemType.ACTION,
        caption: 'Rename category',
        action: () => dispatch(applicationActions.openModal('category_edit', {categoryId: categoryId}))
      },
      {
        type: menuItemType.ACTION,
        caption: 'Delete category',
        action: () => dispatch(categoryActions.deleteCategory(categoryId)),
      }
    ]
  }
}

export default connect(null, mapActionsToProps)(ContextMenu);