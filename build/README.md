# Build & CI

## Milestones

### M1

 * Prepare WORKING build scripts for all subprojects
 * Reolve the workdir problem
 * * ~~pipes-frontend~~
 * ~~Prepare NodeJS dev image, usable for local development AND as a build image (the PHP way)~~
 * Simplify clients' docker-compose files (find a best way to extend them)
 * Finist clients' prod docker-composes 


### M2

 * Do an iteration fixing obvious flaws
 * Rename/move nodejs-build and php-dev to something more sane
 * ~~Get rid of pipes- prefix from the frontend subproject~~
 * Decide whether to use Scallop instead of Make
 * Rewrite frontend  build scripts to single Makefile
 * Use new NodeJS dev image instead of the current build image
 * Enhance NPM caching
 * Enghance Composer caching
 * Look into pf-bundle docker-compose and see if we need a special nginx contaner
