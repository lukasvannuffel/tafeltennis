# Beheerder login
- Username: bobvdb
- Password: test123

# Speler login
- Username: johndoe
- Password: test123!


# Installation

1. Clone the repository.
2. Start ddev (ensure ddev is installed and Docker is running): `ddev start`
3. Copy `.env.example.dev` to `.env` and adjust settings, such as `PRIMARY_SITE_URL` to your local site's URL.
4. Install dependencies: `composer install`
5. Import the database:
    - Copy `_db_snapshots` contents to `.ddev/db_snapshots`.
    - Run `ddev snapshot restore craft-adventure_20241212165655` in the terminal.
6. Set up a tunnel for Mollie webhooks using ngrok:
    - Run `ngrok http ...`
    - Update `PRIMARY_SITE_URL` in `.env` to the ngrok URL.