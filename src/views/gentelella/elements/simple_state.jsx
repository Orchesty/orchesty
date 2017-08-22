import React from 'react'

import {stateType} from '../../../types';

import ErrorState from './error_state';
import LoadingState from './loading_state';
import NotLoadedState from './not_loaded_state';

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