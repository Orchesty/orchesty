variable "region" {}
variable "website_bucket_ws" {
    type = map
}

locals {
    tf_stack = "pipes-website-${terraform.workspace}"
}

provider "aws" {
  region = var.region
}

resource "aws_s3_bucket" "website_bucket" {
  bucket = var.website_bucket_ws[terraform.workspace]
  acl    = "public-read"
  website {
    index_document = "index.html"
  }
  tags = {
    tf_stack = local.tf_stack
  }
}
