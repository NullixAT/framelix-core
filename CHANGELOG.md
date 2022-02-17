# Changelog

All notable changes to this project will be documented in this file.

## [1.6.1 - 2022-02-17]

### :heart: Added

### :pencil: Changed
* changed language key syntax for singular/plurar to must include the number itself for more flexibility
* changed sass compiler to `sass` instead of deprecated `node-sass`
* changed default value for `captchaScoreThreshold` in default config

### :construction: Deprecated

### :x: Removed

### :wrench: Fixed
* fixed typo in `captchaScoreTreshold`
* fixed error when app update throws an error during update result in update never work again because tmp folder was not cleared

### :police_car: Security


## [1.5.0 - 2022-02-09]

### :heart: Added
* added config key backendDefaultView which will point to default backend view after login
* added application and module version info to systemcheck page
* added userpwd and requestBody to browser

### :pencil: Changed
* upgraded node-sass compiler and babel compiler to newest version
* updated backend small layout a bit, so it has a blurry bg
* changed modal window now use semi-transparent page in background instead of blur

### :construction: Deprecated

### :x: Removed
* module config key setupDoneRedirect and replaced it with "backendDefaultView"

### :wrench: Fixed
* fixed typo in Config function
* fixed modal window blur filter will result in repaint a "broken" sidebar

### :police_car: Security

## [1.4.0 - 2022-02-08]

a lot of updates and fixes for backend and general framework

### :heart: Added

* added resort actions to developer language editor to resort keys and update lang files easily
* added headHtmlAfterInit
* added getHtmlTableValue for ObjectTransformable
* added cron to delete system event logs

### :pencil: Changed

* system event log page to be
* improved and simplified use of quick search

### :construction: Deprecated

### :x: Removed

* removed grid field as it was flaky and hard to use on mobile

### :wrench: Fixed

* fixed framelix-string-utils -> slugify replacing only one char
* fixed a lot of layout issues
* fixed layout issues with fields that uses buttons

### :police_car: Security

