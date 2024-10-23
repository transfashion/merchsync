<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

class SaldoInit {
	const _DELAY_BETWEEN_LOOP = 0; // seconds
	const _MAX_TX_PER_LOOP = 10;


	public static function main() : void {	
		try {
			Log::info('Initialize Saldo Inventory starting');
			Database::Connect();

			$periode = '20240930';

			$syncSaldo = new SyncSaldo();
			$syncItem = new SyncItem();

			// '02600',
			$regions = [
				'00700',
				'00900',
				'01100',
				'01110',
				'01130',
				'01400',
				'01500',
				'01510',
				'01800',
				'02500',
				'02600',
				'03400',
				'03700',
				'03800',
				'03900',
				'04000',
				'04210'
			];

			$batchid = uniqid();
			foreach ($regions as $region_id) {
				log::print("setup region $region_id");
				// $syncSaldo->Setup($batchid, $region_id, 'SETUP');
			}

			reset($regions);
			foreach ($regions as $region_id) {
				log::print("get stock $periode region $region_id");
				// $syncSaldo->GetStock($batchid, $region_id, 'GET-STOCK', $periode);
			}


			// Cek Data dahulu sebelum lanjut proses ke main database
			reset($regions);
			foreach ($regions as $region_id) {
				log::print("cek mapping data region $region_id");
				$syncItem->CekData($batchid, $region_id, $periode);
			}
			$errormapping = $syncItem->getMappingErrorString();
			if ($errormapping!="") {
				// ada error maping
				log::error("MAPPIG ERROR: " . $errormapping);
				throw new \Exception('Error Mapping ' . $errormapping);
			}


			reset($regions);
			foreach ($regions as $region_id) {
				log::print("prepare $region_id regionbranch item saldo");
				$syncSaldo->PrepareItemSaldo($batchid, $region_id, 'PREPARE-ITEM-SALDO', $periode);
			}


			/*
			reset($regions);
			foreach ($regions as $region_id) {
				log::print("setup item region $region_id");
				// $syncItem->Setup($batchid, $region_id, 'SETUP');
			}



			reset($regions);
			foreach ($regions as $region_id) {
				log::print("apply saldo periode $periode region $region_id");
				// $syncItem->ApplySaldo($batchid, $region_id, 'APPLY-SALDO', $periode);
			}
			*/
			

			Log::info('DONE.');
		} catch (\Exception $e) {
			Log::error('PROCESS ERROR! ' . $e->getMessage());
			throw $e;
		}
	}


}