# Azure Infrastructure as Code

## Prereuquisites

* Install Azure CLI (the Python one, knowna as v2.0) [link](https://docs.microsoft.com/en-us/cli/azure/install-azure-cli?view=azure-cli-latest)
* Install Terraform [link](https://www.terraform.io/downloads.html)
* Login to use the Azure CLI (run: az login)
* Obtain access key of Storage Account "terraformtho3jafi" using Azure Portal
* Change to this directory

## Connecting Terraform backend to remote state in Azure

```
# Initialize the terraform backend
terraform init -backend-config="access_key=<your-access-key>"

# Clear the access key from shell history!!!
history -r

```
