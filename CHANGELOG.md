Changelog
=========
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
