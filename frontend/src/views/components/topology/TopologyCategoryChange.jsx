import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import CategoryTreeView from 'components/category/CategoryTreeView';
import StateComponent from 'rootApp/views/wrappers/StateComponent';
import * as topologyActions from 'rootApp/actions/topologyActions';
import {stateType} from 'rootApp/types';

class TopologyCategoryChange extends React.Component {
  constructor(props) {
    super(props);
  }

  componentDidMount() {
    this.props.setSubmit(this.submit.bind(this));
  }

  submit(){
    const {selectedId, commitAction} = this.props;
    commitAction(selectedId).then(
      response => {
        const {onSuccess} = this.props;
        if (response){
          if (onSuccess){
            onSuccess(this);
          }
        }
        return response;
      }
    )
  }

  render() {
    const {componentKey, topology} = this.props;
    return <CategoryTreeView componentKey={componentKey} initSelectedId={topology.category} />
  }
}

TopologyCategoryChange.propTypes = {
  setSubmit: PropTypes.func.isRequired,
  componentKey: PropTypes.string.isRequired,
  topology: PropTypes.object.isRequired,
  commitAction: PropTypes.func.isRequired,
  onSuccess: PropTypes.func
};

function mapStateToProps(state, ownProps){
  const {topology, category} = state;
  const newComponentKey = `${ownProps.componentKey}-${ownProps.topologyId}`;
  const tree = category.trees[newComponentKey];
  const selectedId = tree ? tree.selectedId : undefined;
  const topologyObj = topology.elements[ownProps.topologyId];
  return {
    state: topologyObj ? stateType.SUCCESS : stateType.NOT_LOADED,
    topology: topologyObj,
    componentKey: newComponentKey,
    selectedId: selectedId
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    notLoadedCallback: () => dispatch(topologyActions.needTopology(ownProps.topologyId)),
    commitAction: categoryId => dispatch(topologyActions.topologyUpdate(ownProps.topologyId, {category: categoryId}))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(TopologyCategoryChange));