import React from 'react'

import SelectServer from 'components/server/SelectServer';

import './NonAuthPage.less';
import Toaster from "containers/Toaster";

export default (WrappedComponent) => {
  
  class NonAuthPage extends React.Component {
    constructor(props) {
      super(props);
    }

    render() {
      return (
        <div className="login" style={{height: '100%'}}>
          <div className="login_wrapper">
            <div className="animate form login_form">
              <section className="login_content">
                <WrappedComponent {...this.props} />
                <SelectServer/>
              </section>
            </div>
          </div>
          <Toaster />
        </div>
      );
    }
  }

  NonAuthPage.displayName = `NonAuthPage(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;
  
  return NonAuthPage;

}