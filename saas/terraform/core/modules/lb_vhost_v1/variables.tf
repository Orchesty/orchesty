variable "name" {
  type = string
}

variable "enabled" {
  description = "When not enabled, the NEGs won't be attached (like with params.bootstrap=true)"
  type        = bool
  default     = true
}

variable "timeout" {
  description = "Max request duration"
  type        = number
  default     = 10
}

variable "params" {
  description = "Params common for all vhosts"

  type = object({
    region    = string,
    zones     = list(string),
    bootstrap = bool,

    default_healthcheck_id = string,
  })
}