# Certbot Terraform Stack and Docker Image

## Abstract

We use Certbot to manage the main certificate instead of GC's native managed certs, because they do not support wildcard certificates yet.

## The Setup

### Docker Image

The main logic is provided by scripts inside a docker-image in such named subdirectory here.
The image is built and pushed to Google Artifact Registry manually using a Makefile in that subdirectory.
The registry URL is hardcoded in the Makefile, pointing to the orchesty-cloud-prod/infrastructure registry, set up by `core` Terraform stack.
You need to modify the Makefile if you need to do something special.

### Scripts

* `renew.sh` calls `certbot renew` when the cert material already exists.

* `certonly.sh` calls `certbot certonly` and can be used to issue a new certificate, expand the existing one and so on. Even though it can be used also to renew the certificate, I don't recommend it because there are edge cases when it can create a duplicate certificate with a number-suffixed name.

* `manage-gc.sh` This script is called by previously mentioned scripts when the certificate file changes. It creates a new timestamped GC self-managed certificate from the Live cert material and updates the Target Proxy, if not overrided by SKIP_DEPLOYMENT var set to nonempty value.

* `config.sh` is being sourced by previously described scripts. It defines and assembles important variables, such as the list of SAN domains. You have to switch between those by (clumsily) modifying the `job.tf` file.

The scripts also accept extra Certbot arguments using CERTBOT_EXTRA_ARGS env var.


### Terraform Stack

The Terraform stack expects the image to be present before applying.
It creates a Cloud Run Job with a Schedule and additional resources.


### Target Proxy (load balancer) in `core` stack integration.

*not yet implemented!* When the `core` stack is bootstrapped (BOOTSTRAP=true), it creates a dummy managed certificate to be able to create the Load Balancer and the rest of the stack.

By applying the `certbot` stack you override that dummy cert. Subsequent `core` stack updates ignore the cert that is being used by the Target Proxy and do not interfere.