<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;
use AgungDhewe\PhpSqlUtil\SqlUpdate;
use AgungDhewe\PhpSqlUtil\SqlSelect;

class SyncItem extends SyncBase {

	private object $stmt_heinv_select;
	private object $stmt_heinvitem_select;
	private object $stmt_merchctg_select;


	private object $cmd_itemstock_cek;
	private object $cmd_itemstock_create;
	private object $cmd_itemstock_update;

	private object $cmd_tmpmerchitem_ins;

	private object $cmd_itemstockbarcode_cek;
	private object $cmd_itemstockbarcode_create;

	private object $rc;
	private array $fix_size = [];

	function __construct() {
		parent::__construct();
		$this->rc = new RelationChecker();


		// fix untuk size2 yang ukurannya salah dari TB nya
		$this->fix_size = [
			'TM17100028220' => 'N/A',
			'TM15070017305' => '',
			'TM15070017306' => 'N/A',
		];

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
			throw $ex;
		}
	}	

	public function CekHeinvPeriode(string $periode_id) : void {
		try {
			
			log::print("clear tmp_merchitem");
			$sql = "delete from tmp_merchitem";
			$stmt = Database::$DbMain->prepare($sql);
			$stmt->execute();


			$sql = "select distinct heinvitem_id from tmp_heinvitemsaldo where periode_id = :periode_id";
			$stmt = Database::$DbReport->prepare($sql);
			$stmt->execute([':periode_id' => $periode_id]);
			$rows = $stmt->fetchAll();

			// masukkan data dulu ke tmp_merchitem
			log::print("inserting data to tmp_merchitem");
			foreach ($rows as $row) {
				$heinvitem_id = $row['heinvitem_id'];
				$obj = new \stdClass;
				$obj->merchitem_id = $heinvitem_id;
				if (!isset($this->cmd_tmpmerchitem_ins)) {
					$this->cmd_tmpmerchitem_ins = new SqlInsert("tmp_merchitem", $obj);
					$this->cmd_tmpmerchitem_ins->bind(Database::$DbMain);
				}
				$this->cmd_tmpmerchitem_ins->execute($obj);
			}


			// cek apakah semua data di tmp_merchitem sudah ada di mst_merchitem



		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public function ApplySaldo(string $merchsync_id, string $merchsync_doc, string $merchsync_type, string $periode_id ) : void {
		$currentSyncType = 'APPLY-SALDO';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$region_id = $id;

			

		} catch (\Exception $ex) {
			throw $ex;
		}
	}	




	private function SetupMerchArticle(string $mercharticle_id, array &$row) : void {
		try {
			$map_kategori = $this->rc->getMapKategori();
			$map_season = $this->rc->getMapSeason();
			$map_rekanan = $this->rc->getMapRekanan();
			$map_brand = $this->rc->getMapBrand();

			$heinvctg_id = $row['heinvctg_id'];
			if (!array_key_exists($heinvctg_id, $map_kategori)) {
				throw new \Exception("kategori [$heinvctg_id] belum di map");
			}
			$merchctg_id = $map_kategori[$heinvctg_id];
			$row['merchctg_id'] = $merchctg_id;


			$season_id = $row['season_id'];
			if (!array_key_exists($season_id, $map_season)) {
				throw new \Exception("season [$season_id] belum di map");
			}
			$row['merchsea_id'] = $map_season[$season_id];

			$region_id = $row['region_id'];
			if (!array_key_exists($region_id, $map_brand)) {
				throw new \Exception("brand [$region_id] belum di map");
			}
			$row['brand_id'] = $map_brand[$region_id];


			$rekanan_id = $row['rekanan_id'];
			if ($rekanan_id!=null) {
				if (!array_key_exists($rekanan_id, $map_rekanan)) {
					throw new \Exception("rekanan [$rekanan_id] belum di map");
				}
				$row['partner_id'] = $map_rekanan[$rekanan_id];
			} else {
				$row['partner_id'] = null;
			}
			


			// ambil data kategori
			if (!isset($this->stmt_merchctg_select)) {
				$sql = "
					select merchctg_id, dept_id, itemgroup_id, itemclass_id, unit_id 
					from fsn_merchctg 
					where merchctg_id = :merchctg_id
				";
				$stmt = Database::$DbMain->prepare($sql);
				$this->stmt_merchctg_select = $stmt;
			}
			$stmt = $this->stmt_merchctg_select;
			$stmt->execute([":merchctg_id" => $merchctg_id]);
			$row_merchctg = $stmt->fetch();
			$row['dept_id'] = $row_merchctg['dept_id'];
			$row['itemgroup_id'] = $row_merchctg['itemgroup_id'];
			$row['itemclass_id'] = $row_merchctg['itemclass_id'];
			$row['unit_id'] = $row_merchctg['unit_id'];
			



			// setup mercharticle

		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	private function SetupMerchItem(string $merchitem_id, array $row) : void {	
		
		// CATATAN PENTING untuk DIINGAT!!!
		// dept_id, itemgroup_id, itemclass_id, unit_id,
		// didapatkan dari fungsi SetupMerchArticle yang dianggil sebelumnya
		// dengan data $row by reference
		

		try {
			// sebelum setup ke merchitem, harus diinput dulu ke itemstock
			// soalnya data merchitem_id merefrensi ke itemstock_id
			$itemstock_id = $merchitem_id;
			$this->SetupItemstock($itemstock_id, $row);

			// create merchitem

			


		} catch (\Exception $ex) {
			throw $ex;
		}
	}


	private function SetupItemstock(string $itemstock_id, array $row) : void {	
		// CATATAN PENTING untuk DIINGAT!!!
		// dept_id, itemgroup_id, itemclass_id, unit_id,
		// didapatkan dari fungsi SetupMerchArticle yang dianggil sebelumnya
		// dengan data $row by reference


		try {
			$obj = $this->createItemStockObject($row);

			$cek = new \stdClass;
			$cek->itemstock_id = $itemstock_id;
			if (!isset($this->cmd_itemstock_cek)) {
				$this->cmd_itemstock_cek = new SqlSelect("mst_itemstock", $cek);
				$this->cmd_itemstock_cek->bind(Database::$DbMain);
			}
			$this->cmd_itemstock_cek->execute($cek);
			$row_itemstock_cek = $this->cmd_itemstock_cek->fetch();
			if (empty($row_itemstock_cek)) {
				// itemstock belum ada, create
				if (!isset($this->cmd_itemstock_create)) {
					$this->cmd_itemstock_create = new SqlInsert("mst_itemstock", $obj);
					$this->cmd_itemstock_create->bind(Database::$DbMain);
				}
				$this->cmd_itemstock_create->execute($obj);
			} else {
				// itemstock ada, update
				if (!isset($this->cmd_itemstock_update)) {
					$this->cmd_itemstock_update = new SqlUpdate("mst_itemstock", $obj, ['itemstock_id']);
					$this->cmd_itemstock_update->bind(Database::$DbMain);
				}
				$this->cmd_itemstock_update->execute($obj);
			}


			// Create Barcode Local
			$barcode = $itemstock_id;
			$barcode_local = [
				'heinv_barcode' => $barcode,
				'brand_id' => $row['brand_id'],
				'itemstock_id' => $itemstock_id
			];
			$this->SetupItemstockBarcode($itemstock_id, $barcode, $barcode_local);

			// Create Barcode Principal
			$barcode = trim($row['heinv_barcode']);
			if (!empty($barcode)) {	
				$barcode_principal = [
					'heinv_barcode' => $barcode,
					'brand_id' => $row['brand_id'],
					'itemstock_id' => $itemstock_id
				];
				$this->SetupItemstockBarcode($itemstock_id, $barcode, $barcode_principal);
			}

			


		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}

	}


	private function SetupItemstockBarcode(string $itemstock_id, string $barcode, array $row) : void {	
		try {
			$cek = new \stdClass;
			$cek->itemstock_id = $itemstock_id;
			$cek->itemstockbarcode_text = $barcode;
			if (!isset($this->cmd_itemstockbarcode_cek)) {
				$this->cmd_itemstockbarcode_cek = new SqlSelect("mst_itemstockbarcode", $cek);
				$this->cmd_itemstockbarcode_cek->bind(Database::$DbMain);
			}
			$this->cmd_itemstockbarcode_cek->execute($cek);
			$row_itemstockbarcode_cek = $this->cmd_itemstockbarcode_cek->fetch();
			if (empty($row_itemstockbarcode_cek)) {
				// create new
				$obj = $this->createItemStockBarcodeObject($row);
				if (!isset($this->cmd_itemstockbarcode_create)) {
					$this->cmd_itemstockbarcode_create = new SqlInsert("mst_itemstockbarcode", $obj);
					$this->cmd_itemstockbarcode_create->bind(Database::$DbMain);
				}
				$this->cmd_itemstockbarcode_create->execute($obj);
			} else {
				if ($itemstock_id!=$row_itemstockbarcode_cek['itemstock_id']) {
					throw new \Exception('Itemstock barcode '.$barcode.' sudah ada di itemstock dengan kode itemstock_id berbeda ');
				}
			}

		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}




	public function CekData(string $batchid, string $region_id, string $periode_id) : void {
		try {
			$this->rc->cekKategori($region_id);
			$this->rc->cekSeason($region_id);
			$this->rc->cekRekanan($region_id);
			$this->rc->cekBrand($region_id);
			$this->rc->cekSite($region_id, $periode_id);
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public function getMappingErrorString() : string {
		return $this->rc->getMappingErrorString();
	}


	public function createItemStockBarcodeObject($row) {
		$obj = new \stdClass;

		$obj->itemstockbarcode_id = uniqid();
		$obj->itemstockbarcode_text = $row['heinv_barcode'];
		$obj->brand_id = $row['brand_id'];
		$obj->itemstock_id = $row['itemstock_id'];
		$obj->_createby = '5effbb0a0f7d1';

		return $obj;
	}


	public function createItemStockObject($row) {
		$obj = new \stdClass;
		$obj->itemstock_id = $row['heinvitem_id'];
		$obj->itemgroup_id = $row['itemgroup_id'];
		$obj->itemclass_id = $row['itemclass_id'];
		
		
		
		$art = trim($row['heinv_art']);
		$mat = trim($row['heinv_mat']);
		$col = trim($row['heinv_col']);
		$size = trim($row['heinv_size'])=='N/A' ? null : trim($row['heinv_size']);

		// patch size
		if (array_key_exists($obj->itemstock_id, $this->fix_size)) {
			$size = $this->fix_size[$obj->itemstock_id];
		}

		$code = [];
		if (!empty($art)) { $code[] = $art; }
		if (!empty($mat)) { $code[] = $mat;}
		if (!empty($col)) { $code[] = $col;}
		if (!empty($size)) { $code[] = $size;}
		$obj->itemstock_code = implode('-', $code);
		
		$obj->itemstock_name = $row['heinv_name'];

		$obj->itemstock_nameshort = $row['heinv_name'];
		$obj->itemstock_descr = $row['heinv_webdescr'];
		$obj->dept_id = $row['dept_id'];
		$obj->unit_id = $row['unit_id'];
		$obj->brand_id = $row['brand_id'];
		$obj->unitmeasurement_id = 'PCS';
		// $obj->itemstock_couchdbid = $row[''];
		// $obj->itemstock_picture = $row[''];
		$obj->itemstock_source = 'TB';
		// $obj->itemstock_isdisabled = $row[''];
		// $obj->itemstock_ishascompound = $row[''];
		$obj->itemstock_issellable = 1;
		$obj->itemstock_priceori = $row['priceori'];
		$obj->itemstock_priceadj = $row['priceadj'];
		// $obj->itemstock_priceadjdate = $row[''];
		$obj->itemstock_grossprice = $row['pricegross'];
		$obj->itemstock_disc = $row['pricedisc'];
		$obj->itemstock_discval = $row['pricegross'] - $row['price'];
		$obj->itemstock_isdiscvalue = $obj->itemstock_discval > 0 ? 1 : 0;
		$obj->itemstock_sellprice = $row['pricenett'];
		$obj->itemstock_estcost = $row['heinv_lastcost'];
		$obj->itemstock_weight = $row['heinv_weight'];
		$obj->itemstock_length = $row['heinv_length'];
		$obj->itemstock_width = $row['heinv_width'];
		$obj->itemstock_height = $row['heinv_height'];
		// $obj->itemstock_lastqty = $row['heinv_lastrvqty'];
		// $obj->itemstock_lastvalue = $row[''];
		// $obj->itemstock_lastqtyupdate = $row[''];
		// $obj->itemstock_isupdating = $row[''];
		// $obj->itemstock_updatebatch = $row[''];
		$obj->itemstock_lastrecvid = $row['heinv_lastrvid'];
		$obj->itemstock_lastrecvdate = $row['heinv_lastrvdate'];
		$obj->itemstock_lastrecvqty = $row['heinv_lastrvqty'];
		$obj->itemstock_lastcost = $row['heinv_lastcost'];
		$obj->_createby = '5effbb0a0f7d1';
		// $obj->itemstock_lastcostdate = $row[''];

		return $obj;
	}



}