<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

class SaldoInit {
	const _DELAY_BETWEEN_LOOP = 0; // seconds
	const _MAX_TX_PER_LOOP = 10;


	private static SyncSaldo $syncSaldo;
	private static SyncItem $syncItem;
	

	public static function main() : void {	
		try {
			Log::info('Initialize Saldo Inventory starting');
			Database::Connect();

			$periode = '20240930';

			self::$syncSaldo = new SyncSaldo();
			self::$syncItem = new SyncItem();

			$batchid = uniqid();
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


			//self::getActiveItem($batchid, $regions);							// ambil data master item aktiv dari transbrowser
			//self::getItemStockPeriode($batchid, $regions, $periode);			// ambil data saldo stok pada suatu periode dari TransBrowser
			self::cekDataMapping($batchid, $regions, $periode);					// Cek Data dahulu sebelum lanjut proses ke main database
			//self::PrepareTemporaryItemSaldo($batchid, $regions, $periode);		// siapkan data saldo per item (sizing) di temporary table
			//self::SetupMerchItem($batchid, $regions, $periode);					// setup master itemstock, merchitem, mercharticle di main database
			self::CekPeriodeSaldoMerchItemIntegrity($periode);					// cek heinvitem di tmp_heinvitemsaldo apakah sudah ada semua di merchitem
			self::ApplyItemStockMoving($batchid, $regions, $periode);			// apply saldo dari tmp_heinvitemsaldo ke itemstockmoving main database
			
			Log::info('DONE.');
		} catch (\Exception $e) {
			Log::error('PROCESS ERROR! ' . $e->getMessage());
			throw $e;
		}
	}


	private static function getActiveItem(string $batch_id, array $regions) : void {
		// ambil data master item aktiv dari transbrowser
		foreach ($regions as $region_id) {
			log::print("setup region $region_id");
			self::$syncSaldo->Setup($batch_id, $region_id, 'SETUP');
		}
	}

	private static function getItemStockPeriode(string $batch_id, array $regions, string $periode_id) : void {
		reset($regions);
		foreach ($regions as $region_id) {
			log::print("get stock $periode_id region $region_id");
			self::$syncSaldo->GetStock($batch_id, $region_id, 'GET-STOCK', $periode_id);
		}
	}

	private static function cekDataMapping(string $batch_id, array $regions, string $periode_id) : void {
		reset($regions);
		foreach ($regions as $region_id) {
			log::print("cek mapping data region $region_id");
			self::$syncItem->CekData($batch_id, $region_id, $periode_id);
		}
		$errormapping = self::$syncItem->getMappingErrorString();
		if ($errormapping!="") {
			// ada error maping
			log::error("MAPPIG ERROR: " . $errormapping);
			throw new \Exception('Error Mapping ' . $errormapping);
		}
	}

	private static function PrepareTemporaryItemSaldo(string $batch_id, array $regions, string $periode_id) : void {
		reset($regions);
		foreach ($regions as $region_id) {
			log::print("prepare $region_id regionbranch item saldo");
			self::$syncSaldo->PrepareItemSaldo($batch_id, $region_id, 'PREPARE-ITEM-SALDO', $periode_id);
		}
	}

	private static function SetupMerchItem(string $batch_id, array $regions, string $periode_id) : void {
		reset($regions);
		foreach ($regions as $region_id) {
			log::print("setup item region $region_id");
			self::$syncItem->Setup($batch_id, $region_id, 'SETUP');
		}
	}

	private static function CekPeriodeSaldoMerchItemIntegrity(string $periode_id) : void {
		log::print("cek fsn_merchitem vs saldo di periode $periode_id");
		self::$syncItem->CekHeinvPeriode($periode_id);

	}

	private static function ApplyItemStockMoving(string $batch_id, array $regions, string $periode_id) : void {
		reset($regions);
		foreach ($regions as $region_id) {
			log::print("apply saldo periode $periode_id region $region_id");
			self::$syncItem->ApplySaldo($batch_id, $region_id, 'APPLY-SALDO', $periode_id);
		}
	}

}