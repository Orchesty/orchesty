# Orchesty Helm Charts

## Requirements

### subrepo git plugin

1. Familiarize yourself with the subrepo plugin (https://github.com/ingydotnet/git-subrepo#readme)
1. `git clone https://github.com/ingydotnet/git-subrepo ~/git-subrepo`
1. activate the plugin: `source ~/git-subrepo/.rc`

## Release

### Prepare chart(s)

1. Go to root dir of the orchesty repo
1. Pull changes from the public charts repo (this step also checks if the subrepo link is up to date) \
   `git subrepo pull helm/charts`
1. Update version in `helm/charts/orchesty/Chart.yaml`
1. Update `helm/charts/orchesty/RELEASE_NOTES.md`
1. Commit changes
1. Push changes to subrepo \
   `git subrepo push helm/charts`
1. Push changes (including now updated subrepo link) to monorepo \
   `git push`

### Update the helm repo

You can do these steps in a single make call, if you feel like it

1. `cd helm`
1. Pull the helm registry branch to temp dir \
   `make helm-repo`
1. Prepare a helm release tarball of the new version \
   `make orchesty-release`
1. Reindex the helm repo \
   `make reindex`
1. Push the helm repo changes \
   `make push`
