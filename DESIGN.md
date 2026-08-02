# Applyd Bootcamp — UI Design Reference

Single source of truth for UI work on this project. When building or changing any UI, use these tokens — do not introduce new colors.

## Color Scheme

| Token | Hex | Usage |
|---|---|---|
| **Primary (brand red)** | `#c73a41` | Buttons, links, focus rings, progress bars, icons, accents, selected states |
| **Primary dark** | `#9e2b31` | Hover states on primary, deep ends of red gradients |
| **Primary bright** | `#e2545b` | Accent pops on dark backgrounds (hero CTAs, marquee separators, glows) |
| **Charcoal (ink)** | `#272827` | Body text, headings, dark surfaces (hero, marquee, table headers, footer, admin nav) |
| **Ink soft** | `#5f605f` | Secondary text, labels, muted copy |
| **Background** | `#ffffff` | Page background |
| **Background soft** | `#f7f6f5` | Alternate sections, subtle fills |
| **Red tint** | `#faeaeb` | Light red fills: tags, checkmark chips, hover fills |

CSS variables live in `public/css/app.css` under `:root`:

```css
--brand: #c73a41;
--brand-dark: #9e2b31;
--accent: #e2545b;
--ink: #272827;
--ink-soft: #5f605f;
--bg: #ffffff;
--bg-soft: #f7f6f5;
```

## Rules

1. **Two-color identity.** The UI is red (`#c73a41`) on charcoal/white. Every decorative element (gradients, glows, blobs, underline accents, hover shadows) must be built from the reds and charcoal above — never indigo, purple, amber, or blue.
2. **Gradients** go red → dark red (`#c73a41` → `#7e2226`) or charcoal → near-black (`#161716` → `#272827`). No multi-hue gradients.
3. **Dark surfaces** (hero, final CTA, marquee, footer) use charcoal as the base with red glows/accents, white text.
4. **Light surfaces** use white/soft background, charcoal text, red only for emphasis and interaction.
5. **Semantic colors are exempt** — keep them functional, not decorative:
   - Success: `#059669` (badges, confirmations)
   - Danger/validation errors: `#dc2626` (slightly distinct from brand red on purpose — errors must not look like branding)
6. **Neutrals** for borders/dividers: `#e2e8f0` (light) — fine to keep.
7. **Hover/focus** always signal with the brand red (border, ring, or shadow tinted `rgba(199, 58, 65, .15–.45)`).

## Where things are

- Global stylesheet (the one actually served): `public/css/app.css`
- Landing page: `resources/views/landing.blade.php`
- Layout (CDN links, script stack): `resources/views/layouts/app.blade.php`
- Images: `public/img/`
- Logo: `public/img/logo.png` (red arch mark + charcoal wordmark, transparent bg). Dark wordmark — only place on white/light surfaces; on dark surfaces use `filter: brightness(0) invert(1)` (see `footer .footer-logo`).
