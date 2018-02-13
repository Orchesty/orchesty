import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import TabBar from 'elements/tab/TabBar';
import * as applicationActions from 'rootApp/actions/applicationActions';
import config from 'rootApp/config/index';

function pageTitleSelector(state, pageId){
  const page = state.application.pages[pageId];
  const pageDef = config.pages[page.key];
  switch (page.key){
    case 'topology_detail':
      const topology = state.topology.elements[page.args.topologyId];
      if (topology){
        return `${topology.name}.v${topology.version}`;
      } else {
        return pageDef.caption;
      }
    default:
      return pageDef.caption;
  }
}

function mapStateToProps(state){
  const {application} = state;
  let activeIndex = null;
  const items = Object.keys(application.pages)
    .filter(id => application.pages[id].key && config.pages[application.pages[id].key].tab)
    .map((id, index) => {
      if (id == application.selectedPage){
        activeIndex = index;
      }
      return {id:id, caption: pageTitleSelector(state, id)}
    });
  return {
    items,
    active: activeIndex
  };
}

function mapActionsToProps(dispatch){
  return {
    onClose: (pageTab, newPageTab) => dispatch(applicationActions.closePage(pageTab.id, newPageTab ? newPageTab.id : null)),
    onChangeTab: pageTab => dispatch(applicationActions.selectPage(pageTab.id))
  };
}

export default connect(mapStateToProps, mapActionsToProps)(TabBar);