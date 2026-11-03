# Verified certificate email setup (Firebase Spark compatible)

This Worker checks Firestore every 15 minutes for certificates whose status is `verified`. It looks up the learner's **verified Firebase Authentication email** and sends a branded notification via Resend with a link to their private certificate dashboard. It does not attach a PDF: the learner signs in to download/print the certificate, so the email does not expose a private certificate file.

## Accounts and deployment

1. Create a free Cloudflare Workers account at https://dash.cloudflare.com/ and a Resend account at https://resend.com/.
2. In Resend → Domains, verify a sending domain that you control by adding the requested DNS records. Use an address such as `Telecom Digital Hub <certificates@your-verified-domain.example>` for the `RESEND_FROM` secret. Do not use an unverified domain.
3. In Google Cloud Console for Firebase project `telecomhu-1c41c`, create a **dedicated service account** with only the permissions needed for Firestore document reads, creation of `verificationEmails` documents, and Firebase Authentication user lookups. Download its JSON key securely. The exact IAM permissions should be tested before enabling the schedule. Never commit the key to GitHub.
4. Create a Cloudflare Worker using `workers/verified-certificate-email.js` as the Worker script. Configure a cron trigger `*/15 * * * *` (UTC), or deploy from the `workers` directory using the supplied `wrangler.toml` file.
5. Under Cloudflare Worker → Settings → Variables and Secrets, create these **encrypted secrets**:
   - `SERVICE_ACCOUNT_JSON`: the entire service-account JSON file (including private key). Check Cloudflare's current secret size limit; service-account JSON must fit.
   - `RESEND_API_KEY`: the Resend sending API key.
   - `RESEND_FROM`: verified sender, e.g. `Telecom Digital Hub <certificates@yourdomain.com>`.
6. Publish the latest `firestore.rules` to Firebase Console and test an exam pass, certificate saving, admin verification, and Worker execution. Confirm Resend's email logs and the Firestore `verificationEmails` delivery record.

**Security:** The service account is a high-privilege credential. Keep it in Cloudflare encrypted secrets only. Restrict IAM, rotate compromised keys, and never paste keys in chat or commit them. The Worker never trusts browser-supplied email or certificate status; it reads both directly from Firebase. Only verified Firebase Authentication email addresses are eligible.

**Behavior and limitations:** First deployment will email existing verified certificates unless they already have a `verificationEmails` record. Check existing records before enabling the schedule. The scheduled task is not instantaneous (up to ~15 minutes). It sends once per certificate ID/path and does not resend when a certificate is toggled between statuses. Resend idempotency protects against accidental retries. If a delivery attempt fails, the next scheduled check retries. If Firestore logging fails after provider acceptance, the same idempotency key is reused. The log is service-account-managed and does not require changes to client Firestore rules.

**Current certificate caveat:** `academy.html` must correctly save passed exam certificates. Verify that the generated certificate ID matches the Firestore document ID before production rollout. No certificate email is sent for missing records.

**Billing:** Firebase remains on Spark. Cloudflare Workers and Resend have independent free-plan limits, which may change. Review both providers' limits and account settings.

**Testing:** Deploy without a cron trigger first, test against a controlled learner with a verified email and a reviewed certificate, inspect Cloudflare logs and Resend logs, then enable the cron trigger. The Worker endpoint does not send mail on HTTP requests; only scheduled executions send mail.
