<?php



namespace App\OpenApi\Paths;

use OpenApi\Attributes as OAT;

#[OAT\PathItem(
    path: '/startups/{startup}/saved-opportunities',
    get: new OAT\Get(
        operationId: 'savedOpportunitiesIndex',
        summary: 'Liste des opportunités sauvegardées',
        description: 'Opportunités que la startup a marquées comme intéressantes. Des rappels par email sont envoyés avant la date limite.',
        tags: ['Startups'],
        responses: [new OAT\Response(response: 200, description: 'OK')]
    )
)]
#[OAT\PathItem(
    path: '/startups/{startup}/opportunities/{opportunity}/save',
    post: new OAT\Post(
        operationId: 'savedOpportunitySave',
        summary: 'Sauvegarder une opportunité',
        description: 'Marque l\'opportunité comme intéressante. Un rappel par email sera envoyé avant la date limite.',
        tags: ['Startups'],
        responses: [
            new OAT\Response(response: 201, description: 'Opportunité sauvegardée'),
            new OAT\Response(response: 404, description: 'Opportunité introuvable'),
        ]
    ),
    delete: new OAT\Delete(
        operationId: 'savedOpportunityUnsave',
        summary: 'Retirer une opportunité des sauvegardes',
        tags: ['Startups'],
        responses: [new OAT\Response(response: 200, description: 'OK')]
    )
)]
class SavedOpportunityPaths
{
}
