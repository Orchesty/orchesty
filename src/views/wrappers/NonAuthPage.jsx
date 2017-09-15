import React from 'react'

import './NonAuthPage.less';

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
              </section>
            </div>
          </div>
        </div>
      );
    }
  }

  NonAuthPage.displayName = `NonAuthPage(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;
  
  return NonAuthPage;

}