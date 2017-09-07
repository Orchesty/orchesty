import React from 'react'
import { connect } from 'react-redux';

import Error404Page from '../pages/Error404Page';
import DashboardPage from '../pages/DashboardPage';
import TopologyPage from '../pages/TopologyPage';
import SchemaPage from '../pages/SchemaPage';

import './ActivePage.less';

class ActivePage extends React.Component {
  render() {
    const {page} = this.props;
    switch (page.key){
      case 'dashboard':
        return <DashboardPage pageKey={page.key} {...page.args}/>;
      case 'topology_list':
        return <TopologyPage pageKey={page.key} {...page.args}/>;
      case 'topology_schema':
        return <SchemaPage pageKey={page.key} {...page.args}/>;
      default:
        return <Error404Page />;
    }
  }
}

function mapStateToProps(state){
  const {application} = state;

  return {
    page: application.selectedPage
  }
}

export default connect(mapStateToProps)(ActivePage);