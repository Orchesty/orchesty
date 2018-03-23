import React from 'react';
import moment from 'moment';
import DateRangePicker from 'react-bootstrap-daterangepicker';
import 'bootstrap-daterangepicker/daterangepicker.css';

const ranges = {
    'Today': [moment(), moment()],
    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
    'This Month': [moment().startOf('month'), moment().endOf('month')],
    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
  };

const rangesMetrics = {
  'last 1 min': [moment().subtract(1, 'minutes'), moment()],
  'last 30 min': [moment().subtract(30, 'minutes'), moment()],
  'last 4 hours': [moment().subtract(4, 'hours'), moment()]
};

class DateRangeInput extends React.Component {
  constructor(props){
    super(props);
    this.datePickerChanged = this.datePickerChanged.bind(this);
  }

  datePickerChanged(e, picker){
    const onChange = this.props.onChange ? this.props.onChange : this.props.input.onChange;
    if (onChange){
      onChange(picker.startDate.format('L HH:mm:ss'), picker.endDate.format('L HH:mm:ss'));
     // onChange(picker.startDate.format('L'), picker.endDate.format('L'));
    }
  }

  render() {
    const {label, input, readOnly, meta: {touched, error} = {}, value} = this.props;
    const valueStr = typeof value == 'object' ? `${value.since} - ${value.till}` : value;
    return (
      <DateRangePicker ranges={rangesMetrics} autoUpdateInput={false}  timePicker24Hour timePicker timePickerSeconds opens="left" onApply={this.datePickerChanged} startDate={value ? value.since : undefined} endDate={value ? value.till : undefined}>
        <input type="text" className={'form-control' + (touched && error ? ' parsley-error' : '')} onChange={()=>{}} value={valueStr} {...input} readOnly={readOnly}/>
      </DateRangePicker>
    );
  }
}

export default DateRangeInput;