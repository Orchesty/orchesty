variable "name" {}
variable "resource_group_name" {}
variable "location" {}
variable "subnet_id" {}

variable "ssh_keys" {
  type = "list"
  description = "A collection of { path = '/home/<user>/.ssh/authorized_keys', key_data = '...' } structures"
}
