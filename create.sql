drop schema if exists leilao cascade;

create schema leilao;

create table if not exists leilao.auction (
    auction_id uuid,
    start_date timestamptz,
    end_date timestamptz,
    min_increment numeric,
    start_amount numeric
);

create table if not exists leilao.bid (
    bid_id uuid,
    auction_id uuid,
    customer text,
    amount numeric,
    date timestamptz
);
