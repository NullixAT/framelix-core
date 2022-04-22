* changed hidden form submit names, now prefixed with framelix-form-
* fixed wrong redirect after login, when a custom redirect is defined
* fixed backend sidebar overflow when text is too long
* fixed bug with error handler show dupe errors because of StopException catch
* fixed field visibility condition for not* conditions
* fixed number format/parse
* fixed a fiew field layout issues
* fixed FramelixDom.isInDom() with some elements
* optimized email settings
* views with regex in url now remove parameters that are not used when generating urls
* backend pages now by default need a user to be logged in
* added Lang::concatKeys to easily concat lang keys
* added setIntegerOnly() to number field
* added noAnimation option to FramelixModal