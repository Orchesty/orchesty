import React from 'react'
import { connect } from 'react-redux';

import Error404Page from 'pages/Error404Page';
import DashboardPage from 'pages/DashboardPage';
import TopologyListPage from 'pages/TopologyListPage';
import TopologyDetailPage from 'pages/TopologyDetailPage';
import SchemaPage from 'pages/SchemaPage';
import AuthorizationListPage from 'pages/AuthorizationListPage';
import TopologyCategoryListPage from 'pages/TopologyCategoryListPage';

class ActivePage extends React.Component {
  render() {
    const {page} = this.props;
    switch (page.key){
      case 'dashboard':
        return <DashboardPage pageKey={page.key} {...page.args}/>;
      case 'topology_list':
        return <TopologyCategoryListPage pageKey={page.key} {...page.args}/>;
      case 'topology_list_all':
        return <TopologyListPage pageKey={page.key} {...page.args}/>;
      case 'topology_detail':
        return <TopologyDetailPage pageKey={page.key} {...page.args}/>;
      case 'topology_schema':
        return <SchemaPage pageKey={page.key} {...page.args}/>;
      case 'authorization_list':
        return <AuthorizationListPage pageKey={page.key} {...page.args}/>;
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