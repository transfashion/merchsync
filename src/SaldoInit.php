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
			$syncItem = new SyncItem();

			// '02600',
			$regions = ['00900'];
			$batchid = uniqid();
			foreach ($regions as $region_id) {
				// $syncSaldo->Setup($batchid, $region_id, 'SETUP');
			}

			reset($regions);
			foreach ($regions as $region_id) {
				$syncItem->CekData($batchid, $region_id);
			}

			reset($regions);
			foreach ($regions as $region_id) {
				// $syncItem->Setup($batchid, $region_id, 'SETUP');
			}



			

			Log::info('DONE.');
		} catch (\Exception $e) {
			Log::error('PROCESS ERROR! ' . $e->getMessage());
			throw $e;
		}
	}


}