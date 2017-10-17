import React from 'react';

function TextInput({label, input, readOnly, meta: {touched, error} = {}, meta, ...passProps}){
  return (
        <input
          type="text"
          className={'form-control' + (touched && error ? ' parsley-error' : '')}
          placeholder={label}
          readOnly={readOnly}
          {...input}
          {...passProps}
        />
  );
}

export default TextInput;
