import React from 'react';

export default function NumberInput({label, input, readOnly, meta: {touched, error}, min, max, step}){
	return (
    <input
      type="number"
      className={'form-control' + (touched && error ? ' parsley-error' : '')}
      placeholder={label}
      {...input}
      readOnly={readOnly}
      min={min}
      max={max}
      step={step === undefined ? 'any' : step}
    />
  );
}
