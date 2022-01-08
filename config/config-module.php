<?php

// prevent loading directly in the browser without framelix context
if (!defined("FRAMELIX_MODULE")) {
    die();
}
// this config represents the module configuration defaults
// this are settings that are defined by the module developer
// some keys may be editable in the configuration admin interface
// which then will be saved into config-editable.php
?>
<script type="application/json">
    {
        "clientIpKey": "REMOTE_ADDR",
        "languageDefault": "en",
        "languageFallback": "en",
        "languageMultiple": false,
        "compiler": {
            "Framelix": {
                "js": {
                    "form": {
                        "files": [
                            {
                                "type": "file",
                                "path": "js/form/framelix-form.js"
                            },
                            {
                                "type": "file",
                                "path": "js/form/framelix-form-field.js"
                            },
                            {
                                "type": "file",
                                "path": "js/form/framelix-form-field-text.js"
                            },
                            {
                                "type": "file",
                                "path": "js/form/framelix-form-field-select.js"
                            },
                            {
                                "type": "folder",
                                "path": "js/form",
                                "recursive": true
                            }
                        ]
                    },
                    "general-vendor-native": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "vendor/polyfills",
                                "recursive": true
                            },
                            {
                                "type": "file",
                                "path": "vendor/cashjs/cash.min.js"
                            },
                            {
                                "type": "file",
                                "path": "vendor/cashjs/cash-improvements.js"
                            },
                            {
                                "type": "folder",
                                "path": "vendor/dayjs",
                                "recursive": true
                            },
                            {
                                "type": "folder",
                                "path": "vendor/popperjs",
                                "recursive": true
                            },
                            {
                                "type": "folder",
                                "path": "vendor/swiped-events",
                                "recursive": true
                            }
                        ],
                        "options": {
                            "noCompile": true
                        }
                    },
                    "sortablejs": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "vendor/sortablejs"
                            }
                        ],
                        "options": {
                            "noCompile": true,
                            "noInclude": true
                        }
                    },
                    "qrcodejs": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "vendor/qrcodejs"
                            }
                        ],
                        "options": {
                            "noCompile": true,
                            "noInclude": true,
                            "noStrict": true
                        }
                    },
                    "general-vendor-compiled": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "vendor/form-data-json",
                                "recursive": true
                            }
                        ]
                    },
                    "general-early": {
                        "files": [
                            {
                                "type": "file",
                                "path": "js/framelix-local-storage.js"
                            },
                            {
                                "type": "file",
                                "path": "js/framelix-session-storage.js"
                            },
                            {
                                "type": "file",
                                "path": "js/framelix-device-detection.js"
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "general": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "js",
                                "ignoreFilenames": [
                                    "framelix-table-sort-serviceworker.js",
                                    "framelix-device-detection.js",
                                    "framelix-local-storage.js",
                                    "framelix-session-storage.js"
                                ]
                            }
                        ]
                    },
                    "table-sorter": {
                        "files": [
                            {
                                "type": "file",
                                "path": "js/framelix-table-sort-serviceworker.js"
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "backend": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "js/backend",
                                "recursive": true
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    }
                },
                "scss": {
                    "general": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "scss/general"
                            }
                        ]
                    },
                    "form": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "scss/form",
                                "recursive": true
                            }
                        ]
                    },
                    "backend": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "scss/backend",
                                "recursive": true,
                                "ignoreFilenames": [
                                    "framelix-backend-fonts.scss"
                                ]
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "backend-fonts": {
                        "files": [
                            {
                                "type": "file",
                                "path": "scss/backend/framelix-backend-fonts.scss"
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    }
                }
            }
        },
        "userRoles": {
            "admin": "__framelix_user_role_admin__",
            "dev": "__framelix_user_role_dev__",
            "usermanagement": "__framelix_edituser_sidebar_title__",
            "configuration": "__framelix_view_backend_config_index__",
            "logs": "__framelix_view_backend_logs__"
        },
        "backendStartUrl" : "/",
        "userTokenCookieName": "{module}_user_token",
        "captchaScoreTreshold": 0,
        "loginCaptcha": false,
        "devMode": false,
        "systemEventLog": {
            "1": false,
            "2": false,
            "3": false,
            "4": true,
            "5": true,
            "6": true
        }
    }
</script>
