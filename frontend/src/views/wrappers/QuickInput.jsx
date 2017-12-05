import React from 'react'
import PropTypes from 'prop-types';

export default (WrappedComponent) => {
  class QuickInput extends React.Component {
    constructor(props) {
      super(props);
      this.blur = this.blur.bind(this);
      this.keyDown = this.keyDown.bind(this);
    }

    done(success){
      const {onDone} = this.props;
      if (onDone){
        onDone(success);
      }
    }

    blur(e){
      if (this.props.onBlur){
        this.props.onBlur(e);
      }
      this.done(true);
    }

    keyDown(e){
      switch (e.keyCode){
        case 13:
          this.done(true);
          break;
        case 27:
          this.done(false);
          break;
      }
    }

    render() {
      const {onDone, onBlur, ...passProps} = this.props;
      return <WrappedComponent autoFocus onBlur={this.blur} onKeyDown={this.keyDown} {...passProps}/>
    }
  }

  QuickInput.propTypes = {
    onDone: PropTypes.func,
    onBlur: PropTypes.func
  };

  return QuickInput;
}