# Compliance review

This is a technical review of the directory build. It is not a legal opinion. Items marked FLAG FOR LEGAL REVIEW need qualified counsel before launch. The initial place data is Indian states and cities. Hosting outside India does not remove Indian legal obligations where they apply.

## 1. Implemented safeguards

- Public age gate for adults 21 and over. The cookie stores only a confirmation token, not a date of birth or name.
- Listing age is constrained to 21–99. Automated listings store 21 as a minimum and display `21+`.
- Content policy, privacy notice, and terms are published and linked from the footer.
- Every listing has a report link. A separate removal form stores a takedown request.
- Verified badges stay off unless an authorized admin sets them after a real check.
- Sensitive paths and `.env` are denied by Apache rules and the front controller.

## 2. Automated safeguards

- At most five automated listings can be published per day, including a second cron run or a manual run.
- Each candidate is checked for prohibited topics, then duplicate and similarity checks, then SEO length, then database constraints.
- Failed candidates are logged by reason code and are not published.
- Variation pools are category-specific and non-explicit. Reuse of the same combination is blocked for the configured window.
- Locations and cover images rotate toward the least recently used records.

## 3. Moderation controls

- Listing statuses: draft, pending, published, suspended, deleted.
- Moderators can review reports, add internal notes, suspend, delete, or restore a listing.
- The compliance screen lists reports, pending listings, suspended listings, takedown requests, automation failures, and recent audit events.
- Filters cover date, status, and reason. Category and state are shown on each report row.

## 4. Privacy controls

Data the application can store:

- Admin name, email, password hash, role, and session
- Report and contact email addresses and messages
- IP address and user agent on admin sessions, audit logs, and rate-limit rows
- Age-confirmation cookie
- Images uploaded by an editor

Public APIs return place names and public listing fields. They do not return email addresses, password hashes, or IP addresses. The age gate does not collect a date of birth.

## 5. Security controls

- PDO prepared statements
- CSRF tokens on admin and public forms
- Output escaping in templates
- Session regeneration on sign-in, HttpOnly cookies, login throttling, and account lockout
- Role checks for publish, verify, delete, and admin creation
- Upload checks for upload status, MIME type, image size, and generated filenames
- Errors in production are logged and not shown with SQL or filesystem details
- Redirects accept only same-site paths

## 6. Known risks

- Policy pages are templates. They can be wrong for a real business, a real hosting location, or a real payment flow.
- Automated listings are fictional directory records. They must not be presented as identified real people.
- Cover images are abstract generated graphics. Editors can still upload photographs; the application does not identify the people in those photographs.
- A verified checkbox depends on staff discipline. The software cannot prove that a review happened outside the admin action.
- Search and public pages can be scraped. Rate limits cover sign-in, reports, and contact, not every public read.
- Full-text search follows MySQL stop-word rules, so some short queries return no rows.

## 7. Items requiring legal counsel

FLAG FOR LEGAL REVIEW:

- Adult-content restrictions in India and in any state or city where listings are shown
- Advertising and classified-advertising rules
- Privacy and data-protection obligations, including what a privacy notice must contain
- Intermediary or platform obligations, if they apply to this directory
- Responsibilities for user-supplied text and images
- Takedown timing, notice content, and record retention
- Whether any later payment feature creates additional licensing, tax, or consumer-law duties
- Business registration and taxation
- Local or state requirements that differ inside India
- Whether a 21+ rule, rather than another age rule, matches the legal standard that applies

Do not assume that a server located outside India removes those questions.

## 8. Items requiring business decisions

- Who may mark a listing verified, and what evidence is required
- How long reports, audit logs, and contact messages are kept
- Whether automated listings should be used in production at all
- The public brand, contact address, and grievance contact
- Whether editors may upload photographs of real people, and what consent is required

## 9. Pre-launch checks

- Replace the example `APP_KEY` and database password
- Create admin accounts with unique passwords and remove any shared test account
- Set `APP_DEBUG=false`
- Confirm Apache is denying `.env`, application directories, and script execution in uploads
- Install the cron command only after the daily cap is understood
- Have counsel review the terms, privacy notice, content policy, report flow, and takedown flow
- Decide the production canonical URL in `APP_URL` so sitemaps and canonical links are correct
