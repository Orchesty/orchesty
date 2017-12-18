locals {
  provisioning_script = [
    "curl -O http://apt.puppetlabs.com/puppetlabs-release-pc1-wheezy.deb",
    "dpkg -i puppetlabs-release-pc1-wheezy.deb",
    "apt-get update",
    "apt-get install -y puppet-agent",
    "export PATH=$PATH:/opt/puppetlabs/bin",
    "systemctl stop puppet.service",
    "systemctl disable puppet.service",
    "puppet agent --enable",
    "echo '188.122.212.69 puppet' >> /etc/hosts",
    "echo -e '[agent]\\nserver = puppet\\ncertname = ${var.name}.cm.hanaboso.local' > /etc/puppetlabs/puppet/puppet.conf",
    "puppet agent --waitforcert 30 --test || echo PUPPET EC: $?",
  ],
  provisioning_script_map = {
    commandToExecute = "bash -c \"${join(" && ", local.provisioning_script)}\""
  }
}

# Until we create a bastion host
resource "azurerm_public_ip" "pip1" {
  name                         = "${var.name}-pip1"
  location                     = "${var.location}"
  resource_group_name          = "${var.resource_group_name}"
  public_ip_address_allocation = "Static"
  idle_timeout_in_minutes      = 30
}

resource "azurerm_network_interface" "nic1" {
  name                = "${var.name}-nic1"
  location            = "${var.location}"
  resource_group_name = "${var.resource_group_name}"

  ip_configuration {
    name                          = "${var.name}-nic1ip1"
    subnet_id                     = "${var.subnet_id}"
    private_ip_address_allocation = "dynamic"
    public_ip_address_id          = "${azurerm_public_ip.pip1.id}"
  }
}

resource "azurerm_virtual_machine" "node" {
  name                  = "${var.name}-vm"
  location              = "${var.location}"
  resource_group_name   = "${var.resource_group_name}"
  network_interface_ids = [ "${azurerm_network_interface.nic1.id}" ]
  vm_size               = "Standard_F4s_v2"

  delete_os_disk_on_termination = true

  storage_image_reference {
    publisher = "Canonical"
    offer     = "UbuntuServer"
    sku       = "16.04-LTS"
    version   = "latest"
  }

  storage_os_disk {
    name              = "${var.name}-osdisk"
    caching           = "ReadWrite"
    create_option     = "FromImage"
    managed_disk_type = "Standard_LRS"
  }

  os_profile {
    computer_name  = "${var.name}"
    admin_username = "hanaboso"
  }

  os_profile_linux_config {
    disable_password_authentication = true
    ssh_keys = "${var.ssh_keys}"
  }
}

resource "azurerm_virtual_machine_extension" "node_provisioner" {
  name                 = "provisioner"
  location             = "${var.location}"
  resource_group_name  = "${var.resource_group_name}"
  virtual_machine_name = "${azurerm_virtual_machine.node.name}"
  publisher            = "Microsoft.OSTCExtensions"
  type                 = "CustomScriptForLinux"
  type_handler_version = "1.2"
  settings             = "${jsonencode(local.provisioning_script_map)}"
}
