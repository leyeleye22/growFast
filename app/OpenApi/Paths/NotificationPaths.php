<?php



namespace App\OpenApi\Paths;

use OpenApi\Attributes as OAT;

#[OAT\PathItem(
    path: '/notifications',
    get: new OAT\Get(
        operationId: 'notificationsIndex',
        summary: 'Liste des notifications',
        description: 'Notifications internes (DB) de l\'utilisateur connecté. Pagination via per_page (max 50).',
        tags: ['Notifications'],
        parameters: [
            new OAT\Parameter(name: 'per_page', in: 'query', required: false, schema: new OAT\Schema(type: 'integer', default: 15)),
        ],
        responses: [new OAT\Response(response: 200, description: 'OK')]
    )
)]
#[OAT\PathItem(
    path: '/notifications/unread-count',
    get: new OAT\Get(
        operationId: 'notificationsUnreadCount',
        summary: 'Nombre de notifications non lues',
        tags: ['Notifications'],
        responses: [new OAT\Response(response: 200, description: 'OK')]
    )
)]
#[OAT\PathItem(
    path: '/notifications/{id}/read',
    patch: new OAT\Patch(
        operationId: 'notificationsMarkAsRead',
        summary: 'Marquer une notification comme lue',
        tags: ['Notifications'],
        parameters: [new OAT\Parameter(name: 'id', in: 'path', required: true, schema: new OAT\Schema(type: 'string', format: 'uuid'))],
        responses: [new OAT\Response(response: 200, description: 'OK')]
    )
)]
#[OAT\PathItem(
    path: '/notifications/mark-all-read',
    post: new OAT\Post(
        operationId: 'notificationsMarkAllAsRead',
        summary: 'Marquer toutes les notifications comme lues',
        tags: ['Notifications'],
        responses: [new OAT\Response(response: 200, description: 'OK')]
    )
)]
#[OAT\PathItem(
    path: '/notifications/{id}',
    delete: new OAT\Delete(
        operationId: 'notificationsDestroy',
        summary: 'Supprimer une notification',
        tags: ['Notifications'],
        parameters: [new OAT\Parameter(name: 'id', in: 'path', required: true, schema: new OAT\Schema(type: 'string', format: 'uuid'))],
        responses: [new OAT\Response(response: 200, description: 'OK')]
    )
)]
class NotificationPaths
{
}
