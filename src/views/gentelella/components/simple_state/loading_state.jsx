import React from 'react'
import Flusanec from 'flusanec';
import Loading from 'react-loading';

class LoadingState extends Flusanec.Component {
  _initialize() {
  }

  _useProps(props) {

  }

  render() {
    return (
      <div className="loading-source"><Loading type="spinningBubbles" color="#000000" height={24} width={24} delay={200}></Loading></div>
    );
  }
}

export default LoadingState;