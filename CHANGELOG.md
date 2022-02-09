# Changelog

All notable changes to this project will be documented in this file.

## [unreleased]

### :heart: Added
* added config key backendDefaultView which will point to default backend view after login
* added application and module version info to systemcheck page

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

