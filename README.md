# Turn Neos CMS into a newsletter tool

A plugin to turn Neos CMS into a newsletter tool.

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
    startDispatchFromNeosBackend: false
    baseUri: 'http://foo.bar'
    apiKey: '12345678' # Use a safe key with random letters and numbers in sufficient length
    percentageOfDispatch: true
    language: true
    rtrEcgList:
      apiKey: 'bc3e81d8-5673-444d-a373-67954417786a' # When creating mailings, you can check whether the recipients are on the ecg list. These recipients will not be added.
    recipient:
      customFields:
        company:
          type: string
          label: Company
```

## Author

* E-Mail: mail@patriceckhart.com
* URL: http://www.patriceckhart.com 
