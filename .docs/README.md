# TomasKulhanek / Fakturoid-nette

## Usage

### Extension registration

config.neon:

```neon
extensions:
	fakturoid: Fakturoid\Nette\DI\FakturoidExtension
```

### Example configuration

```neon
services:
    httpClient: #any PSR-18 client
fakturoid:
    clientId: '{fakturoid-client-id}'
    clientSecret: '{fakturoid-client-secret}'
    userAgent: 'PHPlib <your@email.cz>'
    accountSlug: '{fakturoid-account-slug}' #optional
    redirectUri: '{your-redirect-uri}' #required for AuthTypeEnum::AUTHORIZATION_CODE_FLOW
    providers: # you can specify which provider do you want to register, default is all providers
        - event
        - expense
        - generator
        - inboxFile
        - inventoryItem
        - invoice
        - setting
        - subject
        - todo
```