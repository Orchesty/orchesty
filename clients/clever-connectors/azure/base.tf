# Variables

variable "stack_resource_group" {}
variable "stack_location" {}
variable "stack_ip_prefix" {}

# Terraform config
terraform {

  # Use remote state stored in Azure storage account
  backend "azurerm" {
    storage_account_name = "terraformtho3jafi"
    #access_key           = "SET THIS VALUE BY terraform init -backend-config, see README.md"
    container_name       = "tfstate"
    key                  = "stage1.terraform.tfstate"
  }

}


# Our only resource group, for now
resource "azurerm_resource_group" "main" {
  name     = "${var.stack_resource_group}"
  location = "${var.stack_location}"
}


# swarm1 network
resource "azurerm_virtual_network" "swarm1" {
  name                = "swarm1"
  address_space       = [ "${var.stack_ip_prefix}.0.0/16" ]
  location            = "${azurerm_resource_group.main.location}"
  resource_group_name = "${azurerm_resource_group.main.name}"
}

resource "azurerm_subnet" "swarm1" {
  name                 = "swarm1"
  resource_group_name  = "${azurerm_resource_group.main.name}"
  virtual_network_name = "${azurerm_virtual_network.swarm1.name}"
  address_prefix       = "${var.stack_ip_prefix}.1.0/24"
}
