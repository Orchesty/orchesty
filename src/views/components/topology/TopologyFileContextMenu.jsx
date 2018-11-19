import React from 'react'
import {connect} from 'react-redux';

import ContextMenu from 'elements/menu/ContextMenu';
import {menuItemType} from 'rootApp/types';
import * as applicationActions from 'actions/applicationActions';

function mapActionsToProps(dispatch, ownProps) {
  const {topologyId} = ownProps;
  return {
    items: [
      {
        type: menuItemType.ACTION,
        caption: 'Change category',
        action: () => dispatch(applicationActions.openModal('category_topology_change', {addNew: true, topologyId}))
      }
    ]
  }
}

export default connect(null, mapActionsToProps)(ContextMenu);