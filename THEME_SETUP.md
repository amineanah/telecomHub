# TelecomHub light/dark theme rollout

This branch adds the shared `css/theme.css` and `js/theme.js` to all active HTML pages in the repository (excluding `index-backup-current.html`, which is an archived copy not linked from the site).

## Behaviour
- Theme is consistent across pages through existing `localStorage["theme"]` values of `"light"` or `"dark"`.
- If no preference was saved, Light mode is selected initially.
- Pages with an existing `#themeToggle` keep their existing toggle handler; the shared script synchronizes button state and palette.
- Pages without a theme switch get a fixed accessible theme toggle at the bottom-right.
- The public Knowledge Hub gains a genuine light palette. The feedback section becomes dark when dark mode is selected.
- Login, article management, virtual simulator and other dark-first pages get light mode adaptations.
- The repository's existing contents, Firebase Authentication/Firestore/Storage integration, existing internal links, and AdSense scripts are not replaced.

## Testing after merge
1. Open the homepage in a private browser with no saved theme; it should display light mode.
2. Toggle Dark mode, navigate to Fiber Optics and RAN articles, verify dark mode persists.
3. Visit login.html, admin-feedback.html, admin-articles.html, article.html?id=ARTICLE_ID, and virtual-telecom-site.html.html. Each should follow the stored preference.
4. Toggle Light mode from one of these pages and navigate back to the homepage. Colors should return to light.
5. Check mobile layouts, contrast, article card buttons, simulator canvas, login inputs, PDF embed and admin controls.
6. Inspect browsers' console for any old theme scripts that assume a missing toggle.

These changes are source-checked but have not received full browser/device testing. More page-specific tuning may be necessary for highly custom simulation interfaces.
