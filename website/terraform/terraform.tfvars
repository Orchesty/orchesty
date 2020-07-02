region = "eu-west-1"

website_bucket_ws = {
    stage = "pipes-website-stage"
    prod = "pipes-website-prod"
}

website_aliases_ws = {
    stage = ["pipes-doc.hanaboso.com"]
    prod = ["pipes-doc.hanaboso.com"]
}

# Must be located in us-east-1!!!
website_certificate_arn_ws = {
    stage = "arn:aws:acm:us-east-1:149082520595:certificate/bdb8f270-f35b-4056-8be2-8ac71c074bc7"
    prod = "arn:aws:acm:us-east-1:149082520595:certificate/bdb8f270-f35b-4056-8be2-8ac71c074bc7"
}
