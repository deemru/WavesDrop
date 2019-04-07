<?php

// CHAINID: 'T' = TestNet, 'W' = MainNet
$chainId = 'T';
// SEED: your seed phrase
$seed = 'manage manual recall harvest series desert melt police rose hollow moral pledge kitten position add';
// ASSET: asset to transfer or null
$asset = '8XesECmKryZQiYsUxPY1SJFoUjNh2K7CtaxCKeCoWAWD';
// ATTACHMENT: attachment string
$attachment = 'WavesDrop';

// JSON LIST
if( 1 )
{
    $list = '
    {
        "3MpVgjqF5T993fdzwbw8G9uC5A199UCipDM": 1147,
        "3N2NK5u3s8Nq1LPXFvWtzeaFE68Lmm1djfe": 8809,
        "3NBGKS1YrgTRuw9vcyurCMrBh5TAVhRxcNX": 705,
        "3N5KSu7A9mZNgnWX6NQX8D6ya4q9NTz63MF": 4795,
        "3N95AKSUFhiQShicg9jhoz59PRdXxXkv2Qv": 1968,
        "3MsEAnN1sR9GA6X942vXkKSo8WyeH7EpR7v": 3787,
        "3Mu3vuWbcCjHmuvYyP3q47FsJoLCmHumSYu": 359,
        "3N4QYDY3uC9suEiey29s5kTguJiNC13evcQ": 4703
    }
    ';
    $list = json_decode( $list, true, 512, JSON_BIGINT_AS_STRING );
}
else
{
    // CSV LIST
    $list = '
        3MpVgjqF5T993fdzwbw8G9uC5A199UCipDM, 1147
        3N2NK5u3s8Nq1LPXFvWtzeaFE68Lmm1djfe, 8809
        3NBGKS1YrgTRuw9vcyurCMrBh5TAVhRxcNX, 705
        3N5KSu7A9mZNgnWX6NQX8D6ya4q9NTz63MF, 4795
        3N95AKSUFhiQShicg9jhoz59PRdXxXkv2Qv, 1968
        3MsEAnN1sR9GA6X942vXkKSo8WyeH7EpR7v, 3787
        3Mu3vuWbcCjHmuvYyP3q47FsJoLCmHumSYu, 359
        3N4QYDY3uC9suEiey29s5kTguJiNC13evcQ, 4703
    ';
    $nl = '
    ';
    $listnl = explode( $nl, $list );
    $list = [];
    foreach( $listnl as $rec )
    {
        $rec = explode( ',', trim( $rec ) );
        if( count( $rec ) === 2 )
            $list[trim( $rec[0] )] = (int)trim( $rec[1] );
    }
}

if( $chainId === 'W' )
{
    $nodes =
    [
        'https://nodes.wavesnodes.com',
    ];
}
else
{
    $nodes =
    [
        'https://testnode1.wavesnodes.com',
        'https://testnode2.wavesnodes.com',
        'https://testnode3.wavesnodes.com',
        'https://testnode4.wavesnodes.com',
    ];
}
