<?php
if (!defined('ABSPATH')) exit;

function oiai_get_offer_questions() {
    return [
        [
            'label' => 'Qui est le client idéal ?',
            'desc' => 'Décrivez le profil du client, ses frustrations, objectifs, etc.',
            'options' => [
                'Hommes de 30 à 50 ans, cadres supérieurs',
                'Jeune cadre, stressé par son travail',
                'Recherche une solution pour améliorer son confort au quotidien',
                'Personnes actives soucieuses de leur apparence',
                'Professionnels en déplacement fréquent',
                'Entrepreneurs dynamiques',
                'Passionnés de mode',
            ],
            'custom' => true,
        ],
        [
            'label' => 'Quel est le résultat désiré ou la transformation que ce client souhaite obtenir ?',
            'desc' => 'Quel changement concret ou bénéfice le client veut-il obtenir ?',
            'options' => [
                'Se sentir plus élégant et professionnel',
                'Améliorer sa posture grâce à des chaussures confortables',
                'Augmenter sa confiance en soi lors de réunions importantes',
                'Être à l’aise toute la journée',
                'Recevoir des compliments sur son style',
                'Optimiser son image auprès de ses pairs',
                'Être fier de son apparence',
            ],
            'custom' => true,
        ],
        [
            'label' => 'Quelle est la méthode unique ou ton approche différenciante ?',
            'desc' => 'Quelle est la proposition unique qui distingue votre produit ou service ?',
            'options' => [
                'Fabrication artisanale en cuir véritable',
                'Confort maximal garanti avec semelles spéciales',
                'Chaussures disponibles en plusieurs couleurs adaptées à chaque style',
                'Processus de sélection rigoureux des matériaux',
                'Design exclusif et moderne',
                'Technologie innovante pour le confort',
                'Service de personnalisation avancé',
            ],
            'custom' => true,
        ],
        [
            'label' => 'Quelle garantie ou promesse forte puis-je proposer ?',
            'desc' => 'Quelle garantie peut rassurer l’acheteur ?',
            'options' => [
                'Garantie satisfait ou remboursé sous 30 jours',
                'Livraison gratuite et sans risques',
                'Garantie de confort sur toute la durée du produit',
                'Assistance après-vente dédiée',
                'Garantie d’authenticité du cuir',
                'Garantie à vie sur les coutures',
                'Échange gratuit en cas de problème',
            ],
            'custom' => true,
        ],
        [
            'label' => 'Quelle urgence ou rareté puis-je intégrer dans l’offre ?',
            'desc' => 'Comment rendre l’offre urgente ou limitée ?',
            'options' => [
                'Offre limitée à 50 paires',
                'Livraison gratuite pendant 24h seulement',
                'Prix spécial pour les 100 premiers clients',
                'Stock limité',
                'Promotion valable jusqu’à dimanche minuit',
                'Vente flash 48h',
                'Cadeau exclusif pour les premiers clients',
            ],
            'custom' => true,
        ],
    ];
}
