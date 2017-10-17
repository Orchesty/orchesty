import React from 'react'
import PropTypes from 'prop-types';

export default (WrappedComponent, parameters = {}) => {

  const {type, property, valueProcess, ...passParams} = parameters;

  class FilterCase extends React.Component {
    constructor(props) {
      super(props);
      this.change = this.change.bind(this);
    }

    change(e){
      const {onChange, valueProcess = parameters.valueProcess, name} = this.props;
      let value = valueProcess ? valueProcess(e.target.value) : e.target.value;
      e.preventDefault();
      onChange(name, {type, value, property: this.props.property || property});
    }

    render() {
      const {name, onChange, filterItem, valueProcess, ...passProps} = this.props;
      let pass = Object.assign({}, passParams, passProps);
      pass.subProps = Object.assign({filterItem, onChange, type, name, property: this.props.property || property}, pass.subProps);
      const newValue = filterItem ? filterItem.value : '';
      return <WrappedComponent value={newValue} onChange={this.change} {...pass}/>
    }
  }

  FilterCase.displayName = `FilterCase(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;

  FilterCase.propTypes = {
    name: PropTypes.string.isRequired,
    onChange: PropTypes.func.isRequired,
    filterItem: PropTypes.object,
    valueProcess: PropTypes.func
  };

  return FilterCase;
}