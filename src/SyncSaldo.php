<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;
use AgungDhewe\PhpSqlUtil\SqlUpdate;
use AgungDhewe\PhpSqlUtil\SqlSelect;

class SyncSaldo extends SyncBase {


	private object $stmt_saldo_del;

	private object $cmd_heinvsaldo_ins;

	private object $cmd_heinvitem_cek;
	private object $cmd_heinv_cek;

	private object $cmd_heinvitem_ins;
	private object $cmd_heinv_ins;
	
	private object $cmd_heinvitem_upd;
	private object $cmd_heinv_upd;

	private object $cmd_heinvitemsaldo_ins;

	private object $stmt_heinv_list;
	private object $stmt_heinv_get;
	private object $stmt_heinvitemsaldo_del;


	private object $stmt_heinvsaldo_list;

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


	public function GetStock(string $merchsync_id, string $merchsync_doc, string $merchsync_type, string $periode_id ) : void {
		$currentSyncType = 'GET-STOCK';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}


			$region_id = $id;
			$endpoint = $this->TransBrowserUrl . '/getstock.php?region_id='. $region_id . '&periode_id=' . $periode_id;
			log::info("get data from $endpoint");
			$data = $this->getDataFromUrl($endpoint);

			if (!isset($this->stmt_saldo_del)) {
				$sql = "delete from tmp_heinvsaldo where region_id=:region_id and periode_id=:periode_id";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_saldo_del = $stmt;	
			}

			$stmt = $this->stmt_saldo_del;
			$stmt->execute([":region_id" => $region_id, ":periode_id" => $periode_id]);
		
			foreach ($data as $row) {
				$row['region_id'] = $region_id;
				$row['periode_id'] = $periode_id;

				$obj = $this->createObjectHeinvsaldo($row);
				if (!isset($this->cmd_heinvsaldo_ins)) {
					$this->cmd_heinvsaldo_ins = new SqlInsert("tmp_heinvsaldo", $obj);
					$this->cmd_heinvsaldo_ins->bind(Database::$DbReport);
				}
				$this->cmd_heinvsaldo_ins->execute($obj);
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}


	public function PrepareItemSaldo(string $merchsync_id, string $merchsync_doc, string $merchsync_type, string $periode_id ) : void {
		$currentSyncType = 'PREPARE-ITEM-SALDO';

		try {
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}


			$region_id = $id;
		
			// hapus item saldo
			if (!isset($this->stmt_heinvitemsaldo_del)) {
				$sql = "delete from tmp_heinvitemsaldo where region_id = :region_id and periode_id = :periode_id";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_heinvitemsaldo_del = $stmt;
			}
			$stmt = $this->stmt_heinvitemsaldo_del;
			$stmt->execute([":region_id" => $region_id, ":periode_id" => $periode_id]);


			// ambil data heinvsaldo
			if (!isset($this->stmt_heinvsaldo_list)) {
				$sql = "select * from tmp_heinvsaldo where region_id = :region_id and periode_id = :periode_id";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_heinvsaldo_list = $stmt;
			}

			$stmt = $this->stmt_heinvsaldo_list;
			$stmt->execute([":region_id" => $region_id, ":periode_id" => $periode_id]);
			$rows = $stmt->fetchall();
			foreach ($rows as $row) {

				$saldo_id = $row['saldo_id'];
				$branch_id = substr($saldo_id, 15, 7);
				$row['region_id'] = $region_id;
				$row['periode_id'] = $periode_id;
				$row['branch_id'] = $branch_id;

				$costperitem = 0;
				$saldo_qty = (int) $row['saldodetil_end'];
				$saldo_value = (float) $row['saldodetil_endvalue'];
				if ($saldo_qty!=0) {
					$costperitem = $saldo_value / $saldo_qty;
				}

				$heinv_id = $row['heinv_id'];
				for ($i=1; $i<=25; $i++) {
					$colnum = str_pad($i, 2, "0", STR_PAD_LEFT);
					$colname = "C$colnum";
					$end_qty = $row[$colname];
					$end_value = $costperitem * $end_qty;
					$heinvitem_id = substr($heinv_id, 0, 11) . $colnum ;

					if ($end_qty==0) {
						continue;
					}

					$saldoitem = [
						'periode_id' => $periode_id,
						'region_id' => $region_id,
						'branch_id' => $branch_id,
						'heinv_id' => $heinv_id,
						'heinvitem_id' => $heinvitem_id,
						'end_qty' => $end_qty,
						'end_value' => $end_value,
						'cost' => $costperitem
					];

					$obj = $this->createObjectHeinvitemSaldo($saldoitem);
					if (!isset($this->cmd_heinvitemsaldo_ins)) {
						$this->cmd_heinvitemsaldo_ins = new SqlInsert("tmp_heinvitemsaldo", $obj);
						$this->cmd_heinvitemsaldo_ins->bind(Database::$DbReport);
					}
					$this->cmd_heinvitemsaldo_ins->execute($obj);
				}
			}
			
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
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
		$obj->heinv_produk = $row['heinv_produk'];
		$obj->heinv_bahan = $row['heinv_bahan'];
		$obj->heinv_pemeliharaan = $row['heinv_pemeliharaan'];
		$obj->heinv_logo = $row['heinv_logo'];
		$obj->heinv_dibuatdi = $row['heinv_dibuatdi'];
		$obj->fit = $row['fit'];
		$obj->pcp_line = $row['pcp_line'];
		$obj->pcp_gro = $row['pcp_gro'];
		$obj->pcp_ctg = $row['pcp_ctg'];
		$obj->heinv_lastrvid = $row['heinv_lastrvid'];
		$obj->heinv_lastrvdate = $row['heinv_lastrvdate'];
		$obj->heinv_lastrvqty = $row['heinv_lastrvqty'];
		$obj->rekanan_id = $row['rekanan_id'];
		$obj->heinv_lastpriceid = $row['heinv_lastpriceid'];
		$obj->heinv_lastpricedate = $row['heinv_lastpricedate'];
		$obj->heinv_lastcost = $row['heinv_lastcost'];
		$obj->heinv_lastcostid = $row['heinv_lastcostid'];
		$obj->heinv_lastcostdate = $row['heinv_lastcostdate'];
		$obj->heinvgro_id = $row['heinvgro_id'];
		$obj->heinvctg_id = $row['heinvctg_id'];
		$obj->heinv_gender = $row['heinv_gender'];
		$obj->heinv_coldescr = $row['heinv_coldescr'];
		$obj->heinv_hscode_ship = $row['heinv_hscode_ship'];
		$obj->heinv_label = $row['heinv_label'];
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
		$obj->priceori = $row['priceori'];
		$obj->priceadj = $row['priceadj'];
		$obj->pricegross = $row['pricegross'];
		$obj->price = $row['price'];
		$obj->pricedisc = $row['pricedisc'];
		$obj->pricenett = $row['pricenett'];
		$obj->discflag = $row['discflag'];

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
		$obj->heinv_produk = $row['heinv_produk'];
		$obj->heinv_bahan = $row['heinv_bahan'];
		$obj->heinv_pemeliharaan = $row['heinv_pemeliharaan'];
		$obj->heinv_logo = $row['heinv_logo'];
		$obj->heinv_dibuatdi = $row['heinv_dibuatdi'];
		$obj->fit = $row['fit'];
		$obj->pcp_line = $row['pcp_line'];
		$obj->pcp_gro = $row['pcp_gro'];
		$obj->pcp_ctg = $row['pcp_ctg'];
		$obj->heinv_lastrvid = $row['heinv_lastrvid'];
		$obj->heinv_lastrvdate = $row['heinv_lastrvdate'];
		$obj->heinv_lastrvqty = $row['heinv_lastrvqty'];
		$obj->rekanan_id = $row['rekanan_id'];
		$obj->heinv_lastpriceid = $row['heinv_lastpriceid'];
		$obj->heinv_lastpricedate = $row['heinv_lastpricedate'];
		$obj->heinv_lastcost = $row['heinv_lastcost'];
		$obj->heinv_lastcostid = $row['heinv_lastcostid'];
		$obj->heinv_lastcostdate = $row['heinv_lastcostdate'];
		$obj->heinvgro_id = $row['heinvgro_id'];
		$obj->heinvctg_id = $row['heinvctg_id'];
		$obj->heinv_gender = $row['heinv_gender'];
		$obj->heinv_coldescr = $row['heinv_coldescr'];
		$obj->heinv_hscode_ship = $row['heinv_hscode_ship'];
		$obj->heinv_label = $row['heinv_label'];
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
		$obj->priceori = $row['priceori'];
		$obj->priceadj = $row['priceadj'];
		$obj->pricegross = $row['pricegross'];
		$obj->price = $row['price'];
		$obj->pricedisc = $row['pricedisc'];
		$obj->pricenett = $row['pricenett'];
		$obj->discflag = $row['discflag'];
		return $obj;
	}


	private function createObjectHeinvsaldo(array $row) : object {
		$obj = new \stdClass;
		$obj->saldo_id = $row['saldo_id'];
		$obj->heinv_id = $row['heinv_id'];
		$obj->C01 = $row['C01'];
		$obj->C02 = $row['C02'];
		$obj->C03 = $row['C03'];
		$obj->C04 = $row['C04'];
		$obj->C05 = $row['C05'];
		$obj->C06 = $row['C06'];
		$obj->C07 = $row['C07'];
		$obj->C08 = $row['C08'];
		$obj->C09 = $row['C09'];
		$obj->C10 = $row['C10'];
		$obj->C11 = $row['C11'];
		$obj->C12 = $row['C12'];
		$obj->C13 = $row['C13'];
		$obj->C14 = $row['C14'];
		$obj->C15 = $row['C15'];
		$obj->C16 = $row['C16'];
		$obj->C17 = $row['C17'];
		$obj->C18 = $row['C18'];
		$obj->C19 = $row['C19'];
		$obj->C20 = $row['C20'];
		$obj->C21 = $row['C21'];
		$obj->C22 = $row['C22'];
		$obj->C23 = $row['C23'];
		$obj->C24 = $row['C24'];
		$obj->C25 = $row['C25'];
		$obj->saldodetil_endvalue = $row['saldodetil_endvalue'];
		$obj->saldodetil_end = $row['saldodetil_end'];
		$obj->periode_id = $row['periode_id'];
		$obj->region_id = $row['region_id'];
		return $obj;
	}


	private function createObjectHeinvitemSaldo(array $row) : object {
		$obj = new \stdClass;
		$obj->periode_id = $row['periode_id'];
		$obj->region_id = $row['region_id'];
		$obj->branch_id = $row['branch_id'];
		$obj->heinv_id = $row['heinv_id'];
		$obj->heinvitem_id = $row['heinvitem_id'];
		$obj->end_qty = $row['end_qty'];
		$obj->end_value = $row['end_value'];
		$obj->cost = $row['cost'];
		return $obj;
	}
}