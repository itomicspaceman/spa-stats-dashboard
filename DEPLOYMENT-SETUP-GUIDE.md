# Automatic Deployment Setup Guide

## Current Status

✅ **Code is deployed** - The embed parameter changes are live  
✅ **Webhook script exists** - `/home/stats/repo/webhook-deploy.php`  
❌ **GitHub webhook NOT configured** - Needs to be added manually  

## The Problem

When you push to `main` branch on GitHub, cPanel doesn't automatically deploy because:
- GitHub doesn't know to notify cPanel
- No webhook is configured in GitHub

## The Solution: Add GitHub Webhook

### Step 1: Get Webhook URL

The webhook URL is:
```
https://stats.squashplayers.app/webhook-deploy.php
```

### Step 2: Add Webhook to GitHub

1. **Go to GitHub Repository Settings:**
   ```
   https://github.com/itomic/squash-court-stats/settings/hooks
   ```

2. **Click "Add webhook"**

3. **Fill in the form:**
   - **Payload URL:** `https://stats.squashplayers.app/webhook-deploy.php`
   - **Content type:** `application/json`
   - **Secret:** `413d66fed586f3447e62dd9f2f574400868b1ebf738cdd4278cf31b0a0be3b6b`
   - **Which events?** Select "Just the push event"
   - **Active:** ✅ Checked

4. **Click "Add webhook"**

### Step 3: Test the Webhook

After adding, GitHub will send a test ping. You can verify it worked by:

1. **Check webhook logs:**
   ```bash
   ssh root@atlas.itomic.com "tail -20 /home/stats/logs/webhook-deploy.log"
   ```

2. **Make a test commit:**
   ```bash
   git commit --allow-empty -m "Test webhook deployment"
   git push origin main
   ```

3. **Check deployment logs:**
   ```bash
   ssh root@atlas.itomic.com "tail -20 /home/stats/logs/deploy-output.log"
   ```

## How It Works

1. **You push to GitHub** → `git push origin main`
2. **GitHub sends webhook** → POST to `webhook-deploy.php`
3. **Webhook verifies signature** → Uses secret to validate request
4. **Webhook checks branch** → Only deploys if `refs/heads/main`
5. **Webhook runs deploy.sh** → Executes deployment script in background
6. **Deployment completes** → Site is updated automatically

## Manual Deployment (Fallback)

If webhook fails, you can deploy manually:

### Option 1: Use cPanel UI
1. Go to cPanel → Git Version Control
2. Click "Manage" on `spa-stats-dashboard`
3. Click "Pull or Deploy" tab
4. Click "Deploy HEAD Commit"

### Option 2: Use SSH
```bash
ssh root@atlas.itomic.com "bash /home/stats/deploy.sh"
```

## Deployment Script Details

The deployment script (`/home/stats/deploy.sh`) does:
1. ✅ Pulls latest from GitHub (`git pull origin main`)
2. ✅ Syncs files to `/home/stats/current` (rsync)
3. ✅ Installs npm dependencies
4. ✅ Builds frontend assets (`npm run build`)
5. ✅ Clears Laravel caches
6. ✅ Updates build symlink

## Verification

After deployment, verify it worked:

1. **Check commit in current directory:**
   ```bash
   ssh root@atlas.itomic.com "cd /home/stats/current && git log --oneline -1"
   ```

2. **Test embed parameter:**
   ```
   https://stats.squashplayers.app/trivia?embed=1
   ```
   Should show: **NO navigation bar or hero section**

3. **Test section parameter:**
   ```
   https://stats.squashplayers.app/trivia?section=graveyard&embed=1
   ```
   Should show: **Only Squash Court Graveyard section**

## Troubleshooting

### Webhook Not Triggering

**Check:**
1. Is webhook active in GitHub? (Settings → Webhooks)
2. Is the URL correct? (`https://stats.squashplayers.app/webhook-deploy.php`)
3. Check GitHub webhook delivery logs (Settings → Webhooks → Recent Deliveries)
4. Check server logs: `/home/stats/logs/webhook-deploy.log`

### Deployment Fails

**Check:**
1. Server disk space: `df -h`
2. Permissions: `/home/stats/current` should be writable
3. Node.js version: Should be Node 24
4. PHP version: Should be PHP 8.3
5. Deployment logs: `/home/stats/logs/deploy-output.log`

### Changes Not Appearing

**Check:**
1. Laravel view cache: `rm -rf /home/stats/current/storage/framework/views/*`
2. Browser cache: Hard refresh (Ctrl+F5)
3. CDN cache: Clear if using CloudFlare or similar
4. Check commit: Is it actually in `/home/stats/current`?

## Next Steps

1. ✅ **Add GitHub webhook** (see Step 2 above)
2. ✅ **Test with a small commit**
3. ✅ **Verify automatic deployment works**
4. ✅ **Document for team**

Once the webhook is configured, every push to `main` will automatically deploy! 🚀

