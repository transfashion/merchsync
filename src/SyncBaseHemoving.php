<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;
use AgungDhewe\PhpSqlUtil\SqlUpdate;




abstract class SyncBaseHemoving extends SyncBase {

	private object $cmd_header_del;
	private object $cmd_detil_del;

	private object $cmd_header_ins;
	private object $cmd_detil_ins;




	public function __construct() {
		parent::__construct();
	}


	protected function deleteHemovingData(string $hemoving_id) : void {
		try {
			$obj = new \stdClass;
			$obj->hemoving_id = $hemoving_id;

			// hapus tmp_hemoving
			if (!isset($this->cmd_header_del)) {
				$this->cmd_header_del = new SqlDelete("tmp_hemoving", $obj, ['hemoving_id']);
				$this->cmd_header_del->bind(Database::$DbReport);
			}

			$stmt = $this->cmd_header_del->getPreparedStatement();
			$params = $this->cmd_header_del->getParameter($obj);
			$stmt->execute($params);


			// hapus tmp_hemovingdetil
			if (!isset($this->cmd_detil_del)) {
				$this->cmd_detil_del = new SqlDelete("tmp_hemovingdetil", $obj, ['hemoving_id']);
				$this->cmd_detil_del->bind(Database::$DbReport);
			}
			$stmt = $this->cmd_detil_del->getPreparedStatement();
			$params = $this->cmd_detil_del->getParameter($obj);
			$stmt->execute($params);

		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	protected function copyToTempHemovingHeader(string $hemoving_id, array $row) : void {
		try {
			$obj = $this->createObjectHeader($row);
			if (!isset($this->cmd_header_ins)) {
				$this->cmd_header_ins = new SqlInsert("tmp_hemoving", $obj);
				$this->cmd_header_ins->bind(Database::$DbReport);
			}
			$stmt = $this->cmd_header_ins->getPreparedStatement();
			$params = $this->cmd_header_ins->getParameter($obj);
			$stmt->execute($params);
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	protected function copyToTempHemovingDetil(string $hemoving_id, array $rows) : void {
		try {
			foreach ($rows as $row) {
				$obj = $this->createObjectDetil($row);
				if (!isset($this->cmd_detil_ins)) {
					$this->cmd_detil_ins = new SqlInsert("tmp_hemovingdetil", $obj);
					$this->cmd_detil_ins->bind(Database::$DbReport);
				}
				$stmt = $this->cmd_detil_ins->getPreparedStatement();
				$params = $this->cmd_detil_ins->getParameter($obj);
				$stmt->execute($params);
			}
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}


	private function createObjectHeader(array $row) : object {
		$obj = new \stdClass;
		$obj->hemoving_id = $row['hemoving_id'];
		$obj->hemoving_source = $row['hemoving_source'];
		$obj->hemoving_date = $row['hemoving_date'];
		$obj->hemoving_date_fr = $row['hemoving_date_fr'];
		$obj->hemoving_date_to = $row['hemoving_date_to'];
		$obj->hemoving_sn = $row['hemoving_sn'];
		$obj->hemoving_pol = $row['hemoving_pol'];
		$obj->hemoving_etd = $row['hemoving_etd'];
		$obj->hemoving_eta = $row['hemoving_eta'];
		$obj->hemoving_logisticcosttmp = $row['hemoving_logisticcosttmp'];
		$obj->hemoving_islogisticpost = $row['hemoving_islogisticpost'];
		$obj->hemoving_isprop = $row['hemoving_isprop'];
		$obj->hemoving_isproplock = $row['hemoving_isproplock'];
		$obj->hemoving_issend = $row['hemoving_issend'];
		$obj->hemoving_isrecv = $row['hemoving_isrecv'];
		$obj->hemoving_ispost = $row['hemoving_ispost'];
		$obj->hemoving_isdisabled = $row['hemoving_isdisabled'];
		$obj->hemoving_descr = $row['hemoving_descr'];
		$obj->hemoving_createby = $row['hemoving_createby'];
		$obj->hemoving_createdate = $row['hemoving_createdate'];
		$obj->hemoving_modifyby = $row['hemoving_modifyby'];
		$obj->hemoving_modifydate = $row['hemoving_modifydate'];
		$obj->hemoving_propby = $row['hemoving_propby'];
		$obj->hemoving_propdate = $row['hemoving_propdate'];
		$obj->hemoving_proplockby = $row['hemoving_proplockby'];
		$obj->hemoving_sendby = $row['hemoving_sendby'];
		$obj->hemoving_senddate = $row['hemoving_senddate'];
		$obj->hemoving_recvby = $row['hemoving_recvby'];
		$obj->hemoving_recvdate = $row['hemoving_recvdate'];
		$obj->hemoving_logisticpostby = $row['hemoving_logisticpostby'];
		$obj->hemoving_logisticpostdate = $row['hemoving_logisticpostdate'];
		$obj->hemoving_postby = $row['hemoving_postby'];
		$obj->hemoving_postdate = $row['hemoving_postdate'];
		$obj->hemovingtype_id = $row['hemovingtype_id'];
		$obj->region_id = $row['region_id'];
		$obj->region_id_out = $row['region_id_out'];
		$obj->branch_id_fr = $row['branch_id_fr'];
		$obj->branch_id_to = $row['branch_id_to'];
		$obj->convert_fr = $row['convert_fr'];
		$obj->convert_to = $row['convert_to'];
		$obj->rekanan_id = $row['rekanan_id'];
		$obj->currency_id = $row['currency_id'];
		$obj->currency_rate = $row['currency_rate'];
		$obj->invoice_id = $row['invoice_id'];
		$obj->disc_rate = $row['disc_rate'];
		$obj->season_id = $row['season_id'];
		$obj->ref_id = $row['ref_id'];
		$obj->channel_id = $row['channel_id'];
		$obj->rowid = $row['rowid'];

		return $obj;
	}


	private function createObjectDetil(array $row) : object {
		$obj = new \stdClass;
		$obj->hemoving_id = $row['hemoving_id'];
		$obj->hemovingdetil_line = $row['hemovingdetil_line'];
		$obj->heinv_id = $row['heinv_id'];
		$obj->heinv_art = $row['heinv_art'];
		$obj->heinv_mat = $row['heinv_mat'];
		$obj->heinv_col = $row['heinv_col'];
		$obj->heinv_name = $row['heinv_name'];
		$obj->heinv_price = $row['heinv_price'];
		$obj->heinv_disc = $row['heinv_disc'];
		$obj->heinv_box = $row['heinv_box'];
		$obj->heinv_invoiceqty = $row['heinv_invoiceqty'];
		$obj->heinv_invoiceid = $row['heinv_invoiceid'];
		$obj->ref_id = $row['ref_id'];
		$obj->ref_line = $row['ref_line'];
		$obj->A01 = $row['A01'];
		$obj->A02 = $row['A02'];
		$obj->A03 = $row['A03'];
		$obj->A04 = $row['A04'];
		$obj->A05 = $row['A05'];
		$obj->A06 = $row['A06'];
		$obj->A07 = $row['A07'];
		$obj->A08 = $row['A08'];
		$obj->A09 = $row['A09'];
		$obj->A10 = $row['A10'];
		$obj->A11 = $row['A11'];
		$obj->A12 = $row['A12'];
		$obj->A13 = $row['A13'];
		$obj->A14 = $row['A14'];
		$obj->A15 = $row['A15'];
		$obj->A16 = $row['A16'];
		$obj->A17 = $row['A17'];
		$obj->A18 = $row['A18'];
		$obj->A19 = $row['A19'];
		$obj->A20 = $row['A20'];
		$obj->A21 = $row['A21'];
		$obj->A22 = $row['A22'];
		$obj->A23 = $row['A23'];
		$obj->A24 = $row['A24'];
		$obj->A25 = $row['A25'];
		$obj->B01 = $row['B01'];
		$obj->B02 = $row['B02'];
		$obj->B03 = $row['B03'];
		$obj->B04 = $row['B04'];
		$obj->B05 = $row['B05'];
		$obj->B06 = $row['B06'];
		$obj->B07 = $row['B07'];
		$obj->B08 = $row['B08'];
		$obj->B09 = $row['B09'];
		$obj->B10 = $row['B10'];
		$obj->B11 = $row['B11'];
		$obj->B12 = $row['B12'];
		$obj->B13 = $row['B13'];
		$obj->B14 = $row['B14'];
		$obj->B15 = $row['B15'];
		$obj->B16 = $row['B16'];
		$obj->B17 = $row['B17'];
		$obj->B18 = $row['B18'];
		$obj->B19 = $row['B19'];
		$obj->B20 = $row['B20'];
		$obj->B21 = $row['B21'];
		$obj->B22 = $row['B22'];
		$obj->B23 = $row['B23'];
		$obj->B24 = $row['B24'];
		$obj->B25 = $row['B25'];
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
		$obj->rowid = $row['rowid'];

		return $obj;
	}
}