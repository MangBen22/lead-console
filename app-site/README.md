# 5N2 App Main Website (Phase 1 Scaffold)

This folder contains the initial control-center website scaffold for `app.5n2digital.com`.

## Included
- Login-ready session shell
- Module navigation (Leads, CRM/Email, Social/Forums, WebOps, SEO)
- Notification panel UI
- API stubs (`/api/index.php`)
- Hostinger-friendly PHP (no framework dependency)

## Run (Local PHP)
```bash
php -S 127.0.0.1:8080 -t app-site
```

Open: `http://127.0.0.1:8080`

## Defaults
- Uses lightweight session auth demo flow.
- Uses `config.example.php` values until replaced with secure environment configuration.

## Next
- Replace demo auth with production auth provider.
- Connect API stubs to database and plugin bridge endpoints.
- Add tenant/account mapping and connector records.
