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
                ],
                'responsable-budget' => [
                    'label' => 'Validation budgétaire',
                    'description' => 'Vérification cohérence budget global',
                    'approver_role' => 'responsable-budget',
                    'conditions' => [],
                    'auto_approve' => false,
                ],
                'service-achat' => [
                    'label' => 'Validation achat',
                    'description' => 'Optimisation fournisseur et création commande',
                    'approver_role' => 'service-achat',
                    'conditions' => [],
                    'auto_approve' => false,
                ],
            ],
        ],
    ],
];
