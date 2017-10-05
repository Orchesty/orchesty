import React from 'react'

import {stateType} from 'rootApp/types';

import ErrorState from 'elements/state/ErrorState';
import LoadingState from 'elements/state/LoadingState';
import NotLoadedState from 'elements/state/NotLoadedState';

export default WrappedComponent => {

  class StateComponent extends React.Component {
    constructor(props) {
      super(props);
    }

    componentWillMount(){
      this._needData(this.props, true);
    }

    componentWillReceiveProps(nextProps){
      this._needData(nextProps, false);
    }

    _needData(props){
      const {state, notLoadedCallback} = props;
      if ((!state || state == stateType.NOT_LOADED) && notLoadedCallback) {
        notLoadedCallback();
      }
    }

    render() {
      const {state,  notLoadedCallback, ...passProps} = this.props;

      switch (state) {
        case stateType.SUCCESS:
          return <WrappedComponent {...passProps} />;
        case stateType.LOADING:
          return <LoadingState />;
        case stateType.ERROR:
          return <ErrorState />;
        default:
          return <NotLoadedState />;
      }
    };
  }

  StateComponent.displayName = `StateComponent(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;
  
  return StateComponent;
}
