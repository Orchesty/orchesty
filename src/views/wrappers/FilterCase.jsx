import React from 'react'
import PropTypes from 'prop-types';

export default (WrappedComponent, parameters) => {

  const {type, ...passParams} = parameters;

  class FilterCase extends React.Component {
    constructor(props) {
      super(props);
      this.change = this.change.bind(this);
    }

    change(e){
      const {onChange, name} = this.props;
      e.preventDefault();
      onChange(name, {type, value: e.target.value});
    }

    render() {
      const {name, onChange, value, ...passProps} = this.props;
      const pass = Object.assign({}, parameters, passProps);
      const newValue = value ? value.value : undefined;
      return <WrappedComponent value={newValue} onChange={this.change} {...pass}/>
    }
  }

  FilterCase.displayName = `FilterCase(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;

  FilterCase.propTypes = {
    name: PropTypes.string.isRequired,
    onChange: PropTypes.func.isRequired
  };

  return FilterCase;
}