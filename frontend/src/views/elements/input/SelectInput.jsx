import React from 'react'

class SelectInput extends React.Component {
  render() {
    const {label, input, readOnly, options, meta: {touched, error} = {}, meta, showErrors, ...passProps} = this.props;
    const optionsArray = options.map(item => <option key={item.value} value={item.value}>{item.label}</option>);
    return (
      <div>
        <select className={'form-control' + (touched && error ? ' parsley-error' : '')} placeholder={label} {...input} {...passProps} readOnly={readOnly}>
          {optionsArray}
        </select>
        {showErrors && touched && error && <ul className="parsley-errors-list filled"><li>{error}</li></ul>}
      </div>
    );
  }
}

export default SelectInput;
