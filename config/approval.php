<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Workflow
    |--------------------------------------------------------------------------
    |
    | This is the default workflow that will be used if no workflow is specified
    | on the Approvable model. You can set this to null if you don't want
    | a default workflow.
    |
    */
    'default_workflow' => null, // Or set a default if you have one

    /*
    |--------------------------------------------------------------------------
    | Workflows
    |--------------------------------------------------------------------------
    |
    | Here you can define all the workflows for your application. Each workflow
    | should have a unique name and an array of steps. Each step should
    | have a unique name and an array of approver roles.
    |
    | 'workflow_name' => [
    |    'steps' => [
    |       'step_name' => [
    |           'label' => 'Step Label', // Optional: User-friendly label for the step
    |           'description' => 'Step Description', // Optional: More details about the step
    |           'approver_role' => 'role_name', // Role required to approve this step (from spatie/laravel-permission)
    |           'conditions' => ['condition_name_1', 'condition_name_2'], // Optional: Conditions to be met for this step
    |           'action_buttons' => [ // Optional: Custom action buttons for Filament
    |               'approve' => [
    |                   'label' => 'Approve Step',
    |                   'color' => 'success',
    |               ],
    |               'reject' => [
    |                   'label' => 'Reject Step',
    |                   'color' => 'danger',
    |               ],
    |           ],
    |           'notifications' => [ // Optional: Notifications to send
    |               'on_pending' => [], // Notify when step becomes pending
    |               'on_approved' => [], // Notify when step is approved
    |               'on_rejected' => [], // Notify when step is rejected
    |           ],
    |           'auto_trigger' => null, // Optional: Event or condition to auto-trigger approval/rejection
    |       ],
    |    ],
    | ],
    */
    'workflows' => [
        'demande-devis-workflow' => [
            'steps' => [
                'responsable-service' => [
                    'label' => 'Validation responsable service',
                    'description' => 'Validation hiérarchique du service',
                    'approver_role' => 'responsable-service',
                    'conditions' => ['agent_same_service'],
                ],
                'responsable-budget' => [
                    'label' => 'Validation budgétaire', // Added from DemandeDevis model spec
                    'description' => 'Vérification cohérence budget et enveloppe service', // Added
                    'approver_role' => 'responsable-budget', // This must match a role name in your Spatie roles
                    'conditions' => ['budget_available', 'line_validated'] // These are symbolic, logic is in canBeApproved or custom condition checkers
                ],
                'service-achat' => [
                    'label' => 'Validation achat', // Added
                    'description' => 'Analyse fournisseur et optimisation commande', // Added
                    'approver_role' => 'service-achat',
                    'conditions' => ['supplier_valid', 'commercial_terms_ok']
                ],
                'reception-livraison' => [
                    'label' => 'Contrôle réception', // Added
                    'description' => 'Vérification livraison et conformité produit', // Added
                    'approver_role' => 'service-demandeur', // This implies the original requester or someone in their service
                                                           // Ensure this role has permissions to approve this step.
                    'auto_trigger' => 'on_delivery_upload' // Symbolic, actual trigger mechanism needs implementation (e.g. event listener)
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Approval Model
    |--------------------------------------------------------------------------
    |
    | This is the model that will be used to store the approval history. You
    | can change this to your own model if you have extended the default
    | model.
    |
    */
    'approval_model' => \RingleSoft\LaravelProcessApproval\Models\ProcessApproval::class, // Or App\Models\ProcessApproval if you extended it

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This is the model that will be used to store your users. You can change
    | this to your own model if you have extended the default model.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Role Model
    |--------------------------------------------------------------------------
    |
    | This is the model that will be used to store your roles. This is only
    | used if you are using the built-in role based approval.
    |
    */
    'role_model' => \Spatie\Permission\Models\Role::class,

    /*
    |--------------------------------------------------------------------------
    | Approval Actions
    |--------------------------------------------------------------------------
    |
    | You can define custom actions that can be performed on an approvable model.
    | These actions can be dispatched from your application code.
    |
    */
    'actions' => [
        // 'custom_action' => \App\ApprovalActions\CustomAction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Conditions
    |--------------------------------------------------------------------------
    |
    | Define custom condition checkers. These are classes that implement
    | RingleSoft\LaravelProcessApproval\Contracts\ConditionChecker.
    | The `canBeApproved` method on the model is the primary condition checker.
    |
    */
    'conditions' => [
        // 'budget_available' => \App\ApprovalConditions\BudgetAvailableCondition::class,
        // 'line_validated' => \App\ApprovalConditions\LineValidatedCondition::class,
        // 'supplier_valid' => \App\ApprovalConditions\SupplierValidCondition::class,
        // 'commercial_terms_ok' => \App\ApprovalConditions\CommercialTermsOkCondition::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure notifications for approval events.
    |
    */
    'notifications' => [
        'mail' => [
            'on_pending' => null, // Example: \App\Notifications\ApprovalPendingNotification::class,
            'on_approved' => null, // Example: \App\Notifications\ApprovalApprovedNotification::class,
            'on_rejected' => null, // Example: \App\Notifications\ApprovalRejectedNotification::class,
        ],
        // Add other notification channels like 'database', 'slack' etc.
    ],
];
