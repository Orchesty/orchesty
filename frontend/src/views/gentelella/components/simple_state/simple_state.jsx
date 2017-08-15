import React from 'react'
import Flusanec from 'flusanec';

import DATA_STATE from 'flusanec/src/core/data_state';
import ErrorState from './error_state';
import LoadingState from './loading_state';
import NotLoadedState from './not_loaded_state';

class SimpleState extends Flusanec.Component {
  render() {
    switch(this.props.state){
      case DATA_STATE.SUCCESS:
        return this.props.children;
      case DATA_STATE.LOADING:
        return <LoadingState />;
      case DATA_STATE.ERROR:
        return <ErrorState />;
    }
    return <NotLoadedState />;
  }
}

export default SimpleState;