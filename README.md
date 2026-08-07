# WP Sheet Logger

A lightweight WordPress MU-plugin that logs updates for core, plugins, and themes directly to a Google Sheet via Google Apps Script webhooks (`upgrader_process_complete`).

## Installation

1. Copy the `wp-sheet-logger-loader.php` file and the `wp-sheet-logger/` directory to your WordPress installation's `wp-content/mu-plugins/` folder.
2. Edit `wp-sheet-logger/wp-sheet-logger.php` and update the `WP_SHEET_LOGGER_DEPLOYMENT_ID` constant with your Google Apps Script Deployment ID.

## Configuration

* **Deployment ID:** Paste your unique Google Apps Script web app ID into the `WP_SHEET_LOGGER_DEPLOYMENT_ID` constant in the plugin file.
* **Google Sheet Tab:** Ensure that the specific tab in your Google Sheet is named exactly as the domain of your site (e.g., `example.com`). The plugin automatically retrieves the site domain and sends it to the script.

## Setup

1. Create a new Google Sheet.
2. Open **Extensions > Apps Script**.
3. Replace the content with the provided code in `google-apps-script/code.js`.
4. Deploy the script as a **Web App** (execute as "Me", access "Anyone").
5. Copy the Deployment ID and update the plugin configuration.