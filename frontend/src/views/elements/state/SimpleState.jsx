import React from 'react'

import {stateType} from 'rootApp/types';

import ErrorState from './ErrorState';
import LoadingState from './LoadingState';
import NotLoadedState from './NotLoadedState';

class SimpleState extends React.Component {
  render() {
    switch(this.props.state){
      case stateType.SUCCESS:
        return this.props.children;
      case stateType.LOADING:
        return <LoadingState />;
      case stateType.ERROR:
        return <ErrorState />;
      default:
        return <NotLoadedState />;
    }
  }
}

export default SimpleState;