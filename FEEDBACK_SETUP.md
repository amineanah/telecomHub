# TelecomHub visitor feedback — setup

This branch adds `js/feedback.js`, `admin-feedback.html`, `firestore.rules`, and a visitor feedback section to `index.html`.

## Required Firebase setup before deployment

1. Open Firebase Console > project `telecomhu-1c41c` > Build > Firestore Database. Create a database if it is not enabled already.
2. Confirm that the admin Firebase Authentication UID is `wq68WfHSlzW1SNCeyIC2uoNwYvd2`.
3. Open Firestore Database > Rules and MERGE the `visitorFeedback` match block from `firestore.rules` into your existing security rules. Do not replace unrelated collection rules with this file if other collections are in use.
4. Publish the merged Firestore rules.
5. Review and merge the pull request. GitHub Pages will publish the homepage from your configured deployment branch.
6. Sign in with the admin account and open `https://www.telcomdigitalhub.com/admin-feedback.html`. Approve or reject pending submissions.
7. Test with a non-admin visitor account: send feedback, confirm it is not shown until approved, confirm the visitor cannot approve it.

## Data model
Firestore collection: `visitorFeedback`.
Submitted fields: `name`, `message`, `rating`, `status: pending`, `authorUid`, `createdAt`.
Admin updates: `status: approved/rejected`, `reviewedAt`.

Note: Creating the file `firestore.rules` on GitHub does not automatically publish its contents to Firestore. Rules must be deployed separately. Firebase config is reused from existing `js/firebase-config.js`. Do not add Firebase Admin SDK credentials or service-account keys to the public repository.
