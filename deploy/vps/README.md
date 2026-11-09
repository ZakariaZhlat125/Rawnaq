# VPS deploy (paused in this repo)

This repository is the Rawnaq demo copy; it deploys to the cPanel server only
(`.github/workflows/deploy-cpanel.yml`). The Hostinger VPS workflows are kept here,
outside `.github/workflows/`, so a push to this repo can never reach the live
Sami Care site. To bring them back:

    git mv deploy/vps/workflows/*.yml .github/workflows/
