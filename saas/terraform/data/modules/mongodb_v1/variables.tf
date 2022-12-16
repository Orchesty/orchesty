variable "name" {
  type        = string
  description = "Instance name"
}

variable "machine_type" {
  type        = string
  description = "Machine type"
}

variable "zone" {
  type        = string
  description = "Compute zone in form <region>-<z>"
}
