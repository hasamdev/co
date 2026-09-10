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
│   └── security.php     Head cleanup, XML-RPC off, REST users hidden, vague login errors
├── components/          Small reusable partials (button, card, nav)
├── sections/            Page-builder layouts, one file per ACF layout
├── acf-json/            Field groups as JSON — versioned, synced across environments
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

## Production notes

- ACF field editing UI is hidden when `WP_ENVIRONMENT_TYPE=production` — edit fields in dev, deploy the JSON, sync.
- `DISALLOW_FILE_EDIT` is enforced; prefer also setting it in `wp-config.php`.
- Cache-busting uses the theme version in production and `filemtime()` when `WP_DEBUG` is on.
- The theme ships zero webfonts. Add project fonts to `/assets/fonts/`, preload in `header.php`, and point `--font-display` / `--font-body` at them.

## License

GPL-2.0-or-later.
