# cPanel deploy (paused)

Deploying to the cPanel server (195.250.26.84) is paused: production now runs on the
Hostinger VPS (`.github/workflows/deploy-vps.yml`).

These workflow files are kept here unchanged so GitHub Actions no longer lists or runs
them. To bring the cPanel deploy back, move them back into `.github/workflows/`:

    git mv deploy/cpanel/workflows/*.yml .github/workflows/

`deploy/remote.sh` is shared by both deploys, so it stays where it is.
