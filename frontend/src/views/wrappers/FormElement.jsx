import React from 'react'

export default (WrappedComponent) => {

  class FormElement extends React.Component {
    constructor(props) {
      super(props);
    }

    render() {
      const {...passProps} = this.props;
      const {label, meta: {error, touched}} = this.props;
      return (
        <div className="form-group">
          <label className="control-label col-md-3 col-sm-3 col-xs-12">{label}</label>
          <div className="col-md-9 col-sm-9 col-xs-12">
            <WrappedComponent {...passProps} />
            {
              touched && error &&
              <ul className="parsley-errors-list filled">
                <li>{error}</li>
              </ul>
            }
          </div>
        </div>
      );
    }
  }
  
  return FormElement;
}

