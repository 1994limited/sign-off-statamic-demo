# Saltmarsh Energy: a Sign-off demo

A small, made-up energy co-operative built with [Sign-off for Statamic](https://github.com/1994limited/sign-off-statamic), to try the addon and to copy from. Every screenshot in Sign-off's docs and Marketplace listing comes from here.

An energy supplier has to get its prices and green claims right, so every change goes through sign-off before it's live:

| Collection | Steps |
| --- | --- |
| Pages | Legal → Publisher |
| Tariffs | Pricing (a user group) → Legal → Publisher |
| News | Editor |

`php artisan demo:setup` makes five people and a week of work in progress, so there's something for each of them to do:

- **A price cut** on Fixed Green 12, approved by Pricing and waiting for Legal. The site still shows the old price.
- **A new tariff**, Night Saver, approved by Pricing and Legal, waiting for the Publisher.
- **A price change** on Flexible Tide, waiting for Pricing.
- **A new page** about the turbines, waiting for Legal. It doesn't exist on the site yet.
- **A page sent back** by Legal, with the reason, which the author has fixed but not submitted again. Compare it with what was submitted before.
- **A news story** signed off last week.
- **An urgent fix** to a deadline, published by the admin without sign-off, and recorded.

Every place, person, price and claim is invented.

## Running it

You need PHP 8.3+, Composer, and a local web server such as [Herd](https://herd.laravel.com) or `php artisan serve`.

```bash
git clone https://github.com/1994limited/sign-off-statamic-demo.git
cd sign-off-statamic-demo
composer install
cp .env.example .env
php artisan key:generate
php artisan demo:setup
php artisan serve
```

Then open http://localhost:8000 for the site, and http://localhost:8000/cp for the Control Panel.

Everyone's password is `password`:

| Account | Who | Try |
| --- | --- | --- |
| `alex@example.com` | Alex Morgan, who writes the website | The dashboard: what was sent back, and what's in review. Submit "Switching to us" again. |
| `priya@example.com` | Priya Shah, in the Pricing team | Approve the Flexible Tide price change. |
| `lee@example.com` | Lee Okafor, in Legal | Compare the Fixed Green 12 prices, then approve them or send them back. |
| `pat@example.com` | Pat Kowalski, a publisher | Approve and publish Night Saver, and watch it appear on the site. |
| `admin@example.com` | A super user | Content → Sign-off → History. Change the steps at Tools → Addons → Sign-off → Settings. Run `php please sign-off:check`. |

`demo:setup` only runs with `APP_ENV=local`. Don't put this demo on a public server as it is: its passwords are in this README.

Sign-off is a commercial addon. Running it locally like this needs no licence. It needs Statamic Pro, which the demo turns on (`STATAMIC_PRO_ENABLED=true`), and which runs in Statamic's trial mode locally.

## Emails

The demo sends Sign-off's emails to the log: open `storage/logs/laravel.log` to read them. To see them properly, point `MAIL_MAILER` at a local mail catcher such as [Herd's](https://herd.laravel.com/docs/macos/herd-pro-services/mail) or [Mailpit](https://mailpit.axllent.org).

## Starting again

`demo:setup` changes the content, so to start again, put it back and clear the history:

```bash
git checkout content && git clean -fdq content
rm -rf storage/sign-off storage/statamic/revisions
php please stache:clear
php artisan demo:setup
```

## Trying the database driver

Submissions are kept in flat files (`storage/sign-off`) by default. To keep them in the database, set `SIGN_OFF_DRIVER=database` in `.env`, run `php artisan migrate`, then start again as above.

## Where to look

| File | What it shows |
| --- | --- |
| `config/sign-off.php` | The steps for each collection, with roles, a user group and "publishers" as approvers |
| Tools → Addons → Sign-off → Settings | The same steps, editable in the Control Panel. Saving them writes `resources/addons/sign-off-statamic.yaml`, which is then used instead of the config file |
| `resources/users/roles.yaml`, `groups.yaml` | The roles: authors can edit but not publish; Legal and Pricing can only view |
| `config/statamic/cp.php` | The `sign_off` dashboard widget |
| `content/collections/*.yaml` | Revisions turned on for each collection |
| `app/Console/Commands/SetUpDemo.php` | Submitting, approving and sending back from PHP, with Sign-off's API |
| `resources/views/` | The site. Nothing in it is about Sign-off: it shows what's live |
| `public/css/saltmarsh.css` | The look |

## Screenshots

`scripts/screenshots.mjs` signs in as each person and captures the pages used in Sign-off's docs at 2×. Start from a fresh demo, after `demo:setup`:

```bash
cd scripts
npm install
DEMO_URL=http://localhost:8000 npm run screenshots
```

They're written to `scripts/screenshots/`. It drives Google Chrome on macOS by default; set `CHROME` to the path of another Chrome or Chromium.

## Licence

The demo's own code (templates, styles, content and scripts) is MIT licensed: copy whatever helps. Sign-off itself is commercial software: see [its licence](https://github.com/1994limited/sign-off-statamic/blob/main/LICENSE.md).
