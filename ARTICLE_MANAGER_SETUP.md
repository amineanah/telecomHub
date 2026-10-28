# TelecomHub — No-code Articles Manager

Branch: `feature/firestore-article-manager`

## Access

1. Confirm the Firebase Authentication user UID for your administrator is `wq68WfHSlzW1SNCeyIC2uoNwYvd2`.
2. After GitHub pull request is merged, sign in on the main site and open:
   `https://www.telcomdigitalhub.com/admin-articles.html`
3. Enter article title, category, short description, cover image URL (optional), and long article text.
4. Add as many as 20 extra media items: images, PDFs, YouTube videos, hosted videos or resource links. Use HTTPS media URLs.
5. Click **Save draft** to keep it private or **Publish article** to show it in the public Knowledge Hub.
6. Use **Edit**, **Unpublish**, or **Delete** in the sidebar to manage content.
7. Published articles automatically populate the existing Knowledge Hub search and filters. They open via `article.html?id=FIRESTORE_DOCUMENT_ID`.

## CRITICAL: Firestore rules are not deployed by GitHub

This feature will NOT work until Firestore rules are configured:

- Firebase Console > `telecomhu-1c41c` > Firestore Database > Rules.
- Read the repository `firestore.rules`.
- If Firestore is only used for visitorFeedback and articles, you can publish this complete file.
- If you have additional collections, **merge** the `articles` match block into your current rules without deleting the other collection permissions.
- Publish and test with an admin and a non-admin user.
- Only signed-in users with the matching UID can write articles. Guests can only read articles with status `published`. Drafts cannot be fetched by guests.

## Optional direct file uploads: Firebase Storage

The editor also supports uploading JPG, PNG, WebP, PDF, MP4 and WebM files up to 25 MB. To enable:

1. Firebase Console > Storage > Get started. **Firebase Storage may require upgrading to the Blaze billing plan; check pricing before enabling.**
2. Publish the repository `storage.rules` in the Storage Rules panel, merging with any existing rules. Publishing the GitHub file alone does not deploy it.
3. The upload action stores media under `article-media/ADMIN_UID/UUID-filename`. It then attaches the HTTPS download URL to the article form; **save the article after uploading**.
4. Uploaded media is publicly readable *even if the associated article is still a draft*. Never upload confidential files.
5. Deleting the article does **not** delete its Storage uploads. Remove unused media manually in Firebase Storage if desired.
6. The 25 MB upload limit is deliberate. For large videos, prefer YouTube hosting and paste the video URL.

If you do not want to enable Firebase Storage, URL-based image, video, PDF and resource attachments still work without it.

## Notes

- The public article page renders text safely as text, not arbitrary HTML or executable Markdown. Paragraphs can be separated with blank lines. It does not expose a rich WYSIWYG editor.
- YouTube URLs support youtube.com/watch?v=, youtube.com/embed/, youtube.com/shorts/ and youtu.be/.
- PDFs are embedded when the source permits framing and include a direct open/download link.
- Existing 18 Knowledge Hub resources remain present as static cards. New Firestore articles appear alongside them, and category/search filtering works on both.
- Check image licensing and article accuracy before publishing. Do not copy material without permission.
- Articles loaded from Firestore are client-side rendered and may not be discoverable by every crawler like static HTML pages. For long-term SEO/AdSense quality, consider a static export or server-rendered article pipeline.
- Firestore and Storage service usage may incur charges under your account's plan. Configure budget alerts as appropriate.
- The new features require live browser and Firebase permissions testing after deployment.
