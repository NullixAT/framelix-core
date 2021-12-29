<?php

namespace Framelix\Framelix;


/**
 * Framelix error codes
 */
enum ErrorCode
{
    case NO_SPECIFIC;
    case CONFIG_VALUE_INVALID_TYPE;
    case NATIVE_CLONE;
    case PHP_ERROR;
    case STORABLE_SORT_CONDITION;
    case STORABLEMETA_NOSTORABLE;
    case URL_HASH;
    case URL_MISSING_SIGNATURE;
    case URL_INCORRECT_SIGNATURE;
    case URL_EXPIRED_SIGNATURE;
    case TOPLEVEL_CALL_ONLY;
    case VIEW_NOURL;
    case VIEW_ACCESSROLE_MISSING;
    case BACKEND_GLOBALCONTEXT_MISSINGKEY;
    case MYSQL_UNSUPPORTED_DB_PARAMETER;
    case MYSQL_CONNECT_ERROR;
    case MYSQL_QUERY_ERROR;
    case MYSQL_FETCH_ASSOC_INDEX_MISSING;
    case STORABLESCHEMA_INVALID_PROPERTY_TYPE;
    case STORABLESCHEMA_INVALID_DOUBLE;
    case FORM_VISIBILITYCONDITION;
    case FORM_CAPTCHA_TYPE_MISSING;
    case FORM_GRID_NESTED_NOT_ALLOWED;
    case COMPILER_DISTFILE_NOTEXIST;
    case COMPILER_BABEL_MISSING;
    case COMPILER_COMPILE_ERROR;
    case HTMLUTILS_INCLUDE_INVALID_EXTENSION;
    case TABLE_FOOTERSUM_COLUMN_NOTEXIST;
    case API_INVALID_METHOD;
    case API_MIXED_OUTPUT;
    case STORABLE_SORT_DIRECTION_MISSING;
    case NOT_INSTANCEOF;
    case NOT_TYPEOF;
    case NOT_OPTIONAL;
    case STORABLE_NEW_DELETE;
    case STORABLE_NOT_DELETABLE;
    case STORABLEFILE_FILE_NOTEXIST;
    case STORABLEFILE_FOLDER_NOTEXIST;
    case STORABLEFILE_FILENAME_MISSING;
    case STORABLEFILE_COPY_FAILURE;
    case ARRAYUTILS_SORT_FLAG_MISSING;
    case CLASSUTILS_CLASSNAME_INVALID;
    case EMAIL_CONFIG_MISSING;
    case ZIP_OPEN;
    case ZIP_UNZIP_NOFILE;
    case ZIP_UNZIP_NODIRECTORY;
    case ZIP_UNZIP_NOTEMPTY;
    case CORE_MINPHPVERSION;
    case SYSTEMVALUE_PROPERTY_MISSING;
    case STORABLE_PROPERTY_NOTEXIST;
    case STORABLE_ARRAY_DUPE_REFERENCES;
    case FORM_SEARCH_METHOD_MISSING;
    case STORABLEFILE_FILE_MISSING;
}