<?php

declare(strict_types=1);

namespace App;

use OpenApi\Attributes as OAT;

#[OAT\OpenApi(
    info: new OAT\Info(
        title: 'GrowFast API',
        version: '1.0',
        description: 'API REST pour la découverte et le matching d\'opportunités de financement pour startups.'
    ),
    servers: [
        new OAT\Server(url: '/api', description: 'API Server'),
    ],
    security: [['bearerAuth' => []]],
    tags: [
        new OAT\Tag(name: 'Auth', description: 'Authentification JWT et OAuth'),
        new OAT\Tag(name: 'Opportunities', description: 'Opportunités de financement'),
        new OAT\Tag(name: 'Startups', description: 'Gestion des startups'),
        new OAT\Tag(name: 'Subscriptions', description: 'Abonnements'),
        new OAT\Tag(name: 'Documents', description: 'Documents des startups'),
        new OAT\Tag(name: 'Matching', description: 'Matching opportunités-startups'),
        new OAT\Tag(name: 'Scraping', description: 'Scraping (admin)'),
        new OAT\Tag(name: 'Suggestions', description: 'Suggestions d\'opportunités'),
    ]
)]
#[OAT\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Token JWT obtenu via /api/auth/login'
)]
class OpenApi
{
}
