# GLB 3D Viewer for PrestaShop (`wbglbviewer`)

Attach a `.glb` file per product and show an interactive 3D viewer on the product page using [`<model-viewer>`](https://modelviewer.dev/).

## Features
- Upload a `.glb` per product from the product edit page
- Renders a 3D viewer under the product summary (PS 1.7/8 via `displayProductExtraContent`)
- Fallback rendering via `displayFooterProduct`
- Lightweight: no theme overrides, uses `@google/model-viewer` via CDN

## Compatibility
- PrestaShop **1.7.x – 8.x**
- PHP: follow your shop’s supported version (tested against 7.4+)

## Installation
1. Download the release ZIP (or build it yourself, see below).
2. In Back Office → **Modules** → **Module Manager** → **Upload a module**.
3. Search a product → **3D Model (.glb)** field appears in the product edit page.

> The installable ZIP must contain a top-level folder named **`wbglbviewer/`**.

## Usage
- Go to a product in Back Office → upload a `.glb`.
- On the product page, a “**3D View**” block appears.  
- Files are stored in `modules/wbglbviewer/uploads/`.

## Security & AJAX flow
This module posts AJAX to `AdminModules&configure=wbglbviewer` and implements:
- `ajaxProcessUpload()` (handles file upload & DB save)
- `ajaxProcessDelete()` (removes file & DB row)

The admin token in the URL is validated by PrestaShop’s dispatcher. Employee session is required.

## MIME & CORS (recommended)
Add an `.htaccess` in `modules/wbglbviewer/uploads/`:
