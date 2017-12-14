# VM swarm1n01
resource "azurerm_network_interface" "swarm1n01-eth0" {
  name                = "swarm1n01-eth0"
  location            = "${azurerm_resource_group.main.location}"
  resource_group_name = "${azurerm_resource_group.main.name}"

  ip_configuration {
    name                          = "ipconf-eth0"
    subnet_id                     = "${azurerm_subnet.swarm1.id}"
    private_ip_address_allocation = "dynamic"
  }
}

resource "azurerm_virtual_machine" "swarm1n01" {
  name                  = "swarm1n01"
  location              = "${azurerm_resource_group.main.location}"
  resource_group_name   = "${azurerm_resource_group.main.name}"
  network_interface_ids = [ "${azurerm_network_interface.swarm1n01-eth0.id}" ]
  vm_size               = "Standard_F4s_v2"

  delete_os_disk_on_termination = true

  storage_image_reference {
    publisher = "Canonical"
    offer     = "UbuntuServer"
    sku       = "16.04-LTS"
    version   = "latest"
  }

  storage_os_disk {
    name              = "myosdisk1"
    caching           = "ReadWrite"
    create_option     = "FromImage"
    managed_disk_type = "Standard_LRS"
  }

  os_profile {
    computer_name  = "swarm1n01"
    admin_username = "hanaboso"
  }

  os_profile_linux_config {
    disable_password_authentication = true
    ssh_keys = "${var.common_ssh_keys}"
  }
}
