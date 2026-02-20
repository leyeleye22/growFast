<?php



namespace App\OpenApi\Paths;

use OpenApi\Attributes as OAT;

#[OAT\PathItem(
    path: '/opportunities',
    get: new OAT\Get(
        operationId: 'opportunitiesIndex',
        summary: 'Liste des opportunités',
        tags: ['Opportunities'],
        parameters: [
            new OAT\Parameter(name: 'page', in: 'query', schema: new OAT\Schema(type: 'integer')),
            new OAT\Parameter(name: 'per_page', in: 'query', schema: new OAT\Schema(type: 'integer')),
        ],
        responses: [new OAT\Response(response: 200, description: 'Liste paginée')]
    ),
    post: new OAT\Post(
        operationId: 'opportunitiesStore',
        summary: 'Créer une opportunité',
        tags: ['Opportunities'],
        description: 'Nécessite la permission manage_opportunities',
        requestBody: new OAT\RequestBody(
            content: new OAT\JsonContent(
                properties: [
                    new OAT\Property(property: 'title', type: 'string'),
                    new OAT\Property(property: 'description', type: 'string'),
                    new OAT\Property(property: 'funding_type', type: 'string', enum: ['grant', 'equity', 'debt', 'prize', 'other']),
                    new OAT\Property(property: 'deadline', type: 'string', format: 'date'),
                    new OAT\Property(property: 'funding_min', type: 'number'),
                    new OAT\Property(property: 'funding_max', type: 'number'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Créé'),
            new OAT\Response(response: 403, description: 'Forbidden'),
        ]
    )
)]
#[OAT\PathItem(
    path: '/opportunities/{id}',
    get: new OAT\Get(operationId: 'opportunitiesShow', summary: 'Détail opportunité', tags: ['Opportunities'], responses: [new OAT\Response(response: 200, description: 'OK')]),
    put: new OAT\Put(operationId: 'opportunitiesUpdate', summary: 'Modifier', tags: ['Opportunities'], description: 'manage_opportunities', responses: [new OAT\Response(response: 200, description: 'OK')]),
    delete: new OAT\Delete(operationId: 'opportunitiesDestroy', summary: 'Supprimer', tags: ['Opportunities'], description: 'manage_opportunities', responses: [new OAT\Response(response: 204, description: 'No content')]),
)]
#[OAT\PathItem(
    path: '/opportunities/{id}/ask',
    post: new OAT\Post(
        operationId: 'opportunitiesAsk',
        summary: 'Ask Gemini a question about an opportunity',
        tags: ['Opportunities'],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['question'],
                properties: [
                    new OAT\Property(property: 'question', type: 'string', example: 'What is the deadline?'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'OK', content: new OAT\JsonContent(properties: [
                new OAT\Property(property: 'answer', type: 'string'),
                new OAT\Property(property: 'question', type: 'string'),
            ])),
            new OAT\Response(response: 400, description: 'Question required'),
            new OAT\Response(response: 503, description: 'Gemini not configured'),
        ]
    )
)]
class OpportunityPaths
{
}
