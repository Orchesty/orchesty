import React from 'react';
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import * as editableActions from 'actions/editableActions';

import './Editable.less';

export default (EditComponent, View, parameters = {valueKey: 'value'}) => {
  class Editable extends React.Component {
    constructor(props) {
      super(props);
      this.viewDoubleClick = this.viewDoubleClick.bind(this);
      this.editChange = this.editChange.bind(this);
      this.editDone = this.editDone.bind(this);
    }

    componentDidMount(){
      const {setControlFunction, switchEdit, switchView} = this.props;
      if (setControlFunction){
        setControlFunction({
          switchEdit, switchView
        });
      }
    }

    viewDoubleClick(e){
      e.preventDefault();
      this.props.switchEdit();
    }

    editChange(e){
      this.props.changeValue(e.target.value);
    }

    editDone(success){
      const {switchView, commitAction, editableRec} = this.props;
      if (success && editableRec && editableRec.hasOwnProperty('value')){
        commitAction(editableRec.value);
      }
      switchView();
    }

    render() {
      const {editableRec, children, valueKey = parameters.valueKey, ...passProps} = this.props;
      if (!editableRec || !editableRec.editMode || !EditComponent) {
        if (View) {
          return <div onDoubleClick={this.viewDoubleClick}><View {...passProps}/></div>
        } else {
          return <span onDoubleClick={this.viewDoubleClick}>{children ? children : this.props[valueKey]}</span>;
        }
      } else {
        const editValue = editableRec && editableRec.hasOwnProperty('value') ? editableRec.value : this.props[valueKey];
        return (
          <div className="editable">
            <EditComponent
              onChange={this.editChange}
              onDone={this.editDone}
              value={editValue}
            />
          </div>);
      }
    }
  }

  Editable.propTypes = {
    editableRec: PropTypes.object,
    valueKey: PropTypes.string,
    switchEdit: PropTypes.func.isRequired,
    switchView: PropTypes.func.isRequired,
    changeValue: PropTypes.func.isRequired,
    commitAction: PropTypes.func.isRequired,
    setControlFunction: PropTypes.func
  };

  Editable.displayName = `Editable(${EditComponent.displayName || EditComponent.name || 'Component'})`;

  function mapStateToProps(state, ownProps) {
    const {editable} = state;
    return {
        editableRec: editable[ownProps.componentKey]
    }
  }

  function mapActionsToProps(dispatch, ownProps){
    return {
      switchEdit: () => dispatch(editableActions.switchEdit(ownProps.componentKey)),
      changeValue: value => dispatch(editableActions.change(ownProps.componentKey, value)),
      switchView: () => dispatch(editableActions.switchView(ownProps.componentKey)),
    }
  }

  return connect(mapStateToProps, mapActionsToProps)(Editable);
}