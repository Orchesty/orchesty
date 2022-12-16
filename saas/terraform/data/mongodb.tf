module "mongodb-rs1n1" {
    source = "./modules/mongodb_v1"
    name = "mongodb-rs1n1"
    zone = "${var.region}-${var.zones[0]}"
    machine_type = "e2-medium"
}

module "mongodb-rs1n2" {
    source = "./modules/mongodb_v1"
    name = "mongodb-rs1n2"
    zone = "${var.region}-${var.zones[1]}"
    machine_type = "e2-medium"
}

module "mongodb-rs1n3" {
    source = "./modules/mongodb_v1"
    name = "mongodb-rs1n3"
    zone = "${var.region}-${var.zones[2]}"
    machine_type = "e2-medium"
}
