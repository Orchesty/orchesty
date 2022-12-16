module "rabbitmq-c1n1" {
    source = "./modules/rabbitmq_v1"
    name = "rabbitmq-c1n1"
    zone = "${var.region}-${var.zones[0]}"
    machine_type = "e2-medium"
}

module "rabbitmq-c1n2" {
    source = "./modules/rabbitmq_v1"
    name = "rabbitmq-c1n2"
    zone = "${var.region}-${var.zones[1]}"
    machine_type = "e2-medium"
}

module "rabbitmq-c1n3" {
    source = "./modules/rabbitmq_v1"
    name = "rabbitmq-c1n3"
    zone = "${var.region}-${var.zones[2]}"
    machine_type = "e2-medium"
}
