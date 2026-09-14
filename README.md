# Cypher One

Cypher One WordPress theme, built on the Narrative House starter. ACF-powered, component-based, no build step required. Re-skin everything through design tokens in `assets/css/variables.css`.

The repository root **is** the theme root, so cloning it into `wp-content/themes/` gives you the theme folder directly. Everything in the theme is prefixed `co` — functions (`co_*`), constants (`CO_THEME_*`), asset handles (`co-main`), ACF keys (`field_co_*`) and the text domain (`co`).

## Requirements

- WordPress 6.4+
- PHP 8.1+
- Advanced Custom Fields **Pro** (free ACF works, but you lose the options page, repeaters and the flexible-content page builder — Pro is the intended baseline)

## Quick start

1. Clone into `wp-content/themes/` so the theme folder is `co`:

   ```bash
   cd wp-content/themes
   git clone https://github.com/hasamdev/co.git co
   ```

   Then activate **Cypher One** under **Appearance → Themes**.
2. Install and activate ACF Pro.
3. Go to **ACF → Field Groups → Sync available** and sync all three groups ("Page builder", "Site settings", "Front page"). Fields ship as JSON in `/acf-json/`, so this is one click.
4. Create a page — the "Sections" builder replaces the editor. Stack Hero, Rich content, Features, CTA, and Latest posts layouts.
5. Assign menus under **Appearance → Menus** (Primary and Footer locations).
6. Set the footer line and social links under **Site settings**.

## Architecture

```
co/
├── style.css            Theme header only — no styles
├── functions.php        Thin bootstrap, loads /inc/
├── inc/
│   ├── setup.php        Theme supports, menus, image sizes
│   ├── enqueue.php      Assets with filemtime cache-busting in WP_DEBUG
│   ├── acf.php          JSON sync, options page, missing-ACF notice
│   ├── helpers.php      co_field(), co_section(), co_component(), co_page_builder()
│   ├── security.php     Head cleanup, XML-RPC off, REST users hidden, vague login errors
│   ├── access.php       24-hour token store, signed session cookie, throttling
│   ├── apps.php         Gated app registry, routing, serving
│   └── admin-tokens.php Tools → App access (issue / review / revoke)
├── components/          Small reusable partials (button, card, nav)
├── sections/            Page-builder layouts, one file per ACF layout
├── acf-json/            Field groups as JSON — versioned, synced across environments
├── apps/                Self-contained gated tools, served whole behind a token
├── template-access-gate.php  Token gate shown in place of a gated app
├── assets/
│   ├── css/variables.css  Design tokens — the ONLY place brand values live
│   ├── css/main.css       Base, layout, components, sections
│   ├── css/editor.css     Block editor parity
│   └── js/main.js         Vanilla JS: nav toggle, reveal-on-scroll
└── page.php / single.php / archive.php / search.php / 404.php / index.php
```

## Conventions

**Adding a section** — two steps, no plumbing:

1. Add a layout to the "Page builder" flexible content field (the layout *name* is the contract).
2. Create `sections/{layout-name}.php`. `co_page_builder()` picks it up automatically.

**Field access** — never call `get_field()` directly in templates. Use `co_field( 'name', $default )` and `co_sub_field()`; they return defaults instead of fataling when ACF is deactivated.

**Styling** — components consume tokens (`var(--color-accent)`, `var(--space-lg)`, `var(--text-xl)`), never raw values. Re-branding a project means editing `variables.css` only. Sections on dark backgrounds get the `.is-inverse` class, which flips the semantic tokens locally.

**Escaping** — escape at output, always: `esc_html()`, `esc_url()`, `esc_attr()`, `wp_kses_post()` for WYSIWYG/rich fields.

**Prefixing** — every function, hook and global is prefixed `co_`.

## Front page (Cypher-One launch layout)

`front-page.php` renders a fixed sequence of six sections (`sections/co-*.php`): hero with brand mark and partner logo strip, "Why" trust bar, live ROI calculator, services grid, evolution timeline, and a launch-list signup on the dark band.

- **Setup:** Settings → Reading → "A static page" → pick any page as Homepage. WordPress routes it to `front-page.php` automatically.
- **Copy:** editable via the "Front page" ACF group; every field falls back to the built-in design copy, so the page is complete with zero configuration.
- **Partner logos:** the strip shows placeholder slots until you upload approved marks to the "Partner logos" gallery field (white/light versions look best — they render at 34px height).
- **Calculator:** `assets/js/roi-calculator.js`; formula is `team × hours/week × 4 × hourly cost`, savings at 80%.
- **Signup form:** posts to `admin-post.php` with nonce + honeypot; entries are stored (deduped, capped) and listed under **Tools → Launch list**, with an email notification to the admin. Swap the storage block in `inc/launch-form.php` for your ESP/CRM when chosen.
- **Assets:** `front-page.css` and `roi-calculator.js` load only on the front page. The global site header is hidden there by design; the footer gains the centered brand mark.

## Gated tools (Smart AI Readiness · Smart AI Governance)

Two self-contained assessment apps ship in `apps/` and are served behind a
24-hour token gate.

```
apps/
├── .htaccess                   Apache-level block (defence in depth)
├── smart-ai-readiness.php      Smart AI Readiness   (Assessment v1.10)
└── smart-ai-governance.php     Smart AI Governance  (QAGI build 43)
```

### How it works

Each app is a complete HTML document with its own fonts, global CSS and (for
governance) its own React runtime. They are **served byte-for-byte**, not
folded into theme templates — so updating one is just replacing the file.

A page becomes a tool by picking it in the **Gated app** box on the page edit
screen (post meta `_co_app`). From then on:

| Visitor state | What they get |
|---|---|
| No token | The access gate, opened as a modal over a holding page |
| Live token | The app, with a small "← Cypher-One" pill injected |
| Logged in as editor | The app, no token needed |

Both pages are created on first admin load, titled **Smart AI Readiness** and
**Smart AI Governance**, and added to the Primary menu. If no menu is
assigned to that location, one named "Primary" is created (with a Home link)
and assigned, so the items actually appear rather than silently going
nowhere.

### The visitor's path

The gate is a two-step modal, and **no one has to be at a desk for a prospect
to get in**:

1. **Email step.** They enter their work email (name and organisation
   optional). A 24-hour code is generated immediately, emailed to them from
   `contact@cypher-one.ai`, and a copy of the request — without the code — is
   sent to the same address so the lead is captured.
2. **Code step.** They paste the code, or just click the button in the email,
   which carries a magic link that signs them in and strips the token from
   the URL.

Which step opens is decided server-side from the `?access=` code on the
redirect, so the flow survives a reload. The dialog starts closed and
`gate.js` opens it as a true modal; a `<noscript>` stylesheet renders it
inline when scripting is off, so the gate never becomes a dead end.

Because this hands out credentials to anyone who asks, requests are budgeted
two ways:

| Limit | Why |
|---|---|
| 5 per IP per hour | One visitor cannot mint codes in bulk |
| 3 per email address per hour | The form cannot be pointed at someone else's inbox |
| 8 redemption attempts per IP per 15 min | Guessing a code is not viable |

### Issuing a token by hand

**Tools → App access** still issues one directly — useful for a workshop, or
when someone's mail is bouncing. You get the code plus a ready-to-send reply
containing the magic link. Only an HMAC is stored, so **it is shown once and
cannot be recovered**; if lost, issue another.

- **24 hours from issue**, not from first use. The session cookie expires at
  the same instant, so it can never outlive the token.
- **Revoke is immediate** — every gated request re-reads the row.
- Multi-use within the window by default. Set the use limit to `1` to make a
  token strictly single-use.

Change the contact address with:

```php
add_filter( 'co_access_contact_email', fn() => 'hello@example.com' );
```

> **Mail must actually work.** Both the code and the lead copy go out through
> `wp_mail()`. On LocalWP, mail is captured by the built-in mailbox rather
> than delivered — fine for testing. In production, put SMTP behind it and
> make sure `contact@cypher-one.ai` is authorised to send for the domain
> (SPF/DKIM), or codes will land in spam. If the send fails the visitor is
> told so explicitly rather than being left waiting for a code that is never
> coming.

### Why the app files are .php

Serving the exports as `.html` would leave them downloadable straight from
`/wp-content/themes/co/apps/`, skipping the gate entirely. Blocking that at
the web server needs different config per server — and none at all on managed
hosting — so the block lives in the file instead. Each app file is a `.php`
whose first line is:

```php
<?php http_response_code( 403 ); exit; /* CO_APP_GUARD */ ?>
```

A direct request is answered `403` with an empty body by PHP itself, on
Apache, nginx and managed hosts alike, with nothing to configure. Everything
after that line is the untouched export: `co_app_serve()` reads the file,
strips the guard and streams the rest, so the response is byte-identical to
the original document and the body is never parsed as PHP.

### Updating an app

Drop the new export in as `apps/<name>.php`, keeping the filename, then **add
the guard line back as line 1** — an export straight out of the tool will not
have it. From a shell:

```bash
cd apps
printf '%s\n' '<?php http_response_code( 403 ); exit; /* CO_APP_GUARD */ ?>' | \
  cat - new-export.html > smart-ai-readiness.php
```

If you forget, **Tools → App access** shows a red warning naming the file —
it checks each app's first bytes for the guard on every load. To register a
third tool, filter `co_apps`.

## Production notes

- ACF field editing UI is hidden when `WP_ENVIRONMENT_TYPE=production` — edit fields in dev, deploy the JSON, sync.
- `DISALLOW_FILE_EDIT` is enforced; prefer also setting it in `wp-config.php`.
- Cache-busting uses the theme version in production and `filemtime()` when `WP_DEBUG` is on.
- The theme ships zero webfonts. Add project fonts to `/assets/fonts/`, preload in `header.php`, and point `--font-display` / `--font-body` at them.

## License

GPL-2.0-or-later.
