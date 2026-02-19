<?php

declare(strict_types=1);

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OAT;

#[OAT\PathItem(
    path: '/startups',
    get: new OAT\Get(operationId: 'startupsIndex', summary: 'Liste des startups', tags: ['Startups'], responses: [new OAT\Response(response: 200, description: 'OK')]),
    post: new OAT\Post(
        operationId: 'startupsStore',
        summary: 'Créer une startup',
        tags: ['Startups'],
        requestBody: new OAT\RequestBody(
            content: new OAT\JsonContent(
                properties: [
                    new OAT\Property(property: 'name', type: 'string'),
                    new OAT\Property(property: 'industry', type: 'string'),
                    new OAT\Property(property: 'stage', type: 'string'),
                    new OAT\Property(property: 'country', type: 'string'),
                    new OAT\Property(property: 'funding_min', type: 'number', description: 'Montant minimum souhaité'),
                    new OAT\Property(property: 'funding_max', type: 'number', description: 'Montant maximum souhaité'),
                    new OAT\Property(property: 'funding_types', type: 'array', items: new OAT\Items(type: 'string', enum: ['grant', 'equity', 'debt', 'prize', 'other'])),
                    new OAT\Property(property: 'preferred_industries', type: 'array', items: new OAT\Items(type: 'string')),
                    new OAT\Property(property: 'preferred_stages', type: 'array', items: new OAT\Items(type: 'string')),
                    new OAT\Property(property: 'preferred_countries', type: 'array', items: new OAT\Items(type: 'string')),
                    new OAT\Property(property: 'deadline_min', type: 'string', format: 'date'),
                    new OAT\Property(property: 'deadline_max', type: 'string', format: 'date'),
                ]
            )
        ),
        responses: [new OAT\Response(response: 201, description: 'Créé')]
    )
)]
#[OAT\PathItem(
    path: '/startups/{id}',
    get: new OAT\Get(operationId: 'startupsShow', summary: 'Détail startup', tags: ['Startups'], responses: [new OAT\Response(response: 200, description: 'OK')]),
    put: new OAT\Put(operationId: 'startupsUpdate', summary: 'Modifier', tags: ['Startups'], responses: [new OAT\Response(response: 200, description: 'OK')]),
    delete: new OAT\Delete(operationId: 'startupsDestroy', summary: 'Supprimer', tags: ['Startups'], responses: [new OAT\Response(response: 204, description: 'No content')]),
)]
class StartupPaths
{
}
