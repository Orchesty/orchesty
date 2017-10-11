import React from 'react'

class SelectInput extends React.Component {
  render() {
    const {label, input, readOnly, options, meta: {touched, error}} = this.props;
    const optionsArray = options.map(item => <option key={item.value} value={item.value}>{item.label}</option>);
    return (
      <select className={'form-control' + (touched && error ? ' parsley-error' : '')} placeholder={label} {...input} readOnly={readOnly}>
        {optionsArray}
      </select>
    );
  }
}

export default SelectInput;