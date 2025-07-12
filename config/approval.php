<?php
return [
    'workflows' => [
        'demande-devis-workflow' => [
            'steps' => [
                'responsable-service' => [
                    'label' => 'Validation responsable service',
                    'description' => 'Validation hiérarchique du service demandeur',
                    'approver_role' => 'responsable-service',
                    'conditions' => ['agent_same_service'],
                    'auto_approve' => false,
                    'timeout_days' => 3,
                ],
                'responsable-budget' => [
                    'label' => 'Validation budgétaire',
                    'description' => 'Vérification cohérence budget global',
                    'approver_role' => 'responsable-budget',
                    'conditions' => ['budget_available'],
                    'auto_approve' => false,
                    'timeout_days' => 5,
                ],
                'service-achat' => [
                    'label' => 'Validation achat',
                    'description' => 'Optimisation fournisseur et création commande',
                    'approver_role' => 'service-achat',
                    'conditions' => [],
                    'auto_approve' => false,
                    'timeout_days' => 7,
                ],
            ],
        ],
    ],

    'notifications' => [
        'email' => true,
        'database' => true,
        'slack' => false,
    ],

    'escalation' => [
        'enabled' => true,
        'timeout_action' => 'notify_admin',
        'admin_roles' => ['admin', 'responsable-budget'],
    ],
];
