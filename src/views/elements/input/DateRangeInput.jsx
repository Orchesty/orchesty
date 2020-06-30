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
  'last 12 hours': [moment().subtract(12, 'hours'), moment()]
};

class DateRangeInput extends React.Component {
  constructor(props){
    super(props);
    this.datePickerChanged = this.datePickerChanged.bind(this);
  }

  datePickerChanged(e, picker){
    const onChange = this.props.onChange ? this.props.onChange : this.props.input.onChange;
    if (onChange){
      onChange(picker.startDate.format(), picker.endDate.format());
     // onChange(picker.startDate.format('L'), picker.endDate.format('L'));
    }
  }

  render() {
    const {input, readOnly, meta: {touched, error} = {}, value} = this.props;
    const since = moment(value.since);
    const till = moment(value.till);
    const valueStr = typeof value == 'object' ? `${since.format('DD.MM.YYYY HH:mm:ss')} - ${till.format('DD.MM.YYYY HH:mm:ss')}` : value;
    return (
      <DateRangePicker containerStyles={{display: 'block'}} ranges={rangesMetrics} autoUpdateInput={false} locale={{format: 'DD.MM.YYYY HH:mm:ss'}}  timePicker24Hour timePicker timePickerSeconds opens="left" onApply={this.datePickerChanged} startDate={value ? since : undefined} endDate={value ? till : undefined}>
        <input type="text" className={'form-control' + (touched && error ? ' parsley-error' : '')} onChange={()=>{}} value={valueStr} {...input} readOnly={readOnly}/>
      </DateRangePicker>
    );
  }
}

export default DateRangeInput;