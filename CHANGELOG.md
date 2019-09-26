Changelog
=========
# 2.0.15 - 2019-09-25
### Fixed
- Fixing more xsd schema compatibility. Changed message ids to be compatible.
- Fixed exception when the user tries to logout (SLO) when they are already logged out.

# 2.0.11 - 2019-09-25
### Fixed
- Fixed xsd schema compatibility. Changed Indexed Endpoints to normal Endpoints (removing invalid index attribute).

# 2.0.5
### Fixed
- Fixed issue with cp links on the list page 

# 2.0.3.7
### Changed
- Changing AccessDenied Exception to a yii HttpException which returns a 403 status

# 2.0.0
### Changed
- Lots of changes!
- Switched from the php LightSaml package to the simplesamlphp core lib.
- Code clean up and considation

# 1.0.1
### Fixed
- Refactoring for cleanup

# 1.0.0-RC1
### Added
- Improved Control Panel UI
- Login via admin for sp
- Labels for Providers
- Auto generate OpenSSL key pairs with Keychain
- Mapping attributes based on provider

# 1.0.0-beta.12
### Fixed
- defaulting signing method to rsa256 (instead of sha1)

# 1.0.0-beta.11
### Fixed
- Fixed a bug where during the verification of a signature, we were pulling the first key from the metadata
which could be the wrong one. Now specify the signing key.

# 1.0.0-beta.10
### Fixed
- Fixed issue with base64 being encoded twice.
### Added
- Easy logging for the plugin

# 1.0.0-beta.9
### Fixed
- Issue with saving metadata with signatures on them

### Removed
- Restriction on forcing HTTP POST for SLO request

# 1.0.0-beta.8
Initial release.
