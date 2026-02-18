<?php

declare(strict_types=1);

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OAT;

#[OAT\PathItem(
    path: '/auth/register',
    post: new OAT\Post(
        operationId: 'authRegister',
        summary: 'Inscription',
        tags: ['Auth'],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OAT\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OAT\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OAT\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Utilisateur créé'),
            new OAT\Response(response: 422, description: 'Validation error'),
        ],
        security: []
    )
)]
#[OAT\PathItem(
    path: '/auth/login',
    post: new OAT\Post(
        operationId: 'authLogin',
        summary: 'Connexion',
        tags: ['Auth'],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OAT\Property(property: 'email', type: 'string', format: 'email'),
                    new OAT\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Token JWT',
                content: new OAT\JsonContent(
                    properties: [
                        new OAT\Property(property: 'access_token', type: 'string'),
                        new OAT\Property(property: 'token_type', type: 'string', example: 'bearer'),
                        new OAT\Property(property: 'expires_in', type: 'integer'),
                    ]
                )
            ),
            new OAT\Response(response: 401, description: 'Invalid credentials'),
        ],
        security: []
    )
)]
#[OAT\PathItem(path: '/auth/logout', post: new OAT\Post(operationId: 'authLogout', summary: 'Déconnexion', tags: ['Auth'], responses: [new OAT\Response(response: 200, description: 'OK')]))]
#[OAT\PathItem(path: '/auth/refresh', post: new OAT\Post(operationId: 'authRefresh', summary: 'Rafraîchir le token', tags: ['Auth'], responses: [new OAT\Response(response: 200, description: 'Nouveau token')]))]
#[OAT\PathItem(path: '/auth/me', get: new OAT\Get(operationId: 'authMe', summary: 'Utilisateur connecté', tags: ['Auth'], responses: [new OAT\Response(response: 200, description: 'User object')]))]
class AuthPaths
{
}
