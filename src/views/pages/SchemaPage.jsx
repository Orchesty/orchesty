import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as applicationActions from '../../actions/applicationActions';

import TopologySchema from '../components/topology/TopologySchema';
import ActionButton from '../elements/ActionButton';

class SchemaPage extends React.Component {
  constructor(props) {
    super(props);
    this.setActions = this.setAction.bind(this);
    this.state = {
      pageActions: null
    }
  }

  setAction(actions) {
    this.setState({
      pageActions: actions
    });
  }

  render() {
    const {pageActions} = this.state;
    const {schemaId, changeSchemaId} = this.props;
    return (
      <div className="right_col" role="main">
        <div className="main-page">
          <div className="page-title">
            <div className="title_left"><h3>Topology schema</h3></div>
            <div className="title_right">
              <div className="pull-right">

                {pageActions ? <ActionButton items={pageActions} size="md" right={true}/> : null}
              </div>
            </div>
          </div>
          <div className="clearfix"/>
          <TopologySchema
            schemaId={schemaId}
            actions={this.setActions}
            onChangeTopology={changeSchemaId}
          />
        </div>
      </div>
    );
  }
}

SchemaPage.propTypes = {
  schemaId: PropTypes.string,
  changeSchemaId: PropTypes.func.isRequired
};

function mapActionsToProps(dispatch, ownProps){
  return {
    changeSchemaId: id => dispatch(applicationActions.changePageArgs(Object.assign({}, ownProps, {schemaId: id})))
  }
}

export default connect(() => ({}), mapActionsToProps)(SchemaPage);