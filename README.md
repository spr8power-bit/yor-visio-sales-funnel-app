# YOR Vision Sales Funnel App

YOR Vision Sales Funnel App is a shared-hosting-friendly Laravel project for the YOR Vision Mineral Drops product funnel.

This repository currently contains:

- the Laravel app in `vibe-app/`
- the local preview page in `vibe-app/preview.html`
- product, lifestyle, ingredient, gallery, and testimonial assets in `vibe-app/public/images/yor-vision/`
- editable JSON content for FAQs and testimonials in `vibe-app/public/data/`

## Project Structure

```text
Project YOR Vision Product/
|- README.md
|- .gitignore
`- vibe-app/
   |- app/
   |- public/
   |  |- data/
   |  `- images/
   |- resources/
   |- routes/
   |- preview.html
   `- composer.json
```

## Main Routes

- `GET /` -> homepage
- `POST /generate` -> placeholder form handler

These routes are defined in `vibe-app/routes/web.php`.

## Local Preview Options

### Option 1: Existing localhost preview

If your local preview server is already running, open:

- `http://localhost:8000/`

### Option 2: Laravel local serve

From the app folder:

```powershell
cd "C:\Users\Admin\Documents\Project YOR Vision Product\vibe-app"
composer install
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8001
```

Then open:

- `http://127.0.0.1:8001/`

## Editing Content

### Homepage layout

- `vibe-app/resources/views/home.blade.php`
- `vibe-app/preview.html`

### FAQ content

- `vibe-app/public/data/faqs.json`

### Testimonial content

- `vibe-app/public/data/testimonials.json`

### Product and funnel assets

- `vibe-app/public/images/yor-vision/`

## Shared Hosting Notes

This project was prepared with a shared-hosting-friendly structure in mind:

- Laravel app code stays inside `vibe-app/`
- static assets are served from `vibe-app/public/`
- CDN-based styling is used where needed for the preview funnel workflow

## GitHub Status

This repository is connected to:

- `https://github.com/spr8power-bit/yor-visio-sales-funnel-app.git`

