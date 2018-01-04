import React from 'react'
import PropTypes from 'prop-types';

import './searchItems.less';

export const topologyGroup = ({item, globalState: {topology, topologyGroup, category}}) => {
  const topologyItem = topology.elements[topologyGroup.elements[item.id].last];
  const categoryItem = category.elements[topologyItem.category];
  return (
    <div className="search-item-topology-group">
      <div className="icon">
        <i className="fa fa-2x fa-connectdevelop" />
      </div>
      <div className="info">
        <div className="name">{topologyItem.name}</div>
        <div className="category">{categoryItem ? categoryItem.name : ''}</div>
      </div>
    </div>
  )
};