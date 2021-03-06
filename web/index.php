<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'none') !== 'POST') {
    die('error');
}

/** @var \M6\Pushbot\Pushbot $pushbot */
$pushbot = include __DIR__.'/../app/bootstrap.php';

$payload = json_decode(file_get_contents('php://input'));

$user = $payload->item->message->from->mention_name ?? null;
$args = explode(' ', $payload->item->message->message ?? '');

if ($user === null || count($args) < 1) {
    die('failed');
}

array_shift($args); //Pop function name
$response = $pushbot->execute(
    $user,
    array_shift($args),
    $args
);

header('Content-Type: application/json; charset=UTF-8');

echo json_encode(
    [
        'color' => $response->status == \M6\Pushbot\Response::SUCCESS ? 'green' : 'red',
        'message' => $response->body,
        'notify' => true,
        'message_format' => 'text',
        'attach_to' => $payload->item->message->id ?? null,
    ]
);
