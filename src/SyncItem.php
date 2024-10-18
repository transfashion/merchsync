<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;
use AgungDhewe\PhpSqlUtil\SqlUpdate;
use AgungDhewe\PhpSqlUtil\SqlSelect;

class SyncItem extends SyncBase {

	private object $stmt_heinv_select;
	private object $stmt_heinvitem_select;

	private object $stmt_kategori_list;

	function __construct() {
		parent::__construct();
	}

	public function Setup(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'SETUP';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$region_id = $id;

			log::info("setup heinv $region_id");
			if (!isset($this->stmt_heinv_select)) {
				$sql = "select * from tmp_heinv where region_id = :region_id";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_heinv_select = $stmt;
			}

			if (!isset($this->stmt_heinvitem_select)) {
				$sql = "select heinvitem_id, heinv_barcode, heinv_size from tmp_heinvitem where heinv_id = :heinv_id";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_heinvitem_select = $stmt;
			}

			$stmt = $this->stmt_heinv_select;
			$stmt->execute([":region_id" => $region_id]);
			$rows = $stmt->fetchAll();
			foreach ($rows as $row) {
				$heinv_id = $row['heinv_id'];
				log::info("setup heinv $heinv_id");
				$this->SetupMerchArticle($heinv_id, $row);

				$stmt = $this->stmt_heinvitem_select;
				$stmt->execute([":heinv_id" => $heinv_id]);
				$item_rows = $stmt->fetchAll();
				foreach ($item_rows as $item_row) {
					$heinvitem_id = $item_row['heinvitem_id'];
					$heinv_barcode = $item_row['heinv_barcode'];
					$heinv_size = $item_row['heinv_size'];
					$row['heinvitem_id'] = $heinvitem_id;
					$row['heinv_barcode'] = $heinv_barcode;
					$row['heinv_size'] = $heinv_size;

					log::info("setup heinvitem $heinvitem_id");
					$this->SetupMerchItem($heinvitem_id, $row);
				}
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}	


	private function SetupMerchArticle(string $mercharticle_id, array $row) : void {
		try {

		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	private function SetupMerchItem(string $merchitem_id, array $row) : void {	
		try {

		} catch (\Exception $ex) {
			throw $ex;
		}
	}


	public function CekData(string $batchid, string $region_id) : void {
		try {
			

			$this->cekKategori($region_id);

			// season

			// rekanan

			// dll

		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	private function cekKategori(string $region_id) : void {
		log::info("cek kategori $region_id");
		try {
			if (!isset($this->stmt_kategori_list)) {
				$sql = "select distinct heinvctg_id from tmp_heinvitem where region_id = :region_id";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_kategori_list = $stmt;
			}
			$stmt = $this->stmt_kategori_list;
			$stmt->execute([":region_id" => $region_id]);
			$rows = $stmt->fetchAll();
			foreach ($rows as $row) {
				$heinvctg_id = $row['heinvctg_id'];
				echo "$heinvctg_id\n";
			}

		} catch (\Exception $ex) {
			throw $ex;
		}
	}


}