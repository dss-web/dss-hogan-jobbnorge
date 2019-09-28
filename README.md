# Jobbnorge Module for [Hogan](https://github.com/dekodeinteraktiv/hogan-core)

## Installation
Downloading this repository and place it in `wp-content/plugins`

## Available filters
- `dss/hogan/module/feed/title/instructions` : Change title instructions.
- `dss/hogan/module/feed/file/instructions` : Change file instructions.
- `dss/hogan/module/feed/mime_types` : Change default instructions. Default `.pdf` (comma separated string of file types)
- `dss/hogan/module/feed/preview_image_size` : Set preview image size. Default 'thumbnail'
- `dss/hogan/module/feed/file_name_max_chars` : Set file name max character length. Default 25
- `dss/hogan/module/feed/preview_image_fallback_icon_path` : Icon / image path for fallback icon / image when 'Show preview image' is on and there is not generated any preview thumbnail. Default {plugin_path}/assets/images/document.png