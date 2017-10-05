import React from 'react'

import ActionButtonPanel from 'elements/actions/ActionButtonPanel';

import './Page.less';

export default (WrappedComponent, PageHeader) => {
  
  class Page extends React.Component {
    constructor(props) {
      super(props);
      this.setActions = this.setActions.bind(this);
      this.state = {
        pageActions: null
      }
    }

    setActions(actions) {
      this.setState({
        pageActions: actions
      });
    }

    render() {
      const {pageActions} = this.state;
      const {...passProps} = this.props;

      let pageTitle = null;
      if (PageHeader || pageActions) {
        pageTitle = typeof PageHeader == 'function' ? <PageHeader actions={pageActions}/> : (
          <div className="page-title">
            <div className="title_left"><h3>{PageHeader}</h3></div>
            <div className="title_right">
              <div className="pull-right">
                <ActionButtonPanel items={pageActions} size="md" right={true}/>
              </div>
            </div>
          </div>
        );
      }

      return (
        <div className="right_col" role="main">
          <div className="main-page">
            {pageTitle}
            <div className="page-content">
              <WrappedComponent setActions={this.setActions} {...passProps} />
            </div>
          </div>
        </div>
      );
    }
  }

  Page.displayName = `Page(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;

  return Page;
}