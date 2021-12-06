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
    percentageOfDispatch: true
```

## Author

* E-Mail: mail@patriceckhart.com
* URL: http://www.patriceckhart.com 
