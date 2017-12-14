variable "common_ssh_keys" {
  type = "list"
  default = [
    {
      path = "/home/hanaboso/.ssh/authorized_keys"
      key_data = "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQCx5/WpYL12pjpXTq9wQ0R4AcEgemI546sEP8kPw95r0o1egl7bMhlKNSYhuMoMdjiJSyLcFeskRY3xhejwWZMFaPv4pWUN4KZcY+dJqatYGw7xbwEopkk/5hBhBYZvFdDV4PsbW0vsU0N9rEPImVAhmLrT1rKxog472egvBPn2oUNRy3k/Br1503MvPEinTUIC2rZosjTXal7zQpHx7R6kCpjWetYqf7BYIFhrBEuX4Y2U6xZrInXqMcX3UFzUJJ6Gln9k0sb2B9e6FnKQiJO5/C3wGeL8fqqXMnlcd29z2vqia1bWJgTvcfvgCVgc1QVQ0hQ37/MfglRNQjDihlmx husak.j@hanaboso.cz"
    }
  ]
}
