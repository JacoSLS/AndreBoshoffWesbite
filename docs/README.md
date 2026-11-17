# Freeland Well and Water Services — website

Hand-built PHP site with a flat-file admin panel. No database, no Composer, no build
step. Upload the folder and it runs.

---

## 1. What is in here

```
/                      index.php, about.php, services.php, gallery.php,
                       contact.php, privacy-policy.php, terms.php, 404.php
/includes              header, footer, shared data layer, mailer  (never served directly)
/data                  site.json, content.json, services.json, gallery.json,
                       legal.json, auth.json      (never served directly)
/assets/css            style.css — one stylesheet, brand tokens at the top
/assets/js             main.js — nav, lightbox, gallery filter. No frameworks.
/assets/img            logo variants, favicons, banner, og-image.jpg (sharing picture)
/assets/uploads        where Andre's uploaded photos land   ← must be writable
/admin                 the staff login and admin panel
.htaccess              clean URLs, security headers, protects /data and /includes
robots.txt             currently blocks all search engines — see section 6
sitemap.xml            update the domain if it ever changes
```

Content lives in `/data/*.json`. The pages read it, the admin panel writes it. That is
the whole architecture. To move the site to another host, copy the folder.

`content.json` holds every heading, paragraph and section switch on every page.
`legal.json` holds the two legal documents. Nothing user-facing is hardcoded in the
PHP any more — if it appears on the site, there is an admin page that edits it.

The editor pages are declarative: each one lists its fields in
`admin/page-*.php` and `admin/includes/editor.php` does the rendering and saving.
Adding a new editable field is one line in that list.

---

## 2. Server requirements

| Requirement | Why |
|---|---|
| PHP 8.1 or newer | Uses `match`, arrow functions, named args |
| GD extension | Resizes uploaded photos. Without it, uploads fail with a clear message |
| `exif` extension (optional) | Rotates phone photos the right way up |
| Write access to `/data` and `/assets/uploads` | Admin panel saves here |
| Free SSL | The admin has a login form — never serve it over plain http |

Works as-is on Afrihost, Namecheap, DreamHost and any other cPanel host. On Afrihost,
set the PHP version per domain in cPanel → **MultiPHP Manager** (the default is
sometimes older than 8.1).

---

## 3. Installing it

1. Upload everything to the root of `public_html`. The site only works at the root
   of a domain, and `.htaccess` sends every visitor to www.freelandwellandwater.com,
   so it cannot run as a subfolder or demo copy without editing those rules.
2. Set permissions: folders `755`, files `644`. Confirm `/data` and `/assets/uploads`
   are writable — most cPanel hosts are fine at 755, a few need 775.
3. There is no built-in password. On a fresh install, create an empty file
   `data/setup-allowed` in File Manager, then open `/admin/`: it asks you to choose the
   username and password, saves them (hashed) in `data/auth.json` and deletes
   `setup-allowed`. Without that file nobody can set up a login.
4. Change the password any time under *Contact & Settings → Your login*.
5. Under *Contact & Settings*, set the **Website address** field to the real domain.

### Check this before handing over

Open `https://yourdomain/data/auth.json` in a browser. You must get **403 Forbidden**
or a 404 — that file holds the password hash. If it downloads, `.htaccess` is not
being read. On nginx hosts, add this to the server block instead:

```nginx
location ~ ^/(data|includes)/ { deny all; return 403; }
location ~ /\.ht { deny all; }
location /assets/uploads/ { location ~ \.php$ { return 403; } }
```

---

## 4. The contact form

Out of the box it uses PHP's `mail()`, sending from andre@freelandwellandwater.com (a
real mailbox on the site's own domain), which works on Namecheap. The settings are in
`/includes/mail-config.php`.

**To switch to SMTP**, never put the password in `mail-config.php`. Instead create
`/includes/mail-local.php` on the server. It is not in the website zip, so updates
never overwrite it, and it replaces any matching setting in `mail-config.php`:

```php
<?php return ['smtp_enabled' => true, 'password' => 'the mailbox password'];
```

The site refuses to send an SMTP login over an unencrypted connection (`secure` must
be `ssl` or `tls`). If a message cannot be sent, the visitor is asked to phone instead
and the reason is written to the server's error log.

Two rules that decide whether enquiries arrive:

- **From** must be a real mailbox on the domain the site is hosted under.
- The visitor's address goes in **Reply-To**, never in From. The code already does
  this — do not "helpfully" change it.

Set `'bcc'` if you want a copy of every enquiry.

The address enquiries are delivered to is set in the admin panel, not here.

---

## 5. What Andre can change himself

Signed into `/admin/`:

There is one editor per part of the site:

- **Home** — every heading, paragraph, tick-list, photo and number on the home page,
  with a show/hide switch on each section.
- **About Us** — the hero, your story, the "how we work" cards and the "who we help"
  block, both photos included.
- **Services** — add, remove and reorder services, each with an icon, an optional
  photo, a short and long description and bullet points. Plus all the page wording.
- **Gallery** — jobs, each holding several photos. Add or remove photos, choose the
  cover, reorder jobs, write the full story.
- **Contact** — page wording, every form label, and a switch to turn the form off.
- **Header & Footer** — both logos, the top bar, menu labels and visibility, the green
  call band, and every footer column.
- **Legal** — the privacy policy and terms pages, section by section.
- **Contact & Settings** — phone, email, where enquiries go, service areas, hours,
  analytics IDs and the hide-from-Google switch.

**Removing a service removes it everywhere** — home page cards, the services page,
the footer menu, the gallery filters and the contact form dropdown. Gallery jobs filed
under it are moved to General work rather than deleted.

### How the gallery works

A gallery entry is a **job**, not a photo. One job holds any number of photos. On the
public page the card shows those photos in turn, fading between them on a stagger so
the grid does not flip in unison, with a photo count and dots in the corner. Clicking
a job opens the full view: every photo with previous/next and thumbnails, plus the
longer write-up.

The site ships with Andre's real job photos loaded as six jobs. If the gallery is ever
emptied, the public page shows a short "new job photos are on their way" note with a
link to the contact page.

Each service also has a **short label** (e.g. "Well Pumps") used on the gallery filter
buttons and photo badges, because the full service name does not fit in a pill. It is
editable alongside the service text.

---

## 6. Going live — do these in order

1. **Set the Website address** in *Contact & Settings* to `https://www.freelandwellandwater.com`.
2. **Update `sitemap.xml`** if the domain differs from the one already in it.
3. **HTTPS + www redirect** in `.htaccess` is already on for
   `https://www.freelandwellandwater.com` (`/.well-known/` is excluded for AutoSSL).
4. **Switch the contact form to SMTP** (section 4) and send a real test enquiry.
5. Add the GA4 ID, Search Console verification code and Meta Pixel ID in
   *Contact & Settings*, then submit the sitemap in Search Console.
6. Only now cancel the Wix plan. Keep the domain registration alive.

---

## 7. Demo notes

The site is always open to search engines; there is no "hide from Google" switch.
A demo copy on another address should therefore be protected another way (for
example a password on the folder in cPanel), so it never competes with the real site.

If the demo is hosted in South Africa and shown to someone in Maryland, expect it to
feel slower than the live site will. Worth saying up front so it is not mistaken for
the site being slow.

---

## 8. Things worth knowing

- **HEIC photos.** iPhones default to HEIC, which GD cannot read. The upload form
  gives a plain-English error telling the user to set Camera → Formats to "Most
  Compatible". If this becomes a nuisance, Imagick with HEIC support is the fix.
- **No undo.** Deleting a gallery photo removes the files from disk. There is no bin.
- **Backups.** Zip `/data` and `/assets/uploads` — that is the entire site's content.
  Everything else is code and can be re-uploaded.
- **Andre does not drill wells.** He pulls, sets and replaces pumps. Keep drilling,
  siting and "new well" language off the site; the first service is deliberately
  named *Well Pump Installation and Replacement*, matching his truck lettering.
- **Brand tokens** are the CSS variables at the top of `style.css`. Change the two
  brand colours in one place and the whole site follows.
- **Clean URLs** come from `.htaccess` and need Apache's mod_rewrite (with
  `AllowOverride All`). Links and the contact form use the clean addresses, so the
  site does not work properly without it.
- **The legal pages are drafts.** They are written to match how the site actually
  behaves, but they say plainly at the top that they need an attorney's eye. Do not
  let them go live unreviewed if the client starts taking payments.
