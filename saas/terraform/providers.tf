data "google_project" "project" {}

terraform {
  backend "gcs" {}
}

provider "google" {
  region      = var.region
  project     = var.project_id
  credentials = file("../account-${terraform.workspace}.json")
}
