import React from 'react';

export default ({label, input, readOnly, meta: {touched, error}}) => {
  return (
        <input type="text" className={'form-control' + (touched && error ? ' parsley-error' : '')} placeholder={label} {...input} readOnly={readOnly}/>
  );
}
