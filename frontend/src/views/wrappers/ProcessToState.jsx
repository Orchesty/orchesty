import React from 'react'
import {connect} from 'react-redux';

export default WrappedComponent => {

  const InnerComponent = props => {
    const {processId, dispatch, ...passProps} = props;
    return <WrappedComponent {...passProps} />;
  };

  function mapStateToProps(state, ownProps) {
    const {process} = state;
    if (ownProps.processId){
      return {
        state: process[ownProps.processId]
      }
    } else {
      return {};
    }
  }

  const ProcessToState = connect(mapStateToProps)(InnerComponent);
  ProcessToState.displayName = `ProcessToState(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;

  return ProcessToState;
}