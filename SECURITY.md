# Security policy

## Reporting a vulnerability

Please **don't open a public issue** for security problems. Report them privately through GitHub's [private vulnerability reporting](https://github.com/nabilrt/form-field/security/advisories/new) (Security tab → "Report a vulnerability").

Include the affected page or file, steps to reproduce, and the impact. You'll get an acknowledgement as soon as a maintainer sees it, and credit in the fix if you'd like.

## Supported versions

Security fixes go into the `main` branch. Store owners should update to the latest `main` and run any new `scripts/migrate-*.php` files.

## Built-in protections

Prepared statements for all queries, CSRF tokens on every form, escaped output, a strict Content-Security-Policy, re-encoded image uploads, per-account and per-IP login throttling, and idle admin sessions that expire. If you find a way around any of these, we want to hear about it.
