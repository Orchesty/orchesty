// Button types
export type ButtonVariant = 'primary' | 'outline'

export interface ButtonProps {
  variant?: ButtonVariant
  type?: 'button' | 'submit' | 'reset'
}

// Input types
export type InputType = 'text' | 'email' | 'password' | 'number'

export interface InputProps {
  type?: InputType
  label?: string
  placeholder?: string
  modelValue?: string | number
  required?: boolean
  disabled?: boolean
  id?: string
}

// Checkbox types
export interface CheckboxProps {
  modelValue?: boolean
  label?: string
  disabled?: boolean
  id?: string
}


