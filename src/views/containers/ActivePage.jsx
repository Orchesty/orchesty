import React from 'react'
import { connect } from 'react-redux';

import * as pages from 'pages/pages';
import Error404Page from 'pages/Error404Page';

const ActivePage = ({page, pageId}) => page && page.key ? React.createElement(pages[page.key], {
  componentKey: page.key,
  pageId: pageId,
  ...page.args
}) : <Error404Page />;


ActivePage.displayName = 'ActivePage';

function mapStateToProps(state){
  const {application} = state;
  return {
    page: application.pages[application.selectedPage],
    pageId: application.selectedPage
  }
}

export default connect(mapStateToProps)(ActivePage);