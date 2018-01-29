import FilterElement from 'wrappers/FilterElement';
import SelectInput from 'elements/input/SelectInput';
import TextInput from 'rootApp/views/elements/input/TextInput';
import DateRangeInput from 'rootApp/views/elements/input/DateRangeInput';

export const FilterSelect = FilterElement(SelectInput);
export const FilterTextInput = FilterElement(TextInput);
export const FilterDateRangeInput = FilterElement(DateRangeInput);