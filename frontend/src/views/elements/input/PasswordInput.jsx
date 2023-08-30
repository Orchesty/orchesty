import React from 'react';

export default function PasswordInput({label, input, readOnly, meta: {touched, error}, showErrors }){
  return (
    <div>
        <input type="password" className={'form-control' + (touched && error ? ' parsley-error' : '')} placeholder={label} {...input} readOnly={readOnly}/>
      {showErrors && touched && error && <ul className="parsley-errors-list filled"><li>{error}</li></ul>}
    </div>
  );
}
