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


# swarm01 network
resource "azurerm_virtual_network" "main-vnet" {
  name                = "main-vnet"
  address_space       = [ "${var.stack_ip_prefix}.0.0/16" ]
  location            = "${azurerm_resource_group.main.location}"
  resource_group_name = "${azurerm_resource_group.main.name}"
}

resource "azurerm_subnet" "swarm01" {
  name                      = "swarm01"
  resource_group_name       = "${azurerm_resource_group.main.name}"
  virtual_network_name      = "${azurerm_virtual_network.main-vnet.name}"
  address_prefix            = "${var.stack_ip_prefix}.1.0/24"
  network_security_group_id = "${azurerm_network_security_group.default.id}"
}

resource "azurerm_network_security_group" "default" {
  name                = "default-nsg"
  location            = "${azurerm_resource_group.main.location}"
  resource_group_name = "${azurerm_resource_group.main.name}"

  security_rule {
    name                       = "ssh-allow"
    priority                   = 100
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "22"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }
  security_rule {
    name                       = "docker-hbo-allow"
    priority                   = 101
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "2376"
    source_address_prefix      = "188.122.212.69/32"
    destination_address_prefix = "*"
  }
  security_rule {
    name                       = "cc-stage1-http-hbo-allow"
    priority                   = 102
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "6540"
    source_address_prefix      = "188.122.212.69/32"
    destination_address_prefix = "*"
  }
}
