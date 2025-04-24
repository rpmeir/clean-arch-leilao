<?php

$client = new GuzzleHttp\Client([
    'base_uri' => 'http://localhost:8000/',
    'http_errors' => false
]);

describe('Testar enpoints', function () use ($client) {

    test('Deve criar um leilão e dar três lances', function () use ($client) {
        $inputCreateAuction = [
            'startDate' => '2025-03-01T10:00:00Z',
            'endDate' => '2025-03-01T12:00:00Z',
            'minIncrement' => 10,
            'startAmount' => 1000
        ];
        $responseCreateAuction = $client->post('/auctions', ['json' => $inputCreateAuction]);
        $outputCreateAuction = json_decode($responseCreateAuction->getBody()->getContents(), true);
        expect($outputCreateAuction)->toHaveKey('auctionId');

        $inputBid1 = [
            'auctionId'=> $outputCreateAuction['auctionId'],
            'customer' => 'a',
            'amount' => 1000,
            'date' => '2025-03-01T10:00:00Z'
        ];
        $responseBid1 = $client->post('/bids', ['json' => $inputBid1]);
        $outputCreateBid = json_decode($responseBid1->getBody()->getContents(), true);
        expect($outputCreateBid)->toHaveKey('bidId');

        $inputBid2 = [
            'auctionId'=> $outputCreateAuction['auctionId'],
            'customer' => 'b',
            'amount' => 1010,
            'date' => '2025-03-01T10:00:00Z'
        ];
        $responseBid2 = $client->post('/bids', ['json' => $inputBid2]);
        $outputCreateBid2 = json_decode($responseBid2->getBody()->getContents(), true);
        expect($outputCreateBid2)->toHaveKey('bidId');

        $inputBid3 = [
            'auctionId'=> $outputCreateAuction['auctionId'],
            'customer' => 'c',
            'amount' => 1100,
            'date' => '2025-03-01T10:00:00Z'
        ];
        $responseBid3 = $client->post('/bids', ['json' => $inputBid3]);
        $outputCreateBid3 = json_decode($responseBid3->getBody()->getContents(), true);
        expect($outputCreateBid3)->toHaveKey('bidId');

        $responseGetAuction = $client->get('/auctions/'.$outputCreateAuction['auctionId']);
        $outputGetAuction = json_decode($responseGetAuction->getBody()->getContents(), true);
        expect($outputGetAuction['highestBid']['customer'])->toBe('c');
        expect($outputGetAuction['highestBid']['amount'])->toBe('1100');
    });

    test('Não deve poder dar lance fora do horário do leilão', function () use ($client) {
        $inputCreateAuction = [
            'startDate' => '2025-03-01T10:00:00Z',
            'endDate' => '2025-03-01T12:00:00Z',
            'minIncrement' => 10,
            'startAmount' => 1000
        ];
        $responseCreateAuction = $client->post('/auctions', ['json' => $inputCreateAuction]);
        $outputCreateAuction = json_decode($responseCreateAuction->getBody()->getContents(), true);

        $inputBid1 = [
            'auctionId'=> $outputCreateAuction['auctionId'],
            'customer' => 'a',
            'amount' => 1000,
            'date' => '2025-03-01T14:00:00Z'
        ];
        $responseBid1 = $client->post('/bids', ['json' => $inputBid1]);
        expect($responseBid1->getStatusCode())->toBe(422);
        $outputCreateBid = json_decode($responseBid1->getBody()->getContents(), true);
        expect($outputCreateBid['error'])->toBe('Auction is ended');
    });

    test('Não pode dar lance menor que o anterior', function() use ($client) {
        $inputCreateAuction = [
            'startDate' => '2025-03-01T10:00:00Z',
            'endDate' => '2025-03-01T12:00:00Z',
            'minIncrement' => 10,
            'startAmount' => 1000
        ];
        $responseCreateAuction = $client->post('/auctions', ['json' => $inputCreateAuction]);
        $outputCreateAuction = json_decode($responseCreateAuction->getBody()->getContents(), true);

        $inputBid1 = [
            'auctionId'=> $outputCreateAuction['auctionId'],
            'customer' => 'a',
            'amount' => 1100,
            'date' => '2025-03-01T11:00:00Z'
        ];
        $client->post('/bids', ['json' => $inputBid1]);

        $inputBid2 = [
            'auctionId'=> $outputCreateAuction['auctionId'],
            'customer' => 'b',
            'amount' => 1000,
            'date' => '2025-03-01T11:30:00Z'
        ];
        $responseBid2 = $client->post('/bids', ['json' => $inputBid2]);
        expect($responseBid2->getStatusCode())->toBe(422);
        $outputCreateBid2 = json_decode($responseBid2->getBody()->getContents(), true);
        expect($outputCreateBid2['error'])->toBe('Bid amount should be higher than the highest bid');
    });

    test('Não deve dar lance seguido pelo mesmo cliente', function() use ($client) {
        $inputCreateAuction = [
            'startDate' => '2025-03-01T10:00:00Z',
            'endDate' => '2025-03-01T12:00:00Z',
            'minIncrement' => 10,
            'startAmount' => 1000
        ];
        $responseCreateAuction = $client->post('/auctions', ['json' => $inputCreateAuction]);
        $outputCreateAuction = json_decode($responseCreateAuction->getBody()->getContents(), true);

        $inputBid1 = [
            'auctionId'=> $outputCreateAuction['auctionId'],
            'customer' => 'a',
            'amount' => 1100,
            'date' => '2025-03-01T11:00:00Z'
        ];
        $client->post('/bids', ['json' => $inputBid1]);

        $inputBid2 = [
            'auctionId'=> $outputCreateAuction['auctionId'],
            'customer' => 'a',
            'amount' => 1200,
            'date' => '2025-03-01T11:30:00Z'
        ];
        $responseBid2 = $client->post('/bids', ['json' => $inputBid2]);
        expect($responseBid2->getStatusCode())->toBe(422);
        $outputCreateBid2 = json_decode($responseBid2->getBody()->getContents(), true);
        expect($outputCreateBid2['error'])->toBe('Auction does not accept sequencial bids from the same customer');
    });
});
