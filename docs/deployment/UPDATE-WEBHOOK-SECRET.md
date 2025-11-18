# Update Webhook Secret - Step by Step

## Current Secret Value

The secret configured in `webhook-deploy.php` is:
```
413d66fed586f3447e62dd9f2f574400868b1ebf738cdd4278cf31b0a0be3b6b
```

## Steps to Update Secret in GitHub

1. **Go to GitHub Webhook Settings:**
   - Navigate to: https://github.com/itomic/squash-court-stats/settings/hooks
   - Click on the existing webhook

2. **Update the Secret:**
   - Click the **"Change secret"** button
   - Paste this value: `413d66fed586f3447e62dd9f2f574400868b1ebf738cdd4278cf31b0a0be3b6b`
   - Click **"Update secret"**

3. **Verify the Update:**
   - GitHub will automatically send a test ping
   - Check the "Recent Deliveries" tab
   - Look for a **200 OK** response (green checkmark ✅)

4. **Check Server Logs:**
   ```bash
   ssh root@atlas.itomic.com "tail -10 /home/stats/logs/webhook-deploy.log"
   ```
   - Should show: `Webhook request: Method=POST, Event=ping, Signature=present`
   - Should NOT show: `[ERROR] Invalid signature` or `[ERROR] Missing signature header`

## Test the Webhook

After updating the secret, test with a real push:

```bash
git commit --allow-empty -m "Test webhook after secret update"
git push origin main
```

Then check deployment logs:
```bash
ssh root@atlas.itomic.com "tail -30 /home/stats/logs/deploy-output.log"
```

## Troubleshooting

### If GitHub Shows 403 Errors

- **Symptom:** Recent deliveries show "403 Forbidden"
- **Cause:** Secret mismatch
- **Solution:** Ensure the secret in GitHub matches exactly (no extra spaces, no line breaks)

### If GitHub Shows 200 OK But No Deployment

- **Symptom:** Webhook receives requests but deployment doesn't happen
- **Check:** 
  ```bash
  ssh root@atlas.itomic.com "tail -50 /home/stats/logs/webhook-deploy.log"
  ```
  - Look for "Deployment triggered" messages
  - Check for any error messages

### If No Requests Are Received

- **Symptom:** Webhook log is empty
- **Check:**
  1. Webhook is "Active" in GitHub
  2. Webhook URL is correct: `https://stats.squashplayers.app/webhook-deploy.php`
  3. Webhook is configured for "push" events
  4. You're pushing to the `main` branch (not `develop`)

## Verification Checklist

- [ ] Secret updated in GitHub webhook settings
- [ ] Test ping shows 200 OK in GitHub
- [ ] Server webhook log shows incoming requests
- [ ] No "Invalid signature" errors in logs
- [ ] Test push to main triggers deployment
- [ ] Deployment log shows successful deployment

