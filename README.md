# Turn Neos CMS into a newsletter tool

A plugin to turn Neos CMS into a newsletter tool.

This packages adds a new backend module to import and manage recipients and recipient lists, create mailings and send them to the recipients. It also adds a tracking pixel to track whether the recipient opened the email and whether they clicked on links in the email.
In the Neos content module you can create new `Newsletter` document nodes, which can be used as content for the mailings.

## Installation

The NeosRulez.DirectMail package is listed on packagist (https://packagist.org/packages/neosrulez/directmail) - therefore you don't have to include the package in your "repositories" entry any more.

Just run:

```
composer require neosrulez/directmail
```

## Usage

```
NeosRulez:
  DirectMail:
    useAjax: false
    senderMail: 'postmaster@foo.bar'
    senderName: 'Postmaster'
    startDispatchFromNeosBackend: false # Enables a button in the Neos backend to start the dispatch of newsletters.
    baseUri: 'http://foo.bar' # Base URI must be set, if your Newsletter will be sent from CLI, e.g. via a cronjob.
    apiKey: '12345678' # Use a safe key with random letters and numbers in sufficient length
    percentageOfDispatch: true
    language: true

    # Optional settings:
    rtrEcgList:
      apiKey: 'bc3e81d8-5673-444d-a373-67954417786a' # When creating mailings, you can check whether the recipients are on the ecg list. These recipients will not be added.
    recipient:
      customFields:
        company:
          type: string
          label: Company
```

### Migration from version < 2.0.0

Since Neos now uses symfony/mailer instead of swiftmailer, the mailer service has been refactored. Therefore, you have to install either `neos/symfonymailer` or `neos/swiftmailer` manually, if not already done.

Version `2.0.0` adds new roles:

- `NeosRulez.DirectMail:RecipientListManager`:
  Allows to manage recipients and recipient lists.
- `NeosRulez.DirectMail:QueueManager`
  Allows to manage mailings and the mailing queue, and to send mailings.
- `NeosRulez.DirectMail:TrackingManager`
  Allows to view the tracking data of mailings.
- `NeosRulez.DirectMail:ImportManager`
  Allows to import recipients and recipient lists.
- `NeosRulez.DirectMail:Administrator`
  Allows full access to the DirectMail module.

If you upgrade from a version `< 2.0.0` you have to assign the new roles to your users, otherwise they won't be able to use the module anymore.

## Author

* E-Mail: mail@patriceckhart.com
* URL: http://www.patriceckhart.com 
