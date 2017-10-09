import FormElement from 'wrappers/FormElement';

import TextInput from './input/TextInput';
import CheckboxInput from './input/CheckboxInput';
import NumberInput from './input/NumberInput';
import TextAreaInput from './input/TextAreaInput';


export const FormTextInput = FormElement(TextInput);
export const FormNumberInput = FormElement(NumberInput);
export const FormCheckboxInput = FormElement(CheckboxInput, {marginTop: '6px'});
export const FormTextAreaInput = FormElement(TextAreaInput);