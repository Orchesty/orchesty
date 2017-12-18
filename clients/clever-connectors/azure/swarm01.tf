## We use multiple swarm_node definitions due to better manageability during development,
## migration to VMSS is the plan.

module "swarm01n01" {
  source              = "./modules/swarm_node"
  name                = "swarm01n01"
  location            = "${azurerm_resource_group.main.location}" 
  resource_group_name = "${azurerm_resource_group.main.name}"
  subnet_id           = "${azurerm_subnet.swarm01.id}"
  ssh_keys            = "${var.common_ssh_keys}"
}

module "swarm01n02" {
  source              = "./modules/swarm_node"
  name                = "swarm01n02"
  location            = "${azurerm_resource_group.main.location}" 
  resource_group_name = "${azurerm_resource_group.main.name}"
  subnet_id           = "${azurerm_subnet.swarm01.id}"
  ssh_keys            = "${var.common_ssh_keys}"
}
