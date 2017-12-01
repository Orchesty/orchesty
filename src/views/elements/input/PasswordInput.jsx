import React from 'react';

export default function PasswordInput({label, input, readOnly, meta: {touched, error}}){
  return (
        <input type="password" className={'form-control' + (touched && error ? ' parsley-error' : '')} placeholder={label} {...input} readOnly={readOnly}/>
  );
}
