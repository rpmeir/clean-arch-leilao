<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Ramsey\Uuid\Uuid;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$connection = new PDO('pgsql:dbname=postgres;host=localhost;port=5432', 'postgres', '123456');

$app->post('/auctions', static function (Request $request, Response $response) use ($connection) {
    $auction = json_decode($request->getBody()->getContents(), true);
    $auction['auctionId'] = Uuid::uuid4()->toString();
    $statement = $connection->prepare('INSERT INTO leilao.auction (auction_id, start_date, end_date, min_increment, start_amount) VALUES (:auctionId, :startDate, :endDate, :minIncrement, :startAmount);');
    $statement->execute($auction);

    $response->getBody()->write(json_encode($auction));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/bids', static function (Request $request, Response $response) use ($connection) {
    $bid = json_decode($request->getBody()->getContents(), true);
    $bid['bidId'] = Uuid::uuid4()->toString();

    $statement = $connection->prepare('SELECT * FROM leilao.auction WHERE auction_id = :auctionId;');
    $statement->execute(['auctionId' => $bid['auctionId']]);
    [$auctionData] = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (! $auctionData) {
        $response->withStatus(404, 'Auction not found');
        return $response->withHeader('Content-Type', 'application/json');
    }

    $bidDate = new DateTimeImmutable($bid['date']);
    $auctionEndDate = new DateTimeImmutable($auctionData['end_date']);
    if ($bidDate > $auctionEndDate) {
        $response->getBody()->write(json_encode(['error' => 'Auction is ended']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(422);
    }

    $statement = $connection->prepare('INSERT INTO leilao.bid (bid_id, auction_id, customer, amount, date) VALUES (:bidId, :auctionId, :customer, :amount, :date);');
    $statement->execute($bid);

    $response->getBody()->write(json_encode($bid));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/auctions/{auctionId}', static function (Request $request, Response $response, array $args) use ($connection) {
    $auctionId = $args['auctionId'];
    $statement = $connection->prepare('SELECT * FROM leilao.auction WHERE auction_id = :auctionId;');
    $statement->execute(['auctionId' => $auctionId]);
    [$auctionData] = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (! $auctionData) {
        $response->withStatus(404, 'Auction not found');
        return $response->withHeader('Content-Type', 'application/json');
    }
    $statement = $connection->prepare('SELECT * FROM leilao.bid WHERE auction_id = :auctionId ORDER BY amount DESC LIMIT 1;');
    $statement->execute(['auctionId' => $auctionId]);
    [$bidData] = $statement->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode([
        'auctionId' => $auctionId,
        'highestBid' => $bidData
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
