import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as applicationActions from 'actions/applicationActions';

import Page from 'wrappers/Page';
import TopologySchema from 'components/topology/TopologySchema';

function mapActionsToProps(dispatch, ownProps){
  return {
    onChangeTopology: id => dispatch(applicationActions.changePageArgs(Object.assign({}, ownProps, {schemaId: id})))
  }
}

const SchemaPage = connect(null, mapActionsToProps)(Page(TopologySchema, 'Topology schema'));

SchemaPage.propTypes = {
  schemaId: PropTypes.string
};

export default SchemaPage;