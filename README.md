# ACF Automation

A small WordPress plugin for building “Automation Page” landing pages from ACF data.

## What it does
- Adds a page template called **Automation Page**.
- Exposes a REST endpoint that lets a script push content into WordPress.
- Saves pushed values into ACF fields when ACF is available (falls back to plain post meta if not).
- Renders the page fresh on every request by reading the current ACF/meta values.
- Loads only the plugin CSS/JS on template pages, and dequeues most other theme/plugin styles to avoid conflicts.
- Outputs basic SEO tags on template pages: `<title>`, meta description, meta keywords, plus LocalBusiness JSON-LD schema.
- Adds a few Gravity Forms niceties (and supports embedding GF shortcodes stored in fields).
- Optional CSP header support (off by default).

## Requirements
- WordPress (REST API enabled)
- Advanced Custom Fields
- Gravity Forms (optional)

## How the push works
Endpoint:
`POST /wp-json/automation/v1/push`

Auth:
- Must be logged in with `edit_pages` (and `publish_pages` if creating new pages).

Payload (example):

```python
import requests

url = "https://your-site.com/wp-json/automation/v1/push"
headers = {
  "Content-Type": "application/json",
  "Authorization": "Bearer YOUR_ACCESS_TOKEN"
}
data = {
  "post_id": 456,
  "title": "Sample Automation Page",
  "slug": "sample-automation",
  "status": "publish",
  "set_template": True,
  "acf": {
    "heading_home": "Your Automation Solution",
    "phone_number": "(123) 456-7890",
    "meta_description": "Professional automation services for your business."
  },
  "meta_title": "Top Automation Services"
}

response = requests.post(url, json=data, headers=headers)
print(response.status_code, response.json())
```
```

Notes:
- If `post_id` is missing, it will try to find an existing page by slug (or create one).
- `acf` is the main object for ACF field values.
- Keys are sanitized (safe meta keys) and values are recursively cleaned before saving.

## Styles and scripts
- `assets/automation.css` and `assets/automation.js` only load on pages using the Automation template.
- Most other enqueued styles get dequeued on those pages (with an allowlist + Gravity Forms styles allowed).

## CSP (optional)
Set `AR_ENABLE_CSP` to `true` (example: in `wp-config.php`) to emit a Content Security Policy header on template pages.

## Admin editor behavior
- The default WordPress editor is removed for template pages (ACF fields are the source of truth).
- A small “Sections” sidebar is added in the admin to jump between ACF tabs faster.

## Version
Current plugin version: `1.1.8`
