# Contacts Summary Print Extension

![Screenshot](images/screenshot.png)

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

- **PHP:** v7.4+
- **CiviCRM:** v5.71+

## Installation

> Checkout [Instructions](instructions.pdf) PDF file, to know more.

### Web UI

To install the extension using the web UI, follow the steps outlined in the [CiviCRM Sysadmin Guide](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/).

### CLI (Command Line Interface) / Zip

Sysadmins and developers can manually extract the `contacts-summary-print.zip` file into the CiviCRM extension directory. After extraction, use the [cv tool](https://github.com/civicrm/cv) to enable the extension:

```bash
cv en contacts_summary_print
```

Alternatively, you can install it via the `CiviCRM Extensions` page [here](/civicrm/admin/extensions).

## Getting Started

1. **Installation:** Follow the installation instructions above to install the extension.
2. **Configuration:** After installation, configure the extension as needed through the CiviCRM UI.

## Known Issues

- **DOCX Editing Limitation:** Currently, the content of `DOCX` files cannot be updated by editing the `Contacts Summary Print` custom message template, unlike PDF files.
