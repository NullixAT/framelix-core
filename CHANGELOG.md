## [1.9.0 - 2022-06-15]

* some cleanup and removements of old files
* added dev role and set dev pages under this dev role
* added `Shell->getOutput()` for nice formatting
* fixed modal width on small screens
* fixed bug docker update will still be marked as available after update
* fixed remember active tab bug with multiple tabs instances on same page
* removed content-length header for response download to fix issues with corrupt downloads

## [1.8.0 - 2022-05-13]

* changed hidden form submit names, now prefixed with framelix-form-
* fixed wrong redirect after login, when a custom redirect is defined
* fixed backend sidebar overflow when text is too long
* fixed bug with error handler show dupe errors because of StopException catch
* fixed field visibility condition for not* conditions
* fixed number format/parse
* fixed a fiew field layout issues
* fixed FramelixDom.isInDom() with some elements
* fixed setStorableValues in case of storableFile properties without a fileUpload
* fixed FramelixColorUtils.hexToRgb returning object instead of array
* optimized email settings
* optimized FramelixModal for different screen sizes
* views with regex in url now remove parameters that are not used when generating urls
* backend pages now by default need a user to be logged in
* added Lang::concatKeys to easily concat lang keys
* added setIntegerOnly() to number field
* added noAnimation option to FramelixModal
* added QuickSearch->forceInitialQuery to set a initial query no matter what the user have stored
* added fieldGroups to Forms, to be able to group fields under a collapsable
* added `Tar` class to create and extract tar files
* added JsCallUnsigned to call handcrafted PHP methods from frontend without a correctly backend signed url
* refactored language handling for more flexible way to load and add values
* refactored update and release process
* removed a few unused user roles
* removed release build script in favor of new https://github.com/NullixAT/framelix-release action

## [1.7.0 - 2022-02-04]

* design refactoring to be more modern and clear

### :pencil: Changed

* changed url anti cache parameter to be always included instead of only 7 days to fix fallback to old cache when
  parameter gets removed

### :heart: Added

* added maxWidth option to framelix modal and use it for alert, confirm and prompt by default

### :pencil: Changed

* changed url anti cache parameter to be always included instead of only 7 days to fix fallback to old cache when
  parameter gets removed

### :wrench: Fixed

* fixed modal window prompt enter key not working
* fixed url signature check

## [1.6.2 - 2022-03-08]

### :heart: Added

* added maxWidth option to framelix modal and use it for alert, confirm and prompt by default

### :pencil: Changed

* changed url anti cache parameter to be always included instead of only 7 days to fix fallback to old cache when
  parameter gets removed

### :wrench: Fixed

* fixed modal window prompt enter key not working
* fixed url signature check

### :police_car: Security

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
* fixed error when app update throws an error during update result in update never work again because tmp folder was not
  cleared

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

