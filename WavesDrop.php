<?php

require_once __DIR__ . '/vendor/autoload.php';
use deemru\WavesKit;

if( file_exists( __DIR__ . '/config.php' ) )
    require_once __DIR__ . '/config.php';
else
    require_once __DIR__ . '/config.sample.php';

$wk = new WavesKit( $chainId );
$wk->setNodeAddress( $nodes[0], 1, array_slice( $nodes, 1 ) );
$wk->setSeed( $seed );
$wk->log( 's', 'WavesDrop @ ' . $wk->getAddress() );
$wk->setBestNode();
$wk->log( 'i', 'best node = ' . $wk->getNodeAddress() );

function prepareDrop( WavesKit $wk, $asset, $list, $attachment )
{
    $attachment = $wk->base58Encode( $attachment );
    $txs = [];
    $fee100 = 0;

    $recipients = [];
    $amounts = [];
    $n = 0;

    foreach( $list as $address => $amount )
    {
        $recipients[] = $address;
        $amounts[] = $amount;

        if( ++$n == 100 )
        {
            $tx = $wk->txMass( $recipients, $amounts, $asset, [ 'attachment' => $attachment ] );

            if( !$fee100 )
                $fee100 = $wk->calculateFee( $tx );
            if( $fee100 === false )
                exit( $wk->log( 'e', 'calculateFee() error' ) );

            $tx['fee'] = $fee100;
            $txs[] = $tx;

            $recipients = [];
            $amounts = [];
            $n = 0;
        }
    }

    if( count( $recipients ) )
    {
        $tx = $wk->txMass( $recipients, $amounts, $asset, [ 'attachment' => $attachment ] );
        $tx['fee'] = $wk->calculateFee( $tx );
        if( $tx['fee'] === false )
            exit( $wk->log( 'e', 'calculateFee() error' ) );
        $txs[] = $tx;
    }

    return $txs;
}

$balance = $wk->balance();
if( isset( $asset ) && !isset( $balance[$asset] ) )
    exit( $wk->log( 'e', "No asset ($asset) on your balance" ) );

$txs = prepareDrop( $wk, isset( $asset ) ? $asset : null, $list, $attachment );

$totalFee = 0;
$totalAmount = 0;

$asset = isset( $txs[0]['assetId'] ) ? $txs[0]['assetId'] : 0;

foreach( $txs as $tx )
{
    $totalFee += $tx['fee'];
    foreach( $tx['transfers'] as $rec )
        $totalAmount += $rec['amount'];
}

$wk->log( 'i', '---' );
$wk->log( 'i', count( $list ) . ' addresses in list' );

if( $asset )
{
    $wavesBalance = $balance[0]['balance'];
    $assetBalance = $balance[$asset]['balance'];
    $decimals = $balance[$asset]['issueTransaction']['decimals'];
    $name = $balance[$asset]['issueTransaction']['name'];

    $afterFee = $wavesBalance - $totalFee;
    $afterBalance = $assetBalance - $totalAmount;

    $wk->log( 'i', sprintf( "%s $name will be send",
        number_format( $totalAmount / pow( 10, $decimals ), $decimals, '.', '' ) ) );
    $wk->log( 'i', '---' );

    $wk->log( 'i', sprintf( "%s Waves = %s (your) - %s (fee)",
        number_format( $afterFee / 100000000, 8, '.', '' ),
        number_format( $wavesBalance / 100000000, 8, '.', '' ),
        number_format( $totalFee / 100000000, 8, '.', '' ) ) );
    $wk->log( 'i', sprintf( "%s $name = %s (your) - %s (amount)",
        number_format( $afterBalance / pow( 10, $decimals ), $decimals, '.', '' ),
        number_format( $assetBalance / pow( 10, $decimals ), $decimals, '.', '' ),
        number_format( $totalAmount / pow( 10, $decimals ), $decimals, '.', '' ) ) );
}
else
{
    $wavesBalance = $balance[0]['balance'];

    $decimals = 8;
    $name = 'Waves';

    $afterFee = 0;
    $afterBalance = $wavesBalance - $totalFee - $totalAmount;

    $wk->log( 'i', sprintf( "%s Waves will be send",
        number_format( $totalAmount / 100000000, 8, '.', '' ) ) );
    $wk->log( 'i', '---' );

    $wk->log( 'i', sprintf( "%s Waves = %s (your) - %s (fee) - %s (amount)",
        number_format( $afterBalance / 100000000, 8, '.', '' ),
        number_format( $wavesBalance / 100000000, 8, '.', '' ),
        number_format( $totalFee / 100000000, 8, '.', '' ),
        number_format( $totalAmount / 100000000, 8, '.', '' ) ) );
}

if( $afterFee < 0 || $afterBalance < 0 )
    exit( $wk->log( 'e', sprintf( "Not enough balance" ) ) );

$wk->log( 'i', '---' );
$wk->log( 'w', 'press Ctrl + C to abort' );
$wk->log( 'i', 'sleeping for 10 seconds...' );
sleep( 10 );

function ensure( $wk, $txs, &$errors )
{
    foreach( $txs as $tx )
    {
        $result = $wk->ensure( $tx );
        if( $result === false )
            $errors[] = $tx;
    }
}

function mass( WavesKit $wk, $txs, &$errors, $sign = true )
{
    $n = 0;
    $sent = [];
    $total = 0;
    foreach( $txs as $tx )
    {
        if( $sign )
        {
            $tx['timestamp'] = $wk->timestamp();
            $tx = $wk->txSign( $tx );
            $send = true;
        }
        else
        {
            $btx = $wk->getTransactionById( $tx['id'] );
            if( $btx !== false )
            {
                $tx = $btx;
                $send = false;
                $wk->log( 's', sprintf( '%d/%d found (%s)', ++$total, count( $txs ), $tx['id'] ) );
            }
        }

        for( ; $send; )
        {
            $result = $wk->txBroadcast( $tx );
            if( $result !== false )
            {
                $tx = $result;
                $wk->log( 's', sprintf( '%d/%d sent (%s)', ++$total, count( $txs ), $tx['id'] ) );
                break;
            }

            $wk->log( 'i', 'sleeping for 10 seconds...' );
            sleep( 10 );
        }

        $sent[] = $tx;

        if( ++$n == 10 )
        {
            ensure( $wk, $sent, $errors );
            $n = 0;
            $sent = [];
        }
    }

    ensure( $wk, $sent, $errors );
}

$errors = [];
mass( $wk, $txs, $errors );

$final_errors = [];
if( count( $errors ) )
{
    $wk->log( 'w', count( $errors ) . " errors found, retrying..." );
    sleep( 10 );
    mass( $wk, $errors, $final_errors, false );
}

if( count( $final_errors ) )
{
    foreach( $final_errors as $tx )
        $wk->log( 'e', json_encode( $tx, JSON_PRETTY_PRINT ) );
    $wk->log( 'e', count( $final_errors ) . " transaction errors" );
    exit( 1 );
}

$wk->log( 's', count( $txs ) . " transactions done" );
