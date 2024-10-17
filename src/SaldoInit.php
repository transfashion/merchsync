<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

class SaldoInit {
	const _DELAY_BETWEEN_LOOP = 0; // seconds
	const _MAX_TX_PER_LOOP = 10;


	public static function main() : void {	
		try {
			Log::info('Initialize Saldo Inventory starting');
			Database::Connect();

			$syncSaldo = new SyncSaldo();

			// '02600',
			$regions = ['00700', '00900',  '03400', '03700', '03800', '03900', '04000', '04210'];
			foreach ($regions as $region_id) {
				$batchid = uniqid();
				$syncSaldo->Sync($batchid, $region_id, 'SALDO');
			}

			Log::info('DONE.');
		} catch (\Exception $e) {
			Log::error('PROCESS ERROR! ' . $e->getMessage());
			throw $e;
		}
	}


}