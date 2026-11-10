# Security Policy

## Reporting a Vulnerability

We take the security of TelecomHub seriously. If you believe you have found a security vulnerability, please report it responsibly.

### How to Report

**Preferred:** Use GitHub's [private vulnerability reporting](https://docs.github.com/code-security/security-advisories/guidance-on-reporting-and-writing-information-about-vulnerabilities/privately-reporting-a-security-vulnerability) feature on this repository.

1. Go to the **Security** tab of this repository
2. Click **Report a vulnerability**
3. Provide a clear description of the issue, steps to reproduce, and potential impact

**Alternatively:** Email the repository owner directly.

### Response Timeline

- **Acknowledgment:** Within 48 hours
- **Initial Assessment:** Within 5 business days
- **Resolution:** Depends on severity — critical issues prioritized

### Scope

This policy covers:
- The TelecomHub web application (HTML, JavaScript, CSS)
- Firebase configuration and security rules
- Cloudflare Workers code
- Authentication and user data handling

### Out of Scope

- Firebase API keys marked as public (these are designed to be public and restricted via Firebase Console settings)
- Third-party services (Firebase, Google Analytics, Google AdSense) — report to their respective security teams

### Responsible Disclosure

Please do not:
- Publicly disclose the vulnerability before it is resolved
- Access or modify data that does not belong to you
- Use automated scanners that may degrade service for other users

Thank you for helping keep TelecomHub secure!
