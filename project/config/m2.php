<?php

// M2 (Assets) tunables. MD-05: placeholder ("dummy") serial values rejected by
// BR-M2-02, compared after normalization (trim, collapse spaces, upper-case).
// The documents say this list is chairman-configurable but M1 has no settings key
// for it; until one is approved it lives here (see M2 report: OPEN DECISION).
return [
    'placeholder_serials' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('M2_PLACEHOLDER_SERIALS', 'N/A,NA,NONE,NULL,UNKNOWN,TBD,-,0,00000000,123456789'))
    ), fn ($v) => $v !== '')),
];
