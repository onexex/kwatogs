# Deployment Guide

This project auto-deploys via GitHub Actions. **Nothing deploys unless you push to one of these two branches:**

| Push to branch | Workflow | Deploys to |
|----------------|----------|------------|
| `staging` | `.github/workflows/staging.yml` | **Staging** — https://staging-kwatogspayroll.kmds-ph.com |
| `master`  | `.github/workflows/deploy.yml`  | **Production** — https://kwatogspayroll.kmds-ph.com |

Each deploy: zips the project → SFTP upload → unzip on server → fix permissions → `php artisan migrate --force` → `optimize`.

> ⚠️ **`master` is live payroll data for real employees. Always test on `staging` first.**

---

## Standard flow: staging → production

### 1. Deploy to STAGING and test
```bash
git checkout staging
git pull origin staging          # get latest

# ...make your code changes...

git add .
git commit -m "describe your change"
git push origin staging          # 🚀 auto-deploys to STAGING
```
Then verify at **https://staging-kwatogspayroll.kmds-ph.com**.

### 2. Promote the SAME code to PRODUCTION
```bash
git checkout master
git pull origin master
git merge staging                # bring tested changes into master
git push origin master           # 🚀 auto-deploys to PRODUCTION
```

The loop: **work → push `staging` → verify → merge to `master` → push → live.**

---

## Bigger changes: use a feature branch
Keeps `staging` as a clean "currently being tested" line.
```bash
git checkout -b feat/my-change master
# ...work, commit...

# test on staging
git checkout staging && git merge feat/my-change && git push origin staging

# after it looks good, ship to production
git checkout master && git merge feat/my-change && git push origin master
```

---

## Important notes / guardrails

- **`.env` files are never deployed.** Each server keeps its own:
  - Staging `.env` → database `dbdash_stagging`
  - Production `.env` → database `dbdash`
  - This is why the same code runs safely on both — they point at different databases.
- **Migrations run automatically** on every deploy (`php artisan migrate --force`). New migrations hit the **staging** DB first (on `staging` push), then **production** (on `master` push). This is the safety you want — never run an untested migration straight to production.
- **The staging deploy aborts if no `.env` exists** in the staging folder on the server. This is an intentional guard so a deploy can never run migrations against the wrong/empty database.
- **Manual trigger:** the staging workflow also has a "Run workflow" button (workflow_dispatch) on the **[Actions](https://github.com/onexex/kwatogs/actions)** page.
- **Watch every deploy** at **https://github.com/onexex/kwatogs/actions** — green ✓ = deployed, red ✗ = open the failed step for the log.

---

## First-time staging setup (already done — for reference)

If staging ever needs to be rebuilt on a new server/subdomain:
1. **cPanel → Subdomains:** create `staging-kwatogspayroll.kmds-ph.com` (document root `public_html/staging-kwatogspayroll.kmds-ph.com/`).
2. **cPanel → MySQL Databases:** create a **separate** staging database + user (e.g. `dbdash_stagging`) and assign the user to it.
3. **cPanel → File Manager:** in the staging folder, create a `.env` (copy the `.env.staging` template at the repo root, fill in the staging DB credentials, set a unique `APP_KEY`). This file stays on the server and is never overwritten by deploys.
4. Push to `staging` (or use the Run workflow button) to deploy.
