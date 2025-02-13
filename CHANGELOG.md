Changelog
=========

# 5.0.5 2025-02-13
## Added
- Adding support for explicit logout for SPs

# 5.0.4 2025-01-07
## Fixed
- revert: filter role decriptors only selecting saml

# 5.0.3 2025-01-27
## Fixed
- filter role decriptors only selecting saml

# 5.0.2 2024-12-03 [CRITICAL]
## Fixed
- SECURITY PATCH - UPDATE REQUIRED! For more information see: https://github.com/simplesamlphp/saml2/security/advisories/GHSA-pxm4-r5ph-q2m2#event-375127

# 5.0.1 2024-04-17
## Fixed
- Issues with cp crumbs

# 5.0.0 2024-04-17
## Fixed
- Craft 5.0 compatibility

# 4.1.0 2024-02-10
## Fixed
- Fixing issue with multi-site linking for the external id field

# 4.0.7 2023-12-19
## Fixed
- reverting changes for shift key descriptor. Seeing issues with other php versions.

# 4.0.5 2023-11-29
## Fixed
- pinning psr/log at ^1.1.4 to avoid unwanted upgrades like https://github.com/flipboxfactory/saml-sp/issues/197

# 4.0.4.1 2023-11-28
## Fixed
- reverting 4.0.4

# 4.0.4 2023-11-28
## Fixed
- fix: issue with logger compatibility

# 4.0.3 2022-12-15
## Fixed
- UI fixes found in https://github.com/flipboxfactory/saml-sp/issues/182

# 4.0.2 2022-12-06
## Fixed
- issue with logout event not sending data along. Thank you @braican!

# 4.0.1 2022-08-11
## Fixed
- issues with 4.0 typing matching craft parent classes

# 4.0.0 2022-06-13
## Fixed
- updates for Craft CMS 4.0.0

# 3.4.1 2021-10-27
## Fixed
- When "This site has it's own base URL" isn't checked bu the site is selected. ref: https://github.com/flipboxfactory/saml-sp/issues/139

# 3.4.0 2021-09-13
## Removed
- Removed `src/validators/Assertion.php`. Moved to the saml-sp package.
- Removed `src/validators/Response.php`. Moved to the saml-sp package.
- Removed `src/validators/SignedElement.php`. Moved to the saml-sp package.

# 3.3.0 2021-09-09
## Fixed
- Added controls to force signature verification.

# 3.2.8 2021-05-14
## Fixed
- Issue with clipboard (using navigator.clipboard with a fallback of the previous method) #113
- Disallow viewing to settings when allowAdminChanges is false #114

# 3.2.7 2021-04-19
## Fixed
- Aligned SamlContainer class with parent class from simplesamlphp/saml2 lib. Ref: https://github.com/flipboxfactory/saml-sp/issues/110

# 3.2.6 2021-04-13
## Added
- Ability to be explicit with internal provider when passing a request url.

# 3.2.5 2021-03-25
## Fixed
- In the edit template, fixed the issue with create type not being set correctly and therefore, not showing the key config on the security tab.
- Column type for the providerUid changed to type `uid`.

# 3.2.4 2021-03-12
## Fixed
- Updating control panel lists to be explicit.

# 3.2.3 2021-02-12
## Fixed
- Migration issue with duplicate metadataOptions

# 3.2.2 2021-02-11
## Fixed
- Migration error when upgrading to Craft CMS 3.6. ref: https://github.com/flipboxfactory/saml-idp/issues/22 and https://github.com/flipboxfactory/saml-sp/issues/98

# 3.2.1 2021-01-28
## Fixed
- Issue with missing beginning forward slash on the provider url when it's not a full url

# 3.2.0 2021-01-09
## Added
- EntityID is is now editible
- Site Selection on My provider page

## Changed
- Url formating
- `flipbox\saml\core\services\Metadata::create` (moved to providers records)


# 3.1.2 - 2020-10-29
## Fixed
- Issue where SP and IdP plugin couldn't be installed on the same craft db due to table conflicts.

# 3.1.0 - 2020-09-22
## Added
- Added NameID Override per IDP to the SP templates.

# 3.0.1 - 2020-08-06
## Fixed
- Constraint on the provider identity table.

# 3.0.0 - 2020-07-14
## Changed
- Updated `simplesamlphp/saml2` to `^4.1`.

# 2.1.7 - 2020-05-15
## Removed
- `\flipbox\saml\core\helpers\SerializeHelper::toBase64`
- `\flipbox\saml\core\helpers\SerializeHelper::isBase64String`

# 2.1.6 - 2020-05-06
## Fixed
- Missed a spot with 57 https://github.com/flipboxfactory/saml-sp/issues/57

# 2.1.5 - 2020-05-05
## Fixed
- Issue CP panel presenting the SLO endpoint, fixing: https://github.com/flipboxfactory/saml-sp/issues/57

# 2.1.4 - 2020-03-12
## Fixed
- Fixed issue with Metadata URL not overwriting the metadata correctly via the control panel.

# 2.1.3 - 2020-03-04
## Fixed
- Fixes issue with `GeneralConfig::headlessMode` by explicitly setting response to HTML. Fixes: https://github.com/flipboxfactory/saml-sp/issues/53

# 2.1.2 - 2020-02-06
## Fixed
- Fixing issue with migration from 1.x to 2.x. Fixes: https://github.com/flipboxfactory/saml-sp/issues/51

# 2.1.1.1 - 2020-01-08
## Fixed
- Fixing issue with Craft 3.2 twig error within the editableTable

# 2.1.1 - 2020-01-08
## Fixed
- Fixing issue with postgres uid - https://github.com/flipboxfactory/saml-sp/issues/49

# 2.1.0 - 2020-01-07
## Fixed
- Fixing issue with requiring admin when project config when `allowAdminChanges` general config is set.
- Duplicate `metadata` html attribute id on the edit page
- Fixed issue with large Metadata too big for the db metadata column (requires migration) https://github.com/flipboxfactory/saml-sp/issues/48

## Added
- Support for Saving Metadata via url (requires migration) https://github.com/flipboxfactory/saml-sp/issues/47

# 2.0.26 - 2020-01-03
## Fixed
- Issue with OneLogin signiture verification.

# 2.0.25 - 2019-11-07
## Fixed
- Patching issue with more than one signing key, filters signing key, and improved bail when signiture is verified

# 2.0.24 - 2019-11-07
## Fixed
- Issue with verifying signitures for providers with more than one signing key

# 2.0.23 - 2019-10-15
## Fixed
- Fixed url in admin for SPs request login and logout

# 2.0.20 - 2019-10-07
## Removed
- flipboxfactory/craft-ember package to easy updates with dependancies.

# 2.0.18 - 2019-10-02
## Added
- Added config option `sloDestroySpecifiedSessions` to support SLO for specified users

# 2.0.17 - 2019-10-01
## Fixed
- Issue with HTTP-Redirect

## Added
- Support for HTTP-Redirect binding for SLO

# 2.0.16 - 2019-09-26
## Fixed
- Issue with decrypting assertions
- Link not rendering correctly on the edit screen for providers

# 2.0.15 - 2019-09-25
## Fixed
- Fixing more xsd schema compatibility. Changed message ids to be compatible.
- Fixed exception when the user tries to logout (SLO) when they are already logged out.

# 2.0.11 - 2019-09-25
## Fixed
- Fixed xsd schema compatibility. Changed Indexed Endpoints to normal Endpoints (removing invalid index attribute).

# 2.0.5
## Fixed
- Fixed issue with cp links on the list page

# 2.0.3.7
## Changed
- Changing AccessDenied Exception to a yii HttpException which returns a 403 status

# 2.0.0
## Changed
- Lots of changes!
- Switched from the php LightSaml package to the simplesamlphp core lib.
- Code clean up and considation

# 1.0.1
## Fixed
- Refactoring for cleanup

# 1.0.0-RC1
## Added
- Improved Control Panel UI
- Login via admin for sp
- Labels for Providers
- Auto generate OpenSSL key pairs with Keychain
- Mapping attributes based on provider

# 1.0.0-beta.12
## Fixed
- defaulting signing method to rsa256 (instead of sha1)

# 1.0.0-beta.11
## Fixed
- Fixed a bug where during the verification of a signature, we were pulling the first key from the metadata
which could be the wrong one. Now specify the signing key.

# 1.0.0-beta.10
## Fixed
- Fixed issue with base64 being encoded twice.
## Added
- Easy logging for the plugin

# 1.0.0-beta.9
## Fixed
- Issue with saving metadata with signatures on them

## Removed
- Restriction on forcing HTTP POST for SLO request

# 1.0.0-beta.8
Initial release.
