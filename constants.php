<?php
define('BLOCK_SIZE', 100);
define('V_BLOCK_SIZE', 10000);

//Status Codes
define('STATUS_COMPLETE', 'Sync process finished successfully');
define('STATUS_PENDING', 'Sync process not started');
define('STATUS_PARTIAL', 'Sync process finished with some unprocessed dirty rows');
define('STATUS_FAILED', 'Sync process Failed');
define('STATUS_SKIP', 'Sync process skipped because source and destination rows are equal');
