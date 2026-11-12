# Consistent editorial card titles

The archive title fitter mistook grid-stretched title boxes for text overflow,
shrinking some short titles from 15.68px to 11.2px. Remove that fitter and use
component-scoped CSS at 1.25rem (20px at the standard root size), line-height
1.35 and a two-line minimum. Longer titles remain fully visible. Flex content
and automatic footer margins keep date/read-more aligned within each row.

Baseline commit: `fdeaeff`. Tested implementation: `c5e2179`.

## Validation

- Playwright substitutes local assets on read-only live-page visits.
- Archive at 1440, 1024, 768, 390 and 320px: all 14 posts, identical title sizes,
  no inline font size, no clipping/overflow, aligned metadata footers.
- Detail-page recommendations and doubled root font size pass.
- Existing PHP editorial smoke test fails on the hero/zirkel-background
  assertion in both baseline and updated versions; unrelated to these assets.
- PowerShell package preparation and Bash syntax checked locally.

Run the browser check with a Python environment containing Playwright/Chromium:

```powershell
python wp-content/plugins/avf-post-templates/tests/card-title-browser.py
```

## Production deployment

From the local `app/public` directory:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\docs\card-title-update\Deploy-CardTitles.ps1 -Deploy
```

The wrapper packages only the two committed assets and their baseline, uploads
via the existing `avfroburger-hostpoint` SSH alias, verifies checksums and runs
the installer. It refuses unexpected production changes. The production root
is `/home/avfrobur/www/gamma.avfroburger.ch`, verified against the WordPress home
URL. It briefly enables maintenance mode, backs up both assets, installs them,
flushes the object cache and verifies the archive and served asset contents.
Existing filemtime asset versions change automatically. No content/database
migration or Elementor regeneration is needed. Failure restores the old files.

For separate upload and SSH launch, use `-Upload` instead of `-Deploy`; it prints
the exact SSH command including release path and checksums. Without switches,
the wrapper only prepares a local package.

Backups: `/home/avfrobur/backups/avf-card-titles-YYYYMMDD-HHMMSS/code-before.tar.gz`.
For manual rollback, restore that archive from the production root, touch the
two restored assets to invalidate browser cache versions and run `wp cache flush`.

After launch, verify `/aktuelles/` with a hard refresh, expand all posts and open
a detail page. Titles should remain equal in size during resizing.
