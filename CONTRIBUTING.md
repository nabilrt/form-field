# Contributing to Form & Field

Thanks for helping build an open-source store anyone can run. Bug fixes, features, docs, translations and design improvements are all welcome.

## Ground rules

- **Keep it framework-free.** Plain PHP 8.2+, MySQL 8+, one stylesheet, one script. No Composer packages, npm build step or frontend framework. If a change seems to need one, open an issue first.
- **Progressive enhancement.** Every page and form must work without JavaScript; scripts only make it smoother.
- **Security first.** Use `q()` with bound parameters for every query, `e()` for every value printed into HTML, and `csrf()` in every POST form. Never trust request values.
- **Money is integers.** Amounts are whole numbers in the currency's minor unit (taka, cents). Use `money()`, `convert()` and `localize_variants()` from `app/currency.php` rather than formatting prices by hand.
- **Small pull requests.** One focused change per PR is much easier to review.

## Set up

```sh
git clone https://github.com/nabilrt/form-field.git
cd form-field
cp .env.example .env        # add your MySQL details
php scripts/install.php     # creates tables, demo catalog, and prints an admin password
php -S 127.0.0.1:8080 -t public router.php
```

Storefront: http://127.0.0.1:8080 · Admin: http://127.0.0.1:8080/admin

## Run the checks

```sh
python3 tests/integration.py     # ~200 end-to-end checks on a temporary SQLite copy; no MySQL needed
php tests/mysql-smoke.php        # optional: smoke test against your configured MySQL
for f in app/*.php views/*.php views/admin/*.php scripts/*.php; do php -l "$f"; done
node --check public/assets/app.js
```

The integration suite runs on every pull request. Add a check to `tests/integration.py` for any new behaviour, especially anything touching money, stock, auth or permissions.

## Code style

- PHP follows `pint.json` (Laravel Pint preset); JS/CSS follow `.prettierrc.json`; indentation follows `.editorconfig`.
- Reuse what is already there: helpers in `app/bootstrap.php`, `app/currency.php` and `app/shopping-tools.php`, CSS tokens in `public/assets/style.css`, the `DESIGN.md` design system.
- Database changes: update `database/schema.sql` **and** add an idempotent `scripts/migrate-*.php` so existing stores can upgrade. Mention it in the README.

## Submitting a pull request

1. Fork the repo and create a branch: `git checkout -b fix/cart-rounding`.
2. Make your change with a test, and run the checks above.
3. Open a PR describing what changed, why, and how you tested it. Screenshots help for UI changes.

## Reporting bugs and ideas

Open an issue using the bug or feature template. For **security vulnerabilities, do not open a public issue**; see [SECURITY.md](SECURITY.md).

## Code of conduct

Be kind and constructive. This project follows the [Contributor Covenant 2.1](https://www.contributor-covenant.org/version/2/1/code_of_conduct/). Harassment or abuse of any kind isn't tolerated; report it privately to the maintainers through GitHub.

By contributing, you agree your contributions are licensed under the [MIT License](LICENSE).
