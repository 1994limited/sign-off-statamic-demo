<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Collections that need sign-off
    |--------------------------------------------------------------------------
    |
    | Each collection lists the steps an entry goes through before it can be
    | published, in order. Sign-off needs Statamic Pro, with revisions on for
    | each collection here (`revisions: true` in the collection's settings).
    |
    | Who can approve a step:
    |
    |   'approvers' => 'publishers'                    anyone who can publish
    |                                                  the collection's entries
    |   'approvers' => ['roles' => ['legal']]          users with any of these roles
    |   'approvers' => ['groups' => ['marketing']]     members of any of these groups
    |   'approvers' => ['users' => ['sam@example.com']]  these users (email or ID)
    |   'approvers' => ['permission' => 'approve legal']  users with this permission
    |
    | Mix them in one array to allow any of several kinds. Super users can
    | approve any step.
    |
    */

    'collections' => [

        // A page is checked by Legal, then published by a Publisher.
        'pages' => [
            'steps' => [
                ['handle' => 'legal', 'title' => 'Legal', 'approvers' => ['roles' => ['legal']]],
                ['handle' => 'publisher', 'title' => 'Publisher', 'approvers' => 'publishers'],
            ],
        ],

        // Prices are checked by the Pricing team (a user group) first.
        'tariffs' => [
            'steps' => [
                ['handle' => 'pricing', 'title' => 'Pricing', 'approvers' => ['groups' => ['pricing']]],
                ['handle' => 'legal', 'title' => 'Legal', 'approvers' => ['roles' => ['legal']]],
                ['handle' => 'publisher', 'title' => 'Publisher', 'approvers' => 'publishers'],
            ],
        ],

        // News needs one editor's approval.
        'news' => [
            'steps' => [
                ['handle' => 'editor', 'title' => 'Editor', 'approvers' => 'publishers'],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Rules
    |--------------------------------------------------------------------------
    */

    // Can the person who submitted an entry approve a step of it?
    'self_approval' => false,

    // While an entry is in review, nobody can change it. The author can
    // withdraw it to make changes, or a reviewer can reject it.
    'lock_during_review' => true,

    // Users with the "Publish without sign-off" permission can still publish
    // directly; it's recorded in the entry's sign-off history. Super users
    // have it unless this is false.
    'super_users_can_skip' => true,

    // Taking a published entry down (unpublishing) follows Statamic's normal
    // publish permission, without sign-off, so content can come down fast.
    'unpublish_without_sign_off' => true,

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Where submissions and their history are kept: "file" (YAML files, like
    | form submissions) or "database".
    |
    */

    'driver' => env('SIGN_OFF_DRIVER', 'file'),

    'drivers' => [
        'file' => [
            'directory' => storage_path('sign-off'),
        ],
        'database' => [
            'connection' => env('SIGN_OFF_DB_CONNECTION'),
            'table' => 'sign_off_submissions',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email
    |--------------------------------------------------------------------------
    |
    | Reviewers are emailed when an entry reaches their step; the person who
    | submitted it when it's approved, rejected or published.
    |
    */

    'notifications' => [
        'mail' => true,
    ],

];
