<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlInsert;
use AgungDhewe\PhpSqlUtil\SqlUpdate;
use AgungDhewe\PhpSqlUtil\SqlSelect;

class SyncItem extends SyncBase {

	private \PDOStatement $stmt_heinv_select;
	private \PDOStatement $stmt_heinvitem_select;
	private \PDOStatement $stmt_merchctg_select;

	private \PDOStatement $stmt_itemstockcost_list;
	private \PDOStatement $stmt_itemstockcost_del;

	private \PDOStatement $stmt_itemstocksaldo_list;
	private \PDOStatement $stmt_itemstocksaldo_del;


	private SqlUpdate $cmd_heinvsaldo_periodeupdate;
	private SqlUpdate $cmd_heinvitemsaldo_periodeupdate;

	private SqlSelect $cmd_merchitem_check;
	private SqlSelect $cmd_mercharticle_check;

	private SqlSelect $cmd_itemstock_cek;
	private SqlInsert $cmd_itemstock_create;
	private SqlUpdate $cmd_itemstock_update;

	private SqlInsert $cmd_tmpmerchitem_ins;

	private SqlSelect $cmd_itemstockbarcode_cek;
	private SqlInsert $cmd_itemstockbarcode_create;

	private SqlInsert $cmd_mercharticle_create;
	private SqlUpdate $cmd_mercharticle_update;
	private SqlInsert $cmd_merchitem_create;
	private SqlUpdate $cmd_merchitem_update;
	
	private SqlSelect $cmd_itemstockperiode_cek;
	private SqlInsert $cmd_itemstockperiode_create;

	private SqlInsert $cmd_itemstockcost_create; 
	private SqlInsert $cmd_itemstocksaldo_create; 
	

	private RelationChecker $rc;
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
			$sql = "
				select 
				A.merchitem_id
				from 
				tmp_merchitem A left join fsn_merchitem B on B.merchitem_id = A.merchitem_id 
				WHERE 
				B.merchitem_id is null;
			";
			$stmt = Database::$DbMain->prepare($sql);
			$stmt->execute();
			$rows = $stmt->fetchAll();
			if (count($rows)>0) {
				foreach ($rows as $row) {
					log::print($row['merchitem_id']);
				}
				throw new \Exception("ada beberapa items yang belum di set di fsn_merchitem");
			}

		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public function ApplySaldoPeriode(string $merchsync_id, string $merchsync_doc, string $merchsync_type, string $periode_id ) : void {
		$currentSyncType = 'APPLY-SALDO';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$region_id = $id;
		
			$map_periode = $this->rc->getMapPeriode();
			$periodemo_id = $map_periode[$periode_id];

			// Update temporary saldo periode
			$this->updateTempPeriodemo($region_id, $periode_id, $periodemo_id);

			// masukkan data ke mst_itemstockperiode, mst_itemstockcost, mst_itemstocksaldo
			$this->SetupItemstockPeriode($region_id, $periodemo_id);

		} catch (\Exception $ex) {
			throw $ex;
		}
	}	




	private function SetupMerchArticle(string $mercharticle_id, array &$row) : void {
		try {
			$row['mercharticle_id'] = $mercharticle_id;

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
			
			// TODO: edit map brand, agar ada brand_id dan dept_id
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

			// cek dulu apakah $mercharticle_id sudah ada di table fns_mercharticle
			$obj = $this->createMercharticleObject($row);
			$cek = new \stdClass;
			$cek->mercharticle_id = $mercharticle_id;
			if (!isset($this->cmd_mercharticle_check)) {
				$this->cmd_mercharticle_check = new SqlSelect("fsn_mercharticle", $cek);
				$this->cmd_mercharticle_check->bind(Database::$DbMain);
			}
			$this->cmd_mercharticle_check->execute($cek);
			$row_mercharticle_cek = $this->cmd_mercharticle_check->fetch();
			if ($row_mercharticle_cek==null) {
				// mercharticle belum ada, insert
				if (!isset($this->cmd_mercharticle_create)) {
					$this->cmd_mercharticle_create = new SqlInsert("fsn_mercharticle", $obj);
					$this->cmd_mercharticle_create->bind(Database::$DbMain);
				}
				$this->cmd_mercharticle_create->execute($obj);

			} else {
				// mercharticle sudah ada, update
				if (!isset($this->cmd_mercharticle_update)) {
					$this->cmd_mercharticle_update = new SqlUpdate("fsn_mercharticle", $obj, ['mercharticle_id']);
					$this->cmd_mercharticle_update->bind(Database::$DbMain);
				}
				$this->cmd_mercharticle_update->execute($obj);
			}


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
			$obj = $this->createMerchitemObject($row);
			$cek = new \stdClass;
			$cek->merchitem_id = $merchitem_id;
			if (!isset($this->cmd_merchitem_check)) {
				$this->cmd_merchitem_check = new SqlSelect("fsn_merchitem", $cek);
				$this->cmd_merchitem_check->bind(Database::$DbMain);
			}
			$this->cmd_merchitem_check->execute($cek);
			$row_merchitem_cek = $this->cmd_merchitem_check->fetch();
			if ($row_merchitem_cek==null) {
				// merchitem belum ada, create
				if (!isset($this->cmd_merchitem_create)) {
					$this->cmd_merchitem_create = new SqlInsert("fsn_merchitem", $obj);
					$this->cmd_merchitem_create->bind(Database::$DbMain);
				}
				$this->cmd_merchitem_create->execute($obj);
			} else {
				// merchitem sudah ada, update
				if (!isset($this->cmd_merchitem_update)) {
					$this->cmd_merchitem_update = new SqlUpdate("fsn_merchitem", $obj, ['merchitem_id']);
					$this->cmd_merchitem_update->bind(Database::$DbMain);
				}
				$this->cmd_merchitem_update->execute($obj);
			}


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

	private function updateTempPeriodemo(string $region_id, string $periode_id, string $periodemo_id) : void {
		
		try {
			$obj = new \stdClass;
			$obj->region_id = $region_id;
			$obj->periode_id = $periode_id;
			$obj->periodemo_id = $periodemo_id;

			if (!isset($this->cmd_heinvsaldo_periodeupdate)) {
				$this->cmd_heinvsaldo_periodeupdate = new SqlUpdate("tmp_heinvsaldo", $obj, ['region_id','periode_id']);
				$this->cmd_heinvsaldo_periodeupdate->bind(Database::$DbReport);
			}

			if (!isset($this->cmd_heinvitemsaldo_periodeupdate)) {
				$this->cmd_heinvitemsaldo_periodeupdate = new SqlUpdate("tmp_heinvitemsaldo", $obj, ['region_id', 'periode_id']);
				$this->cmd_heinvitemsaldo_periodeupdate->bind(Database::$DbReport);
			}

			$this->cmd_heinvsaldo_periodeupdate->execute($obj);
			$this->cmd_heinvitemsaldo_periodeupdate->execute($obj);

		} catch (\Exception $ex) {
			throw $ex;
		}
	}


	private function SetupItemstockPeriode(string $region_id, string $periodemo_id) : void {
		log::info("SetupItemstock $region_id Periode $periodemo_id");

		try {
			$row = [
				'region_id' => $region_id,
				'periodemo_id' => $periodemo_id
			];


			// $obj = createItemstockPeriode($row);
			$map_unit = $this->rc->getMapUnit();
			$data = $map_unit[$region_id];
			$dept_id = $data['dept_id'];

			$row = [
				'itemstockperiode_id' => null,
				'dept_id' => $dept_id,
				'periodemo_id' => $periodemo_id
			];
			$obj = $this->createItemstockPeriodeObject($row);
			if (!isset($this->cmd_itemstockperiode_create)) {
				$this->cmd_itemstockperiode_create = new SqlInsert("mst_itemstockperiode", $obj);
				$this->cmd_itemstockperiode_create->bind(Database::$DbMain);
			}




			$cek = new \stdClass;
			$cek->dept_id = $dept_id;
			$cek->periodemo_id = $periodemo_id;
			if (!isset($this->cmd_itemstockperiode_cek)) {
				$this->cmd_itemstockperiode_cek = new SqlSelect("mst_itemstockperiode", $cek);
				$this->cmd_itemstockperiode_cek->bind(Database::$DbMain);
			}
			$this->cmd_itemstockperiode_cek->execute($cek);
			$row_itemstockperiode_cek = $this->cmd_itemstockperiode_cek->fetch();
			if (empty($row_itemstockperiode_cek)) {
				// buat baru
				$obj->itemstockperiode_id = uniqid();
				$this->cmd_itemstockperiode_create->execute($obj);
			}  else {
				$obj->itemstockperiode_id = $row_itemstockperiode_cek['itemstockperiode_id'];
			}


			// hapus dulu data itemstockcost dengan region dan periode ini
			if (!isset($this->stmt_itemstockcost_del)) {
				$sql = "delete from mst_itemstockcost where dept_id=:dept_id and itemstockperiode_id=:itemstockperiode_id";
				$stmt = Database::$DbMain->prepare($sql);
				$this->stmt_itemstockcost_del = $stmt;	
			}
			$stmt = $this->stmt_itemstockcost_del;
			$stmt->execute([
				':dept_id' => $dept_id,
				':itemstockperiode_id' => $obj->itemstockperiode_id
			]);


			// hapus itemstocksaldo
			if (!isset($this->stmt_itemstocksaldo_del)) {
				$sql = "delete from mst_itemstocksaldo where dept_id=:dept_id and itemstockperiode_id=:itemstockperiode_id";
				$stmt = Database::$DbMain->prepare($sql);
				$this->stmt_itemstocksaldo_del = $stmt;	
			}
			$stmt = $this->stmt_itemstocksaldo_del;
			$stmt->execute([
				':dept_id' => $dept_id,
				':itemstockperiode_id' => $obj->itemstockperiode_id
			]);

			$this->SetupItemstockCost($obj->itemstockperiode_id, $region_id, $dept_id, $periodemo_id);
			$this->SetupItemstockSaldo($obj->itemstockperiode_id, $region_id, $dept_id, $periodemo_id);

		} catch (\Exception $ex) {
			log::warning($ex->getMessage());
			throw $ex;
		}
	}

	private function SetupItemstockCost(string $itemstockperiode_id, string $region_id, string $dept_id, string $periodemo_id) : void {
		log::info("SetupItemstockCost $region_id Periode $periodemo_id");
		
		try {
			// ambil data dari temp saldo
			if (!isset($this->stmt_itemstockcost_list)) {
				$sql = "
					select
					heinvitem_id,
					sum(end_qty) as end_qty,
					sum(end_value) as end_value
					from tmp_heinvitemsaldo 
					where 
					region_id = :region_id and periodemo_id = :periodemo_id
					group by
					heinvitem_id				
				";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_itemstockcost_list = $stmt;
			}

			$stmt = $this->stmt_itemstockcost_list;
			$stmt->execute([
				':region_id' => $region_id,
				':periodemo_id' => $periodemo_id
			]);
			$rows = $stmt->fetchall();
			foreach ($rows as $row) {
				$qty = (int)$row['end_qty'];
				$value = (float)$row['end_value'];
				$cost = (float)0;
				if ($qty!=0) {
					$cost = $value / $qty ;
				}

				$row['itemstockperiode_id'] = $itemstockperiode_id;
				$row['dept_id'] = $dept_id;
				$row['periodemo_id'] = $periodemo_id;
				$row['itemstock_id'] = $row['heinvitem_id'];
				$row['valueperitem'] = $cost;
				$row['saldoqty'] = $qty;
				$row['saldovalue'] = $value;

				$obj = $this->createItemstockCostObject($row);	
				if (!isset($this->cmd_itemstockcost_create)) {
					$this->cmd_itemstockcost_create = new SqlInsert("mst_itemstockcost", $obj);
					$this->cmd_itemstockcost_create->bind(Database::$DbMain);
				}
				
				$obj->itemstockcost_id = uniqid();
				$this->cmd_itemstockcost_create->execute($obj);
			}
		} catch (\Exception $ex) {
			log::warning($ex->getMessage());
			throw $ex;
		}
	}

	private function SetupItemstockSaldo(string $itemstockperiode_id, string $region_id, string $dept_id,  string $periodemo_id) : void {
		log::info("SetupItemstockSaldo $region_id Periode $periodemo_id");
		
		try {
			$map_site = $this->rc->getMapSite();


			if (!isset($this->stmt_itemstocksaldo_list)) {
				$sql = "
					select
					branch_id,
					heinvitem_id,
					sum(end_qty) as end_qty,
					sum(end_value) as end_value
					from tmp_heinvitemsaldo 
					where 
					region_id = :region_id and periodemo_id = :periodemo_id
					group by
					branch_id, heinvitem_id				
				";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_itemstocksaldo_list = $stmt;
			}

			$stmt = $this->stmt_itemstocksaldo_list;
			$stmt->execute([
				':region_id' => $region_id,
				':periodemo_id' => $periodemo_id
			]);
			$rows = $stmt->fetchall();
			foreach ($rows as $row) {
				$qty = (int)$row['end_qty'];
				$value = (float)$row['end_value'];
				$cost = (float)0;
				if ($qty!=0) {
					$cost = $value / $qty ;
				}

				$branch_id = $row['branch_id'];
				$regionbranch = "$region_id:$branch_id";
				$data = $map_site[$regionbranch];
				$site_id = $data['site_id'];

				$row['itemstockperiode_id'] = $itemstockperiode_id;
				$row['dept_id'] = $dept_id;
				$row['site_id'] = $site_id;
				$row['periodemo_id'] = $periodemo_id;
				$row['itemstock_id'] = $row['heinvitem_id'];
				$row['valueperitem'] = $cost;
				$row['saldoqty'] = $qty;
				$row['saldovalue'] = $value;

				$obj = $this->createItemstockSaldoObject($row);	
				if (!isset($this->cmd_itemstocksaldo_create)) {
					$this->cmd_itemstocksaldo_create = new SqlInsert("mst_itemstocksaldo", $obj);
					$this->cmd_itemstocksaldo_create->bind(Database::$DbMain);
				}
				
				$obj->itemstocksaldo_id = uniqid();
				$this->cmd_itemstocksaldo_create->execute($obj);
			}
		} catch (\Exception $ex) {
			log::warning($ex->getMessage());
			throw $ex;
		}
	}


	public function CekData(string $batchid, string $region_id, string $periode_id) : void {
		try {
			$this->rc->cekKategori($region_id);
			$this->rc->cekSeason($region_id);
			$this->rc->cekRekanan($region_id);
			$this->rc->cekBrand($region_id);
			$this->rc->cekUnit($region_id);
			$this->rc->cekSite($region_id, $periode_id);
			$this->rc->cekPeriode($periode_id);
			
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
		$obj->itemstock_source = 'TB';
		$obj->itemstock_isdisabled = $row['heinv_isdisabled'];
		$obj->itemstock_issellable = 1;
		$obj->itemstock_priceori = $row['priceori'];
		$obj->itemstock_priceadj = $row['priceadj'];
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
		$obj->itemstock_lastrecvid = $row['heinv_lastrvid'];
		$obj->itemstock_lastrecvdate = $row['heinv_lastrvdate'];
		$obj->itemstock_lastrecvqty = $row['heinv_lastrvqty'];
		$obj->itemstock_lastcost = $row['heinv_lastcost'];
		$obj->_createby = '5effbb0a0f7d1';

		return $obj;
	}

	public function createMercharticleObject(array $row) : object {
		$obj = new \stdClass;

		$obj->mercharticle_id = $row['mercharticle_id'];
		$obj->mercharticle_art = $row['heinv_art'];
		$obj->mercharticle_mat = $row['heinv_mat'];
		$obj->mercharticle_col = $row['heinv_col'];
		$obj->mercharticle_name = $row['heinv_name'];
		$obj->mercharticle_descr = $row['heinv_webdescr'];
		$obj->mercharticle_isdisabled = $row['heinv_isdisabled'];
		$obj->mercharticle_pcpline = $row['pcp_line'];
		$obj->mercharticle_pcpgroup = $row['pcp_gro'];
		$obj->mercharticle_pcpcategory = $row['pcp_ctg'];
		$obj->mercharticle_gender = $row['heinv_gender'];
		$obj->mercharticle_fit = $row['fit'];
		$obj->mercharticle_hscodeship = $row['heinv_hscode_ship'];
		$obj->mercharticle_hscodeina = $row['heinv_hscode_ina'];
		$obj->mercharticle_labelname = $row['heinv_label'];
		$obj->mercharticle_labelproduct = $row['heinv_produk'];
		$obj->mercharticle_bahan = $row['heinv_bahan'];
		$obj->mercharticle_pemeliharaan = $row['heinv_pemeliharaan'];
		$obj->mercharticle_logo = $row['heinv_logo'];
		$obj->mercharticle_dibuatdi = $row['heinv_dibuatdi'];
		$obj->merchctg_id = $row['merchctg_id'];
		$obj->merchsea_id = $row['merchsea_id'];
		$obj->unit_id = $row['unit_id'];
		$obj->brand_id = $row['brand_id'];
		$obj->dept_id = $row['dept_id'];
		$obj->_createby = '5effbb0a0f7d1';

		return $obj;
	}

	public function createMerchItemObject(array $row) : object {
		$obj = new \stdClass;
		$obj->merchitem_id = $row['heinvitem_id']; 
		$obj->merchitem_size = $row['heinv_size'];
		$obj->merchitem_isdisabled = $row['heinv_isdisabled'];
		$obj->unit_id = $row['unit_id'];
		$obj->brand_id = $row['brand_id'];
		$obj->dept_id = $row['dept_id'];
		$obj->mercharticle_id = $row['heinv_id'];
		$obj->_createby = '5effbb0a0f7d1';
		return $obj;
	}

	public function createItemstockPeriodeObject(array $row) : object {
		$obj = new \stdClass;
		$obj->itemstockperiode_id = $row['itemstockperiode_id'];
		$obj->periodemo_id = $row['periodemo_id'];
		$obj->dept_id = $row['dept_id'];
		$obj->_createby = '5effbb0a0f7d1';
		return $obj;
	}

	private function createItemstockCostObject(array $row) : object {
		$obj = new \stdClass;
		$obj->itemstockcost_id = null;
		$obj->dept_id = $row['dept_id'];
		$obj->itemstock_id = $row['itemstock_id'];
		$obj->itemstockcost_valueperitem = $row['valueperitem'];
		$obj->itemstockcost_saldoqty = $row['saldoqty'];
		$obj->itemstockcost_saldovalue = $row['saldovalue'];
		$obj->itemstockperiode_id = $row['itemstockperiode_id'];
		$obj->_createby = '5effbb0a0f7d1';

		return $obj;
	}

	private function createItemstockSaldoObject(array $row) : object {
		$obj = new \stdClass;
		$obj->itemstocksaldo_id = null;
		$obj->dept_id = $row['dept_id'];
		$obj->site_id = $row['site_id'];
		$obj->itemstock_id = $row['itemstock_id'];
		$obj->itemstocksaldo_valueperitem = $row['valueperitem'];
		$obj->itemstocksaldo_qty = $row['saldoqty'];
		$obj->itemstocksaldo_value = $row['saldovalue'];
		$obj->itemstockperiode_id = $row['itemstockperiode_id'];
		$obj->_createby = '5effbb0a0f7d1';

		return $obj;
	}

}
