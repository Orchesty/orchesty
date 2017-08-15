import React from 'react'
import Flusanec from 'flusanec';

class ErrorState extends Flusanec.Component {
  _initialize() {}

  _useProps(props) {}

  render() {
    return (
      <div className="error-source"><span>Error{this.props.msg && ': '}{this.props.msg}</span></div>
    );
  }
}

export default ErrorState;