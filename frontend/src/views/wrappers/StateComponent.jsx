import React from 'react';

import {stateType} from 'rootApp/types';

import ErrorState from 'elements/state/ErrorState';
import LoadingState from 'elements/state/LoadingState';
import NotLoadedState from 'elements/state/NotLoadedState';

export default (WrappedComponent, stateFunc) => {

  class StateComponent extends React.Component {
    constructor(props) {
      super(props);
    }

    UNSAFE_componentWillMount(){
      this._needData(this.props, true);
    }

    UNSAFE_componentWillReceiveProps(nextProps){
      this._needData(nextProps, false);
    }

    _needData(props, mount){
      const {state, notLoadedCallback} = props;
      const calcState = typeof stateFunc == 'function' ? stateFunc(this.props) : state;
      if ((!calcState || calcState === stateType.NOT_LOADED || (mount && calcState === stateType.ERROR)) && notLoadedCallback) {
        notLoadedCallback();
      }
    }

    render() {
      const {state,  notLoadedCallback, ...passProps} = this.props;

      const calcState = typeof stateFunc == 'function' ? stateFunc(this.props) : state;

      switch (calcState) {
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
