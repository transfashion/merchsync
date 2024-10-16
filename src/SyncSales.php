<?php namespace TransFashion\MerchSync;



use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;


class SyncSales extends SyncBase {

	const bool SKIP_SYNC = false;

	const bool UNIMPLEMENTED_SYNC = true;

	private object $cmd_bon_header_del;
	private object $cmd_bon_items_del;
	private object $cmd_bon_payments_del;
	
	private object $cmd_bon_header_ins;
	private object $cmd_bon_items_ins;
	private object $cmd_bon_payments_ins;
	
	private object $cmd_rpt_sales;


	function __construct() {
		parent::__construct();
	}

	public function Sync(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'SL';
		
		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$endpoint = $this->TransBrowserUrl . '/getsales.php?id='. $id;
			$data = $this->getDataFromUrl($endpoint);

			// Debug::print($data);
			$bon_id = $data['header']['bon_id'];
			$this->deletePreviousSalesData($bon_id);
			$this->copyToTempSalesHeader($bon_id, $data['header']);
			$this->copyToTempSalesItems($bon_id, $data['items']);
			$this->copyToTempSalesPayments($bon_id, $data['payments']);
			$this->copyToSalesReport($bon_id, $data['view']);

			if (self::UNIMPLEMENTED_SYNC) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}


	private function deletePreviousSalesData(string $bon_id) : void {
		Log::info("Deleting previous Sales $bon_id");

		try {

			$obj = new \stdClass;
			$obj->bon_id = $bon_id;

			// hapus bon header temporary
			if (!isset($this->cmd_bon_header_del)) {
				$this->cmd_bon_header_del = new SqlDelete("tmp_bon", $obj, ['bon_id']);
				$this->cmd_bon_header_del->bind(Database::$DbReport);
			}
			$this->cmd_bon_header_del->execute($obj);

			// hapus bon items temporary
			if (!isset($this->cmd_bon_items_del)) {
				$this->cmd_bon_items_del = new SqlDelete("tmp_bonitem", $obj, ['bon_id']);
				$this->cmd_bon_items_del->bind(Database::$DbReport);
			}
			$this->cmd_bon_items_del->execute($obj);
			
			
			// hapus bon payment temporary 
			if (!isset($this->cmd_bon_payments_del)) {
				$this->cmd_bon_payments_del = new SqlDelete("tmp_bonpayment", $obj, ['bon_id']);
				$this->cmd_bon_payments_del->bind(Database::$DbReport);
			}
			$this->cmd_bon_payments_del->execute($obj);
			

			// hapus bon di rpt_sales 
			if (!isset($this->cmd_rpt_sales_del)) {
				$this->cmd_rpt_sales_del = new SqlDelete("rpt_sales", $obj, ['bon_id']);
				$this->cmd_rpt_sales_del->bind(Database::$DbReport);
			}
			$this->cmd_rpt_sales_del->execute($obj);
	

		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	private function copyToTempSalesHeader(string $bon_id, array $row) : void {
		try {
			$obj = $this->createObjectHeader($row);
			if (!isset($this->cmd_bon_header_ins)) {
				$this->cmd_bon_header_ins = new SqlInsert("tmp_bon", $obj);
				$this->cmd_bon_header_ins->bind(Database::$DbReport);
			}
			$this->cmd_bon_header_ins->execute($obj);
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	private function copyToTempSalesItems(string $bon_id, array $rows) : void {
		try {
			foreach ($rows as $row) {
				$obj = $this->createObjectItem($row);
				if (!isset($this->cmd_bon_items_ins)) {
					$this->cmd_bon_items_ins = new SqlInsert("tmp_bonitem", $obj);
					$this->cmd_bon_items_ins->bind(Database::$DbReport);
				}
				$this->cmd_bon_items_ins->execute($obj);
			}
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	private function copyToTempSalesPayments(string $bon_id, array $rows) : void {
		try {
			foreach ($rows as $row) {
				$obj = $this->createObjectPayment($row);				
				if (!isset($this->cmd_bon_payments_ins)) {
					$this->cmd_bon_payments_ins = new SqlInsert("tmp_bonpayment", $obj);
					$this->cmd_bon_payments_ins->bind(Database::$DbReport);
				}
				$this->cmd_bon_payments_ins->execute($obj);
		}
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	private function copyToSalesReport(string $bon_id, array $rows) : void {
		try {
			foreach ($rows as $row) {
				$obj = $this->createObjectReport($row);
				if (!isset($this->cmd_rpt_sales)) {
					$this->cmd_rpt_sales = new SqlInsert("rpt_sales", $obj);
					$this->cmd_rpt_sales->bind(Database::$DbReport);
				}
				$this->cmd_rpt_sales->execute($obj);
			}
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	
	private function createObjectHeader(array $row) : object {
		$obj = new \stdClass;
		$obj->bon_id = $row['bon_id'];
		$obj->bon_idext = $row['bon_idext'];
		$obj->bon_event = $row['bon_event'];
		$obj->bon_date = $row['bon_date'];
		$obj->bon_createby = $row['bon_createby'];
		$obj->bon_createdate = $row['bon_createdate'];
		$obj->bon_modifyby = $row['bon_modifyby'];
		$obj->bon_modifydate = $row['bon_modifydate'] ? $row['bon_modifydate'] : null;
		$obj->bon_isvoid = $row['bon_isvoid'];
		$obj->bon_voidby = $row['bon_voidby'];
		$obj->bon_voiddate = $row['bon_voiddate'] ? $row['bon_voiddate'] : null;
		$obj->bon_replacefromvoid = $row['bon_replacefromvoid'];
		$obj->bon_msubtotal = $row['bon_msubtotal'];
		$obj->bon_msubtvoucher = $row['bon_msubtvoucher'];
		$obj->bon_msubtdiscadd = $row['bon_msubtdiscadd'];
		$obj->bon_msubtredeem = $row['bon_msubtredeem'];
		$obj->bon_msubtracttotal = $row['bon_msubtracttotal'];
		$obj->bon_msubtotaltobedisc = $row['bon_msubtotaltobedisc'];
		$obj->bon_mdiscpaympercent = $row['bon_mdiscpaympercent'];
		$obj->bon_mdiscpayment = $row['bon_mdiscpayment'];
		$obj->bon_mtotal = $row['bon_mtotal'];
		$obj->bon_mpayment = $row['bon_mpayment'];
		$obj->bon_mrefund = $row['bon_mrefund'];
		$obj->bon_msalegross = $row['bon_msalegross'];
		$obj->bon_msaletax = $row['bon_msaletax'];
		$obj->bon_msalenet = $row['bon_msalenet'];
		$obj->bon_itemqty = $row['bon_itemqty'];
		$obj->bon_rowitem = $row['bon_rowitem'];
		$obj->bon_rowpayment = $row['bon_rowpayment'];
		$obj->bon_npwp = $row['bon_npwp'];
		$obj->bon_fakturpajak = $row['bon_fakturpajak'];
		$obj->bon_adddisc_authusername = $row['bon_adddisc_authusername'];
		$obj->bon_disctype = $row['bon_disctype'];
		$obj->customer_id = $row['customer_id'];
		$obj->customer_name = $row['customer_name'];
		$obj->customer_telp = $row['customer_telp'];
		$obj->customer_npwp = $row['customer_npwp'];
		$obj->customer_ageid = $row['customer_ageid'];
		$obj->customer_agename = $row['customer_agename'];
		$obj->customer_genderid = $row['customer_genderid'];
		$obj->customer_gendername = $row['customer_gendername'];
		$obj->customer_nationalityid = $row['customer_nationalityid'];
		$obj->customer_nationalityname = $row['customer_nationalityname'];
		$obj->customer_typename = $row['customer_typename'];
		$obj->customer_passport = $row['customer_passport'];
		$obj->customer_disc = $row['customer_disc'];
		$obj->voucher01_id = $row['voucher01_id'];
		$obj->voucher01_name = $row['voucher01_name'];
		$obj->voucher01_codenum = $row['voucher01_codenum'];
		$obj->voucher01_method = $row['voucher01_method'];
		$obj->voucher01_type = $row['voucher01_type'];
		$obj->voucher01_discp = $row['voucher01_discp'];
		$obj->salesperson_id = $row['salesperson_id'];
		$obj->salesperson_name = $row['salesperson_name'];
		$obj->pospayment_id = $row['pospayment_id'];
		$obj->pospayment_name = $row['pospayment_name'];
		$obj->posedc_id = $row['posedc_id'];
		$obj->posedc_name = $row['posedc_name'];
		$obj->machine_id = $row['machine_id'];
		$obj->region_id = $row['region_id'];
		$obj->branch_id = $row['branch_id'];
		$obj->syncode = $row['syncode'];
		$obj->syndate = $row['syndate'];
		$obj->rowid = $row['rowid'];
		$obj->site_id_from = $row['site_id_from'];

		return $obj;
	}

	private function createObjectItem(array $row) : object {
		$obj = new \stdClass;
		$obj->bon_id = $row['bon_id'];
		$obj->bondetil_line = $row['bondetil_line'];
		$obj->bondetil_gro = $row['bondetil_gro'];
		$obj->bondetil_ctg = $row['bondetil_ctg'];
		$obj->bondetil_art = $row['bondetil_art'];
		$obj->bondetil_mat = $row['bondetil_mat'];
		$obj->bondetil_col = $row['bondetil_col'];
		$obj->bondetil_size = $row['bondetil_size'];
		$obj->bondetil_descr = $row['bondetil_descr'];
		$obj->bondetil_qty = $row['bondetil_qty'];
		$obj->bondetil_mpriceori = $row['bondetil_mpriceori'];
		$obj->bondetil_mpricegross = $row['bondetil_mpricegross'];
		$obj->bondetil_mdiscpstd01 = $row['bondetil_mdiscpstd01'];
		$obj->bondetil_mdiscrstd01 = $row['bondetil_mdiscrstd01'];
		$obj->bondetil_mpricenettstd01 = $row['bondetil_mpricenettstd01'];
		$obj->bondetil_mdiscpvou01 = $row['bondetil_mdiscpvou01'];
		$obj->bondetil_mdiscrvou01 = $row['bondetil_mdiscrvou01'];
		$obj->bondetil_mpricecettvou01 = $row['bondetil_mpricecettvou01'];
		$obj->bondetil_vou01id = $row['bondetil_vou01id'];
		$obj->bondetil_vou01codenum = $row['bondetil_vou01codenum'];
		$obj->bondetil_vou01type = $row['bondetil_vou01type'];
		$obj->bondetil_vou01method = $row['bondetil_vou01method'];
		$obj->bondetil_vou01discp = $row['bondetil_vou01discp'];
		$obj->bondetil_mpricenett = $row['bondetil_mpricenett'];
		$obj->bondetil_msubtotal = $row['bondetil_msubtotal'];
		$obj->bondetil_rule = $row['bondetil_rule'];
		$obj->heinv_id = $row['heinv_id'];
		$obj->heinvitem_id = $row['heinvitem_id'];
		$obj->heinvitem_barcode = $row['heinvitem_barcode'];
		$obj->region_id = $row['region_id'];
		$obj->region_nameshort = $row['region_nameshort'];
		$obj->colname = $row['colname'];
		$obj->sizetag = $row['sizetag'];
		$obj->proc = $row['proc'];
		$obj->bon_idext = $row['bon_idext'];
		$obj->pricing_id = $row['pricing_id'];
		$obj->rowid = $row['rowid'];
		$obj->season_id = $row['season_id'];

		return $obj;
	}

	private function createObjectPayment(array $row) : object {
		$obj = new \stdClass;
		$obj->bon_id = $row['bon_id'];
		$obj->payment_line = $row['payment_line'];
		$obj->payment_cardnumber = $row['payment_cardnumber'];
		$obj->payment_cardholder = $row['payment_cardholder'];
		$obj->payment_mvalue = $row['payment_mvalue'];
		$obj->payment_mcash = $row['payment_mcash'];
		$obj->payment_installment = $row['payment_installment'];
		$obj->pospayment_id = $row['pospayment_id'];
		$obj->pospayment_name = $row['pospayment_name'];
		$obj->pospayment_bank = $row['pospayment_bank'];
		$obj->posedc_id = $row['posedc_id'];
		$obj->posedc_name = $row['posedc_name'];
		$obj->posedc_approval = $row['posedc_approval'];
		$obj->bon_idext = $row['bon_idext'];
		$obj->rowid = $row['rowid'];

		return $obj;
	}

	private function createObjectReport(array $row) : object {
		$obj = new \stdClass;
		$obj->bon_id = $row['bon_id'];
		$obj->bondetil_line = $row['bondetil_line'];
		$obj->bon_isvoid = $row['bon_isvoid'];
		$obj->bon_date = $row['bon_date'];
		$obj->bon_time = $row['bon_time'];
		$obj->bon_year = $row['bon_year'];
		$obj->bon_month = $row['bon_month'];
		$obj->bon_ym = $row['bon_ym'];
		$obj->bon_datestr = $row['bon_datestr'];
		$obj->bon_hour = $row['bon_hour'];
		$obj->bon_week = $row['bon_week'];
		$obj->bon_day = $row['bon_day'];
		$obj->bon_event = $row['bon_event'];
		$obj->payment_id = $row['payment_id'];
		$obj->payment_name = $row['payment_name'];
		$obj->payment_bank = $row['payment_bank'];
		$obj->payment_cardnumber = $row['payment_cardnumber'];
		$obj->payment_cardholder = $row['payment_cardholder'];
		$obj->payment_value = $row['payment_value'];
		$obj->otherpayment_value = $row['otherpayment_value'];
		$obj->machine_id = $row['machine_id'];
		$obj->bon_region_id = $row['bon_region_id'];
		$obj->region_id = $row['region_id'];
		$obj->branch_id = $row['branch_id'];
		$obj->brand_name = $row['brand_name'];
		$obj->site_id = $row['site_id'];
		$obj->site_name = $row['site_name'];
		$obj->city_id = $row['city_id'];
		$obj->area_id = $row['area_id'];
		$obj->sitemodel_id = $row['sitemodel_id'];
		$obj->location_id = $row['location_id'];
		$obj->location_name = $row['location_name'];
		$obj->salesperson_id = $row['salesperson_id'];
		$obj->salesperson_nik = $row['salesperson_nik'];
		$obj->salesperson_name = $row['salesperson_name'];
		$obj->customer_id = $row['customer_id'];
		$obj->customer_telp = $row['customer_telp'];
		$obj->customer_name = $row['customer_name'];
		$obj->customer_email = $row['customer_email'];
		$obj->heinv_id = $row['heinv_id'];
		$obj->heinvitem_id = $row['heinvitem_id'];
		$obj->heinvitem_barcode = $row['heinvitem_barcode'];
		$obj->heinv_art = $row['heinv_art'];
		$obj->heinv_mat = $row['heinv_mat'];
		$obj->heinv_col = $row['heinv_col'];
		$obj->bondetil_size = $row['bondetil_size'];
		$obj->heinv_name = $row['heinv_name'];
		$obj->heinv_iskonsinyasi = $row['heinv_iskonsinyasi'];
		$obj->heinvgro_id = $row['heinvgro_id'];
		$obj->heinvgro_name = $row['heinvgro_name'];
		$obj->heinvctg_id = $row['heinvctg_id'];
		$obj->heinvctg_name = $row['heinvctg_name'];
		$obj->heinvctg_costadd = $row['heinvctg_costadd'];
		$obj->heinvctg_mf = $row['heinvctg_mf'];
		$obj->invcls_id = $row['invcls_id'];
		$obj->invcls_name = $row['invcls_name'];
		$obj->invcls_descr = $row['invcls_descr'];
		$obj->invcls_gro = $row['invcls_gro'];
		$obj->heinv_fit = $row['heinv_fit'];
		$obj->heinv_colordescr = $row['heinv_colordescr'];
		$obj->heinv_gender = $row['heinv_gender'];
		$obj->pcp_line = $row['pcp_line'];
		$obj->pcp_gro = $row['pcp_gro'];
		$obj->pcp_ctg = $row['pcp_ctg'];
		$obj->mdflag = $row['mdflag'];
		$obj->heinv_gtype = $row['heinv_gtype'];
		$obj->season_group = $row['season_group'];
		$obj->season_id = $row['season_id'];
		$obj->heorder_id = $row['heorder_id'];
		$obj->heinv_lastrvid = $row['heinv_lastrvid'];
		$obj->heinv_lastrvdate = $row['heinv_lastrvdate'];
		$obj->rvage = $row['rvage'];
		$obj->heinv_lasttrid = $row['heinv_lasttrid'];
		$obj->heinv_lasttrdate = $row['heinv_lasttrdate'];
		$obj->trage = $row['trage'];
		$obj->promo_id = $row['promo_id'];
		$obj->promo_codenum = $row['promo_codenum'];
		$obj->promo_type = $row['promo_type'];
		$obj->promo_line = $row['promo_line'];
		$obj->promo_method = $row['promo_method'];
		$obj->promo_discp = $row['promo_discp'];
		$obj->sales_qty = $row['sales_qty'];
		$obj->sales_itemgross = $row['sales_itemgross'];
		$obj->sales_itemnett = $row['sales_itemnett'];
		$obj->sales_nett = $row['sales_nett'];
		$obj->currency_id = $row['currency_id'];
		$obj->heinv_fob = $row['heinv_fob'];
		$obj->heinv_lastcost = $row['heinv_lastcost'];
		$obj->cogs_estimated = $row['cogs_estimated'];
		$obj->cogs_final = $row['cogs_final'];

		return $obj;
	}

}