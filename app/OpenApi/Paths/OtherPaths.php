<?php

declare(strict_types=1);

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OAT;

#[OAT\PathItem(path: '/subscriptions', get: new OAT\Get(operationId: 'subscriptionsIndex', summary: 'Liste des plans', tags: ['Subscriptions'], responses: [new OAT\Response(response: 200, description: 'OK')]))]
#[OAT\PathItem(path: '/subscriptions/my', get: new OAT\Get(operationId: 'subscriptionsMy', summary: 'Mon abonnement actif', tags: ['Subscriptions'], responses: [new OAT\Response(response: 200, description: 'OK')]))]
#[OAT\PathItem(
    path: '/user-subscriptions/subscribe',
    post: new OAT\Post(
        operationId: 'userSubscribe',
        summary: 'S\'abonner',
        tags: ['Subscriptions'],
        requestBody: new OAT\RequestBody(content: new OAT\JsonContent(properties: [new OAT\Property(property: 'subscription_id', type: 'string')])),
        responses: [new OAT\Response(response: 200, description: 'OK')]
    )
)]
#[OAT\PathItem(path: '/user-subscriptions/cancel', post: new OAT\Post(operationId: 'userCancel', summary: 'Annuler l\'abonnement', tags: ['Subscriptions'], responses: [new OAT\Response(response: 200, description: 'OK')]))]
#[OAT\PathItem(
    path: '/startups/{startup}/documents',
    get: new OAT\Get(operationId: 'documentsIndex', summary: 'Liste des documents', tags: ['Documents'], responses: [new OAT\Response(response: 200, description: 'OK')]),
    post: new OAT\Post(
        operationId: 'documentsStore',
        summary: 'Upload document',
        tags: ['Documents'],
        requestBody: new OAT\RequestBody(content: new OAT\MediaType(mediaType: 'multipart/form-data', schema: new OAT\Schema(properties: [new OAT\Property(property: 'file', type: 'string', format: 'binary')]))),
        responses: [new OAT\Response(response: 201, description: 'Créé')]
    )
)]
#[OAT\PathItem(path: '/startups/{startup}/documents/{document}', delete: new OAT\Delete(operationId: 'documentsDestroy', summary: 'Supprimer document', tags: ['Documents'], responses: [new OAT\Response(response: 204, description: 'No content')]))]
#[OAT\PathItem(path: '/startups/{startup}/matches', get: new OAT\Get(operationId: 'matchesIndex', summary: 'Opportunités matchées', tags: ['Matching'], responses: [new OAT\Response(response: 200, description: 'Liste des matches avec scores')]))]
#[OAT\PathItem(path: '/scraping/run', post: new OAT\Post(operationId: 'scrapingRun', summary: 'Lancer le scraping', tags: ['Scraping'], description: 'Nécessite run_scraper', responses: [new OAT\Response(response: 200, description: 'OK')]))]
#[OAT\PathItem(
    path: '/opportunity-suggestions',
    post: new OAT\Post(
        operationId: 'suggestionsStore',
        summary: 'Proposer une opportunité',
        tags: ['Suggestions'],
        description: 'Public ou authentifié',
        requestBody: new OAT\RequestBody(
            content: new OAT\JsonContent(
                required: ['grant_name'],
                properties: [
                    new OAT\Property(property: 'grant_name', type: 'string'),
                    new OAT\Property(property: 'url', type: 'string'),
                    new OAT\Property(property: 'description', type: 'string'),
                ]
            )
        ),
        responses: [new OAT\Response(response: 201, description: 'Créé')],
        security: []
    )
)]
class OtherPaths
{
}
