import React from 'react';

function TextInput({label, input, readOnly, meta: {touched, error} = {}, meta, showErrors, ...passProps}){
  return (
    <div>
      <input
        type="text"
        className={'form-control' + (touched && error ? ' parsley-error' : '')}
        placeholder={label}
        readOnly={readOnly}
        {...input}
        {...passProps}
      />
      {showErrors && touched && error && <ul className="parsley-errors-list filled"><li>{error}</li></ul>}
    </div>
  );
}

export default TextInput;
