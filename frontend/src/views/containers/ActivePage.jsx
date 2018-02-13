import React from 'react'
import { connect } from 'react-redux';

import * as pages from 'pages/pages';
import Error404Page from 'pages/Error404Page';
import * as applicationActions from 'actions/applicationActions';

const ActivePage = ({page, setPageArgs}) => page && page.key ? React.createElement(pages[page.key], {
  componentKey: page.key,
  setPageArgs: setPageArgs.bind(null, page),
  ...page.args
}) : <Error404Page />;


ActivePage.displayName = 'ActivePage';

function mapStateToProps(state){
  const {application} = state;

  return {
    page: application.pages[application.selectedPage]
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    setPageArgs: (page, newArgs) => dispatch(applicationActions.openPage(page.key, Object.assign({}, page.args, newArgs)))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(ActivePage);