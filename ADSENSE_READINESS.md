# AdSense readiness changes — TelecomHub

This branch addresses identified source-level AdSense readiness issues:

- Adds Privacy Policy describing Google AdSense/Analytics, Firebase auth and Firestore feedback, embeds and cookies.
- Adds Contact & About page with a genuine published LinkedIn contact channel; no email address was invented.
- Adds ads.txt using the AdSense publisher ID already present in index.html. **Owner must verify publisher ID in AdSense > Account > Account information.**
- Adds robots.txt and sitemap.xml to help discovery, without claiming approval.
- Adds links to policies and contact across current pages, and corrects broken local navigation references.

**Manual action required**: Google-certified CMP consent messaging cannot be legitimately implemented merely by adding a custom cookie popup. In your AdSense account, visit Privacy & messaging and configure a Google-certified IAB TCF CMP for relevant EEA, UK and Switzerland visitors, plus any required consent choices for other regions. Confirm the message is published and test it in a relevant region. See: https://support.google.com/adsense/answer/13554116

**Manual review required**: verify the factual statements in privacy-policy.html against all services you actually use, processing and retention practices; set up an accessible direct privacy contact method if appropriate. Review GDPR and applicable laws as relevant. Confirm publisher identity and AdSense status. Verify ad rendering and real-world speed/mobile UX with actual browsers. AdSense approval is Google's decision, not determined by files on GitHub.
