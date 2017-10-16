import React from 'react'
import PropTypes from 'prop-types';

const sizes = {
  xs: 'col-lg-1 col-md-1 col-sm-2 col-xs-12',
  sm: 'col-lg-2 col-md-2 col-sm-3 col-xs-12',
  md: 'col-lg-4 col-md-6 col-sm-6 col-xs-12',
  lg: 'col-lg-6 col-md-12 col-sm-12 col-xs-12'
};

export default (WrappedComponent, SubComponent) => {

  class FilterElement extends React.Component {
    constructor(props) {
      super(props);
      this.onFocus = this.onFocus.bind(this);
      this.onBlur = this.onBlur.bind(this);
      this.setKeyPressed = this.setKeyPressed.bind(this);
      this.onKeyPress = this.onKeyPress.bind(this);
      this._keypressed = null;
      this.state = {
        focused: false
      };
      this._blurTimer = null;
    }

    componentWillUnmount(){
      if (this._blurTimer){
        clearTimeout(this._blurTimer);
        this._blurTimer = null;
      }
    }

    setKeyPressed(callback){
      this._keypressed = callback;
    }

    onKeyPress(e){
      if (this._keypressed){
        this._keypressed(e);
      }
    }

    onBlur() {
      this._blurTimer = setTimeout(() => {
        this._blurTimer = null;
        this.setState({ focused: false });
      }, 100);
    }

    onFocus() {
      this.setState({ focused: true });
    }

    render() {
      const {icon, size, subProps, ...passProps} = this.props;
      const {label} = this.props;
      return (
        <div className={'form-group ' + sizes[size]} onFocus={this.onFocus} onBlur={this.onBlur} onKeyDown={this.onKeyPress}>
          <label className="control-label">{label}</label>
          <div className="input-prepend input-group">
            <span className="add-on input-group-addon"><i className={icon} /></span>
            <WrappedComponent {...passProps} />
          </div>
          {SubComponent && <SubComponent setKeyPressed={this.setKeyPressed} focused={this.state.focused} {...subProps}/>}
        </div>
      );
    }
  }

	FilterElement.displayName = `FilterElement(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;

  FilterElement.defaultProps = {
    size: 'sm',
    icon: 'fa fa-question'
  };

  FilterElement.propTypes = {
    icon: PropTypes.string.isRequired,
    size: PropTypes.string.isRequired,
    filterItem: PropTypes.object
  };

  return FilterElement;
}

