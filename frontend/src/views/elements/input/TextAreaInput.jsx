import React from 'react';

function TextAreaInput({label, rows, input, readOnly, meta: {touched, error}}){
  return (
    <textarea
      className={'form-control' + (touched && error ? ' parsley-error' : '')}
      rows={rows}
      placeholder={label}
      readOnly={readOnly}
      style={{resize: 'vertical'}}
      {...input}
    />
  );
}

export default TextAreaInput;