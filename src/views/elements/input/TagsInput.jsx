import React from 'react';
import ReactTagsInput from 'react-tagsinput';

import './TagsInput.less';


class TagsInput extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {placeholder, label, input, readOnly, options, meta: {touched, error} = {}, meta, ...passProps} = this.props;
    return <ReactTagsInput
      className={'react-tagsinput' + (touched && error ? ' parsley-error' : '')}
      placeholder={label}
      inputProps={{placeholder: placeholder}}
      {...input}
      {...passProps}
      readOnly={readOnly}
    />;
  }
}

TagsInput.propTypes = {};

export default TagsInput;