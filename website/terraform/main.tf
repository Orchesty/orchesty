variable "region" {}

variable "website_bucket_ws" {
    type = map
}

variable "website_aliases_ws" {
    type = map
}

variable "website_certificate_arn_ws" {
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
    Stack = local.tf_stack
  }
}

resource "aws_cloudfront_distribution" "website_distribution" {
  origin {
    domain_name = aws_s3_bucket.website_bucket.website_endpoint
    origin_id   = "pipes-website-${terraform.workspace}"
    custom_origin_config {
      origin_protocol_policy = "http-only"
      http_port              = "80"
      https_port             = "443"
      origin_ssl_protocols   = ["TLSv1.2"]
    }
  }

  enabled             = true
  is_ipv6_enabled     = true
  default_root_object = "index.html"

  aliases = var.website_aliases_ws[terraform.workspace]

  default_cache_behavior {
    allowed_methods  = ["GET", "HEAD", "OPTIONS"]
    cached_methods   = ["GET", "HEAD"]
    target_origin_id = "pipes-website-${terraform.workspace}"

    forwarded_values {
      query_string = false
      cookies {
        forward = "none"
      }
    }

    viewer_protocol_policy = "redirect-to-https"
    min_ttl                = 0
    default_ttl            = 60 #3600
    max_ttl                = 60 #86400
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    ssl_support_method = "sni-only"
    minimum_protocol_version = "TLSv1.2_2018"
    acm_certificate_arn = var.website_certificate_arn_ws[terraform.workspace]
  }

  tags = {
    Stack = local.tf_stack
  }
}
