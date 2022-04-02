* fixed wrong redirect after login, when a custom redirect is defined
* fixed backend sidebar overflow when text is too long
* fixed bug with error handler show dupe errors because of StopException catch
* views with regex in url now remove parameters that are not used when generating urls
* backend pages now be default need a user to be logged in
* added Lang::concatKeys to easily concat lang keys