# Klaro CMP (WordPress plugin)

WordPress integration for [Klaro](https://klaro.kiprotect.com/), a lightweight open-source consent manager. The plugin loads Klaro on the front end and exposes a **Settings → Klaro** screen to configure behavior, copy, and services without editing JavaScript by hand.

Bundled Klaro version: **0.7.22** (see `KLARO_CMP_VERSION` in `klaro.php`).

## Requirements

- WordPress **5.8+**
- PHP **7.4+**

## Installation

1. Copy the `klaro` folder into `wp-content/plugins/`.
2. Activate **Klaro CMP** in the WordPress admin.
3. Go to **Settings → Klaro**, enable **Load Klaro on the front end**, adjust options, and save.

A **Settings** link is also available on the Plugins list.

## What this plugin does

- Enqueues Klaro after an inline `window.klaroConfig = { ... }` so configuration is always defined before the library runs.
- Loads the script **synchronously** (no `defer`) so Klaro’s bootstrap can reliably detect the script tag (avoids issues with `document.currentScript` and some minify/combine plugins).
- Adds `data-klaro-config="klaroConfig"` on the script tag for an explicit global name.
- Merges translation overrides into **`zz`**, **`en`**, and your configured language code so custom notice text overrides bundled English (not only the `zz` fallback).
- Ships vendor builds under `assets/vendor/` (`klaro.js`, `klaro-no-css.js`, `klaro.min.css`) or optional **CDN** loading with a pinned version.

## Settings overview

| Area | Purpose |
|------|---------|
| **General** | Enable/disable, bundled vs CDN, hide CMP for administrators (useful while testing). |
| **Behavior** | Klaro flags: must consent, notice as modal, group by purpose, HTML in texts, **optional services on by default (opt-out)**, etc. |
| **Storage** | Cookie vs `localStorage`, name, expiry. |
| **Consent notice text** | Title, description, Learn more, Reject, Accept — merged into translations for active locales. |
| **Content** | Privacy policy URL, UI theme tokens, services JSON, extra translations JSON. |

Empty notice fields fall back to sensible plugin defaults. **Extra translations JSON** wins over the same keys as the form fields.

### UI theme (position)

Comma-separated Klaro theme tokens, e.g. `light,bottom,right` for a bottom-right notice. `top` and `bottom` conflict; `wide` conflicts with `right`.

## Services JSON

Third-party tools must be listed in the **Services (JSON array)** field. Each service needs a `name` that matches `data-name` on gated markup.

Use the **(?)** help panels under the JSON textareas for examples and field reference.

## Gating scripts (required for real blocking)

Klaro only controls tags you mark up correctly. A normal GA/gtag snippet runs immediately; it is **not** blocked by consent.

Use Klaro’s pattern, for example:

```html
<script
  type="text/plain"
  data-type="text/javascript"
  data-name="google-analytics"
  data-src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX">
</script>
<script
  type="text/plain"
  data-type="text/javascript"
  data-name="google-analytics">
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-XXXXXXXXXX');
</script>
```

- Remove duplicate loaders (theme, other plugins, Tag Manager) or gate the **container** snippet the same way.
- `data-name` must match the service `name` in your Services JSON.

### Opt-in vs opt-out (per service)

- **Default (opt-in):** scripts run only after the user accepts (for that service).
- **Opt-out:** set `"optOut": true` on the service so it can run **by default** until the user rejects or turns it off — only if that matches your legal setup (often stricter in the EU than opt-in).

Klaro’s own docs generally recommend `optOut: false` for privacy-sensitive services.

## Developer hooks

| Hook | Description |
|------|-------------|
| `klaro_cmp_should_load` | Pass `false` as the first filtered value to skip loading Klaro on a request. Second argument: merged settings array. |
| `klaro_cmp_default_services_if_empty` | Replace the fallback services list when the Services JSON decodes to an empty array. |

## Updating the bundled Klaro build

Rebuild or download Klaro **0.7.22+** from the [Klaro repository](https://github.com/kiprotect/klaro) `dist/` folder and copy into `assets/vendor/`:

- `klaro.js` (full, includes CSS)
- `klaro-no-css.js` and `klaro.min.css` (if you use the no-CSS mode)

Bump `KLARO_CMP_VERSION` and the plugin header version in `klaro.php` to match.

## Uninstall

Deleting the plugin from WordPress runs `uninstall.php`, which removes the `klaro_cmp_settings` option.

## Troubleshooting

- **Banner or scripts missing:** Ensure the theme calls `wp_head()`. Clear caches. If you use a combine/minify plugin, exclude Klaro or verify the script URL still contains `klaro` if you rely on detection (this plugin avoids `defer` to reduce breakage).
- **Custom copy not showing:** Overrides are applied to `en` as well as `zz` so they beat bundled English.
- **Still seeing analytics before consent:** An untagged snippet or a second plugin is still loading the tag — find and remove or gate it.

## License

- **Klaro** is [BSD-3-Clause](https://github.com/kiprotect/klaro/blob/main/LICENSE).
- This WordPress wrapper follows the same license unless you specify otherwise in your repository.

## References

- [Klaro documentation](https://klaro.kiprotect.com/)
- [Klaro on GitHub](https://github.com/kiprotect/klaro)
