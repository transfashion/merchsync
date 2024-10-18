<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;
use AgungDhewe\PhpSqlUtil\SqlUpdate;
use AgungDhewe\PhpSqlUtil\SqlSelect;

class SyncSaldo extends SyncBase {

	private object $cmd_heinvitem_cek;
	private object $cmd_heinv_cek;

	private object $cmd_heinvitem_ins;
	private object $cmd_heinv_ins;
	
	private object $cmd_heinvitem_upd;
	private object $cmd_heinv_upd;

	private object $stmt_heinv_list;
	private object $stmt_heinv_get;

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
			$endpoint = $this->TransBrowserUrl . '/getheinv.php?region_id='. $region_id;
			log::info("get data from $endpoint");
			$data = $this->getDataFromUrl($endpoint);

			foreach ($data as $row) {
				// print_r($row);
				$heinvitem_id = $row['heinvitem_id'];
				log::info("copy heinvitem $region_id $heinvitem_id");
				$this->AddOrUpdateTempHeinvItem($heinvitem_id, $row);
			}

			// ambil data heinv
			if (!isset($this->stmt_heinv_list)) {
				$sql = "select distinct heinv_id from tmp_heinvitem where region_id = :region_id";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_heinv_list = $stmt;
			}
			$stmt = $this->stmt_heinv_list;
			$stmt->execute([":region_id" => $region_id]);
			$rows = $stmt->fetchAll();
			foreach ($rows as $row) {
				$heinv_id = $row['heinv_id'];
				if (!isset($this->stmt_heinv_get)) {
					$sql = "select * from tmp_heinvitem where heinv_id = :heinv_id limit 1";
					$stmt = Database::$DbReport->prepare($sql);
					$this->stmt_heinv_get = $stmt;
				}
				$stmt = $this->stmt_heinv_get;
				$stmt->execute([":heinv_id" => $heinv_id]);
				$row = $stmt->fetch();

				log::info("copy heinv $region_id $heinv_id");
				$this->AddOrUpdateTempHeinv($heinv_id, $row);
			}

		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

	private function AddOrUpdateTempHeinvItem(string $heinvitem_id, array $row) : void {
		try {
			$obj = $this->createObjectHeinvitem($row);

			if (!isset($this->cmd_heinvitem_ins)) {
				$this->cmd_heinvitem_ins = new SqlInsert("tmp_heinvitem", $obj);
				$this->cmd_heinvitem_ins->bind(Database::$DbReport);
			}
			
			if (!isset($this->cmd_heinvitem_upd)) {
				$this->cmd_heinvitem_upd = new SqlUpdate("tmp_heinvitem", $obj, ['heinvitem_id']);
				$this->cmd_heinvitem_upd->bind(Database::$DbReport);
			}

			$cek = new \stdClass;
			$cek->heinvitem_id = $heinvitem_id;
			if (!isset($this->cmd_heinvitem_cek)) {
				$this->cmd_heinvitem_cek = new SqlSelect("tmp_heinvitem", $cek);
				$this->cmd_heinvitem_cek->bind(Database::$DbReport);
			}
			$this->cmd_heinvitem_cek->execute($cek);
			$row = $this->cmd_heinvitem_cek->fetch();
			if (empty($row)) {
				// insert
				$this->cmd_heinvitem_ins->execute($obj);
			} else {
				// update
				$this->cmd_heinvitem_upd->execute($obj);
			}



		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	private function AddOrUpdateTempHeinv(string $heinv_id, array $row) : void {
		try {
			$obj = $this->createObjectHeinv($row);

			if (!isset($this->cmd_heinv_ins)) {
				$this->cmd_heinv_ins = new SqlInsert("tmp_heinv", $obj);
				$this->cmd_heinv_ins->bind(Database::$DbReport);
			}
			
			if (!isset($this->cmd_heinv_upd)) {
				$this->cmd_heinv_upd = new SqlUpdate("tmp_heinv", $obj, ['heinv_id']);
				$this->cmd_heinv_upd->bind(Database::$DbReport);
			}


			$cek = new \stdClass;
			$cek->heinv_id = $heinv_id;
			if (!isset($this->cmd_heinv_cek)) {
				$this->cmd_heinv_cek = new SqlSelect("tmp_heinv", $cek);
				$this->cmd_heinv_cek->bind(Database::$DbReport);
			}
			$this->cmd_heinv_cek->execute($cek);
			$row = $this->cmd_heinv_cek->fetch();
			if (empty($row)) {
				// insert
				$this->cmd_heinv_ins->execute($obj);
			} else {
				// update
				$this->cmd_heinv_upd->execute($obj);
			}

		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public function createObjectHeinv(array $row) : object {
		$obj = new \stdClass;
		$obj->heinv_id = $row['heinv_id'];
		$obj->heinv_art = $row['heinv_art'];
		$obj->heinv_mat = $row['heinv_mat'];
		$obj->heinv_col = $row['heinv_col'];
		$obj->heinv_name = $row['heinv_name'];
		$obj->heinv_descr = $row['heinv_descr'];
		$obj->heinv_gtype = $row['heinv_gtype'];
		$obj->heinv_isdisabled = $row['heinv_isdisabled'];
		$obj->heinv_isnonactive = $row['heinv_isnonactive'];
		$obj->heinv_iskonsinyasi = $row['heinv_iskonsinyasi'];
		$obj->heinv_isassembly = $row['heinv_isassembly'];
		$obj->heinv_priceori = $row['heinv_priceori'];
		$obj->heinv_price01 = $row['heinv_price01'];
		$obj->heinv_pricedisc01 = $row['heinv_pricedisc01'];
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
		$obj->heinv_lastrvid = $row['heinv_lastrvid'];
		$obj->heinv_lastrvdate = $row['heinv_lastrvdate'];
		$obj->heinv_lastrvqty = $row['heinv_lastrvqty'];
		$obj->heinv_lastpriceid = $row['heinv_lastpriceid'];
		$obj->heinv_lastpricedate = $row['heinv_lastpricedate'];
		$obj->heinv_lastcost = $row['heinv_lastcost'];
		$obj->heinv_lastcostid = $row['heinv_lastcostid'];
		$obj->heinv_lastcostdate = $row['heinv_lastcostdate'];
		$obj->heinvgro_id = $row['heinvgro_id'];
		$obj->heinvctg_id = $row['heinvctg_id'];
		$obj->heinv_group1 = $row['heinv_group1'];
		$obj->heinv_group2 = $row['heinv_group2'];
		$obj->heinv_gender = $row['heinv_gender'];
		$obj->heinv_color1 = $row['heinv_color1'];
		$obj->heinv_color2 = $row['heinv_color2'];
		$obj->heinv_color3 = $row['heinv_color3'];
		$obj->heinv_hscode_ship = $row['heinv_hscode_ship'];
		$obj->heinv_plbname = $row['heinv_plbname'];
		$obj->ref_id = $row['ref_id'];
		$obj->season_id = $row['season_id'];
		$obj->region_id = $row['region_id'];
		$obj->invcls_id = $row['invcls_id'];
		$obj->heinv_isweb = $row['heinv_isweb'];
		$obj->heinv_weight = $row['heinv_weight'];
		$obj->heinv_length = $row['heinv_length'];
		$obj->heinv_width = $row['heinv_width'];
		$obj->heinv_height = $row['heinv_height'];
		$obj->heinv_webdescr = $row['heinv_webdescr'];
		$obj->deftype_id = $row['deftype_id'];
		$obj->ref_heinv_id = $row['ref_heinv_id'];
		$obj->ref_heinvitem_id = $row['ref_heinvitem_id'];
		

		return $obj;
	}
	
	public function createObjectHeinvitem(array $row) : object {
		$obj = new \stdClass;
		$obj->heinvitem_id = $row['heinvitem_id'];
		$obj->heinv_barcode = $row['heinv_barcode'];
		$obj->heinv_size = $row['heinv_size'];
		$obj->heinv_id = $row['heinv_id'];
		$obj->heinv_art = $row['heinv_art'];
		$obj->heinv_mat = $row['heinv_mat'];
		$obj->heinv_col = $row['heinv_col'];
		$obj->heinv_name = $row['heinv_name'];
		$obj->heinv_descr = $row['heinv_descr'];
		$obj->heinv_gtype = $row['heinv_gtype'];
		$obj->heinv_isdisabled = $row['heinv_isdisabled'];
		$obj->heinv_isnonactive = $row['heinv_isnonactive'];
		$obj->heinv_iskonsinyasi = $row['heinv_iskonsinyasi'];
		$obj->heinv_isassembly = $row['heinv_isassembly'];
		$obj->heinv_priceori = $row['heinv_priceori'];
		$obj->heinv_price01 = $row['heinv_price01'];
		$obj->heinv_pricedisc01 = $row['heinv_pricedisc01'];
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
		$obj->heinv_lastrvid = $row['heinv_lastrvid'];
		$obj->heinv_lastrvdate = $row['heinv_lastrvdate'];
		$obj->heinv_lastrvqty = $row['heinv_lastrvqty'];
		$obj->heinv_lastpriceid = $row['heinv_lastpriceid'];
		$obj->heinv_lastpricedate = $row['heinv_lastpricedate'];
		$obj->heinv_lastcost = $row['heinv_lastcost'];
		$obj->heinv_lastcostid = $row['heinv_lastcostid'];
		$obj->heinv_lastcostdate = $row['heinv_lastcostdate'];
		$obj->heinvgro_id = $row['heinvgro_id'];
		$obj->heinvctg_id = $row['heinvctg_id'];
		$obj->heinv_group1 = $row['heinv_group1'];
		$obj->heinv_group2 = $row['heinv_group2'];
		$obj->heinv_gender = $row['heinv_gender'];
		$obj->heinv_color1 = $row['heinv_color1'];
		$obj->heinv_color2 = $row['heinv_color2'];
		$obj->heinv_color3 = $row['heinv_color3'];
		$obj->heinv_hscode_ship = $row['heinv_hscode_ship'];
		$obj->heinv_plbname = $row['heinv_plbname'];
		$obj->ref_id = $row['ref_id'];
		$obj->season_id = $row['season_id'];
		$obj->region_id = $row['region_id'];
		$obj->invcls_id = $row['invcls_id'];
		$obj->heinv_isweb = $row['heinv_isweb'];
		$obj->heinv_weight = $row['heinv_weight'];
		$obj->heinv_length = $row['heinv_length'];
		$obj->heinv_width = $row['heinv_width'];
		$obj->heinv_height = $row['heinv_height'];
		$obj->heinv_webdescr = $row['heinv_webdescr'];
		$obj->deftype_id = $row['deftype_id'];
		$obj->ref_heinv_id = $row['ref_heinv_id'];
		$obj->ref_heinvitem_id = $row['ref_heinvitem_id'];
		

		return $obj;
	}

}