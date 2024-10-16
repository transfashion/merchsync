<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;

class SyncRegister extends SyncBase {

	const bool SKIP_SYNC = false;

	const bool UNIMPLEMENTED_SYNC = true;

	private object $cmd_register_header_del;
	private object $cmd_register_items_del;
	private object $cmd_register_header_ins;
	private object $cmd_register_items_ins;


	function __construct() {
		parent::__construct();
	}

	public function Sync(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'REG';
		
		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$endpoint = $this->TransBrowserUrl . '/getregister.php?id='. $id;
			$data = $this->getDataFromUrl($endpoint);

			$heinvregister_id = $data['header']['heinvregister_id'];
			$this->deletePreviousRegisterData($heinvregister_id);
			$this->copyToTempRegisterHeader($heinvregister_id, $data['header']);
			$this->copyToTempRegisterItems($heinvregister_id, $data['items']);

			if (self::UNIMPLEMENTED_SYNC) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

	private function deletePreviousRegisterData($heinvregister_id) : void {
		try {

			$obj = new \stdClass;
			$obj->heinvregister_id = $heinvregister_id;

			if (!isset($this->cmd_register_header_del)) {
				$this->cmd_register_header_del = new SqlDelete("tmp_heinvregister", $obj, ['heinvregister_id']);
				$this->cmd_register_header_del->bind(Database::$DbReport);
			}
			$this->cmd_register_header_del->execute($obj);

			
			if (!isset($this->cmd_register_items_del)) {
				$this->cmd_register_items_del = new SqlDelete("tmp_heinvregisteritem", $obj, ['heinvregister_id']);
				$this->cmd_register_items_del->bind(Database::$DbReport);
			}
			$this->cmd_register_items_del->execute($obj);

		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	private function copyToTempRegisterHeader(string $heinvregister_id,array $row) : void {
		try {
			$obj = $this->createObjectHeader($row);
			if (!isset($this->cmd_register_header_ins)) {
				$this->cmd_register_header_ins = new SqlInsert("tmp_heinvregister", $obj);
				$this->cmd_register_header_ins->bind(Database::$DbReport);
			}
			$this->cmd_register_header_ins->execute($obj);

		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	private function copyToTempRegisterItems(string $heinvregister_id, array $rows) : void {
		try {
			foreach ($rows as $row) {
				$obj = $this->createObjectItem($row);
				if (!isset($this->cmd_register_items_ins)) {
					$this->cmd_register_items_ins = new SqlInsert("tmp_heinvregisteritem", $obj);
					$this->cmd_register_items_ins->bind(Database::$DbReport);
				}
				$this->cmd_register_items_ins->execute($obj);
			}
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	private function createObjectHeader(array $row) : object {
		$obj = new \stdClass;
		$obj->heinvregister_id = $row['heinvregister_id'];
		$obj->heinvregister_date = $row['heinvregister_date'];
		$obj->heinvregister_descr = $row['heinvregister_descr'];
		$obj->heinvregister_issizing = $row['heinvregister_issizing'];
		$obj->heinvregister_isposted = $row['heinvregister_isposted'];
		$obj->heinvregister_isgenerated = $row['heinvregister_isgenerated'];
		$obj->heinvregister_isseasonupdate = $row['heinvregister_isseasonupdate'];
		$obj->heinvregister_createby = $row['heinvregister_createby'];
		$obj->heinvregister_createdate = $row['heinvregister_createdate'];
		$obj->heinvregister_modifyby = $row['heinvregister_modifyby'];
		$obj->heinvregister_modifydate = $row['heinvregister_modifydate'] ?  $row['heinvregister_modifydate'] : null;
		$obj->heinvregister_postby = $row['heinvregister_postby'];
		$obj->heinvregister_postdate = $row['heinvregister_postdate'] ? $row['heinvregister_postdate'] : null;
		$obj->heinvregister_generateby = $row['heinvregister_generateby'];
		$obj->heinvregister_generatedate = $row['heinvregister_generatedate'] ? $row['heinvregister_generatedate'] : null;
		$obj->region_id = $row['region_id'];
		$obj->branch_id = $row['branch_id'];
		$obj->season_id = $row['season_id'];
		$obj->rekanan_id = $row['rekanan_id'];
		$obj->currency_id = $row['currency_id'];
		$obj->rowid = $row['rowid'];
		$obj->heinvregister_type = $row['heinvregister_type'];

		return $obj;
	}

	private function createObjectItem(array $row) : object {
		$obj = new \stdClass;
		$obj->heinvregister_id = $row['heinvregister_id'];
		$obj->heinvregisteritem_line = $row['heinvregisteritem_line'];
		$obj->heinv_id = $row['heinv_id'];
		$obj->heinv_art = $row['heinv_art'];
		$obj->heinv_mat = $row['heinv_mat'];
		$obj->heinv_col = $row['heinv_col'];
		$obj->heinv_size = $row['heinv_size'];
		$obj->heinv_barcode = $row['heinv_barcode'];
		$obj->heinv_name = $row['heinv_name'];
		$obj->heinv_descr = $row['heinv_descr'];
		$obj->heinv_box = $row['heinv_box'];
		$obj->heinv_gtype = $row['heinv_gtype'];
		$obj->heinv_produk = $row['heinv_produk'];
		$obj->heinv_bahan = $row['heinv_bahan'];
		$obj->heinv_pemeliharaan = $row['heinv_pemeliharaan'];
		$obj->heinv_logo = $row['heinv_logo'];
		$obj->heinv_dibuatdi = $row['heinv_dibuatdi'];
		$obj->heinv_other1 = $row['heinv_other1'];
		$obj->heinv_other2 = $row['heinv_other2'];
		$obj->heinv_other3 = $row['heinv_other3'];
		$obj->heinv_other4 = $row['heinv_other4'];
		$obj->heinv_other5 = $row['heinv_other5'];
		$obj->heinv_other6 = $row['heinv_other6'];
		$obj->heinv_other7 = $row['heinv_other7'];
		$obj->heinv_other8 = $row['heinv_other8'];
		$obj->heinv_other9 = $row['heinv_other9'];
		$obj->heinv_plbname = $row['heinv_plbname'];
		$obj->heinvitem_colnum = $row['heinvitem_colnum'];
		$obj->heinvgro_id = $row['heinvgro_id'];
		$obj->heinvctg_id = $row['heinvctg_id'];
		$obj->heinvctg_sizetag = $row['heinvctg_sizetag'];
		$obj->branch_id = $row['branch_id'];
		$obj->C00 = $row['C00'];
		$obj->heinv_isweb = $row['heinv_isweb'];
		$obj->heinv_webdescr = $row['heinv_webdescr'];
		$obj->invcls_id = $row['invcls_id'];
		$obj->heinv_hscode_ship = $row['heinv_hscode_ship'];
		$obj->heinv_hscode_ina = $row['heinv_hscode_ina'];
		$obj->heinv_weight = $row['heinv_weight'];
		$obj->heinv_length = $row['heinv_length'];
		$obj->heinv_width = $row['heinv_width'];
		$obj->heinv_height = $row['heinv_height'];
		$obj->heinv_price = $row['heinv_price'];

		return $obj;
	}


}
