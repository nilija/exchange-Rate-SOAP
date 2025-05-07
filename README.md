
---
This version uses the official **SOAP web service** provided by the National Bank of Serbia.  
Designed for backend systems, data automation, and deeper integrations.
### 🧼 `README-soap.md` (za SOAP verziju)

```markdown
# 💱 NBS Exchange Rate Updater – SOAP API Version

This PHP script connects to the official **SOAP web service** of the National Bank of Serbia (NBS), retrieves daily exchange rates, and updates the **selling rate** for specified currencies in a MySQL table.

## 🔧 How It Works

- Uses the NBS WSDL API to get exchange rates for the current date.
- Extracts the `SrednjiZaDan` (middle rate) and/or `Prodajni` (selling rate).
- Updates your database table accordingly.
- Designed for automation via cron jobs.

## 🧰 Requirements

- PHP with `SoapClient` and `mysqli` enabled
- MySQL database
- Internet access to connect to `https://www.nbs.rs/kursnaListaModul?wsdl`

## ⚙️ Configuration

In the PHP script, set:
- MySQL connection parameters
- List of currency codes to update
- Table and column names

## 🕒 Cron Setup

Example daily cron job (run at 08:15):

```bash
15 8 * * * /usr/bin/php /path/to/nbs_soap_update.php
