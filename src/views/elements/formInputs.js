import FormElement from 'wrappers/FormElement';

import TextInput from './input/TextInput';
import CheckboxInput from './input/CheckboxInput';
import NumberInput from "rootApp/views/elements/input/NumberInput";


export const FormTextInput = FormElement(TextInput);
export const FormNumberInput = FormElement(NumberInput);
export const FormCheckboxInput = FormElement(CheckboxInput, {marginTop: '6px'});