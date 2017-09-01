import React from 'react'

import {stateType} from '../../types';

import ErrorState from './errorState';
import LoadingState from './loadingState';
import NotLoadedState from './notLoadedState';

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