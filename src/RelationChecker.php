<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;


class RelationChecker {

	private object $stmt_kategori_list;
	private object $stmt_heinvctgref_cek;

	private object $stmt_season_list;
	private object $stmt_seasonref_cek;

	private object $stmt_rekanan_list;
	private object $stmt_rekananref_cek;

	private object $stmt_brandref_cek;

	private object $stmt_regionbranch_list;
	private object $stmt_regionbranch_cek;


	private array $error_season = [];
	private array $error_partner = [];
	private array $error_kategori = [];
	private array $error_brand = [];
	private array $error_site = [];
	
	
	private array $map_season = [];
	private array $map_partner = [];
	private array $map_kategori = [];
	private array $map_site = [];


	public function cekBrand(string $region_id) : void {
		try {
			if (!isset($this->stmt_brandref_cek)) {
				$sql = "
					select * from mst_brandref 
					where 
						interface_id='TB' 
					and brandref_name='region_id' 
					and brandref_code=:region_id
				";
				$stmt = Database::$DbMain->prepare($sql);
				$this->stmt_brandref_cek = $stmt;
			}

			$stmt = $this->stmt_brandref_cek;
			$stmt->execute([":region_id" => $region_id]);
			$row_brand = $stmt->fetch();
			if ($row_brand==null) {
				$this->error_brand[] = $region_id;
			} else {
				$brand_id = $row_brand['brand_id'];
				$this->map_brand[$region_id] = $brand_id;
			}
		} catch (\Exception $ex) {
			throw $ex;
		}
	}
	public function cekKategori(string $region_id) : void {
		log::info("[$region_id] cek kategori");
		try {
			if (!isset($this->stmt_kategori_list)) {
				$sql = "select distinct heinvctg_id from tmp_heinv where region_id = :region_id";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_kategori_list = $stmt;
			}
			$stmt = $this->stmt_kategori_list;
			$stmt->execute([":region_id" => $region_id]);
			$rows = $stmt->fetchAll();
			foreach ($rows as $row) {
				$heinvctg_id = $row['heinvctg_id'];
				
				// cek heinvctg_id apakah sudah ada di fsn_merchctgref database main
				if (!isset($this->stmt_heinvctgref_cek)) {
					$sql = "
						select * from fsn_merchctgref 
						where 
							interface_id='TB' 
						and merchctgref_name='heinvctg_id' 
						and merchctgref_code=:heinvctg_id
					";
					$stmt = Database::$DbMain->prepare($sql);
					$this->stmt_heinvctgref_cek = $stmt;
				}

				$stmt = $this->stmt_heinvctgref_cek;
				$stmt->execute([":heinvctg_id" => $heinvctg_id]);
				$row_heinvctg = $stmt->fetch();
				if ($row_heinvctg==null) {
					$this->error_kategori[] = $heinvctg_id;
				} else {
					$merchctg_id = $row_heinvctg['merchctg_id'];
					$this->map_kategori[$heinvctg_id] = $merchctg_id;
				}
			}
		} catch (\Exception $ex) {
			throw $ex;
		}
	}




	public function cekSeason(string $region_id) : void {
		log::info("[$region_id] cek season");
		try {
			if (!isset($this->stmt_season_list)) {
				$sql = "select distinct season_id from tmp_heinv where region_id = :region_id";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_season_list = $stmt;
			}


			$stmt = $this->stmt_season_list;
			$stmt->execute([":region_id" => $region_id]);
			$rows = $stmt->fetchAll();
			foreach ($rows as $row) {
				$season_id = $row['season_id'];
				
				// cek season_id apakah sudah ada di fsn_merchsearef database main
				if (!isset($this->stmt_seasonref_cek)) {
					$sql = "
						select * from fsn_merchsearef 
						where 
							interface_id='TB' 
						and merchsearef_name='season_id' 
						and merchsearef_code=:season_id
					";
					$stmt = Database::$DbMain->prepare($sql);
					$this->stmt_seasonref_cek = $stmt;
				}

				$stmt = $this->stmt_seasonref_cek;
				$stmt->execute([":season_id" => $season_id]);
				$row_season = $stmt->fetch();
				if ($row_season==null) {
					$this->error_season[] = $season_id;
				} else {
					$merchsea_id = $row_season['merchsea_id'];
					$this->map_season[$season_id] = $merchsea_id;
				}
	
			}

		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public function cekRekanan(string $region_id) : void {
		log::info("[$region_id] cek rekanan");
		try {
			if (!isset($this->stmt_rekanan_list)) {
				$sql = "select distinct rekanan_id from tmp_heinv where region_id = :region_id";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_rekanan_list = $stmt;
			}


			$stmt = $this->stmt_rekanan_list;
			$stmt->execute([":region_id" => $region_id]);
			$rows = $stmt->fetchAll();
			foreach ($rows as $row) {
				$rekanan_id = $row['rekanan_id'];
				if ($rekanan_id==null) {
					continue;
				}

				// cek rekanan_id apakah sudah ada di mst_partner database main
				if (!isset($this->stmt_rekananref_cek)) {
					$sql = "
						select * from mst_partnerref 
						where 
							interface_id='TB' 
						and partnerref_name='rekanan_id' 
						and partnerref_code=:rekanan_id
					";
					$stmt = Database::$DbMain->prepare($sql);
					$this->stmt_rekananref_cek = $stmt;
				}

				$stmt = $this->stmt_rekananref_cek;
				$stmt->execute([":rekanan_id" => $rekanan_id]);
				$row_rekanan = $stmt->fetch();
				if ($row_rekanan==null) {
					$this->error_partner[] = $rekanan_id;
				} else {
					$partner_id = $row_rekanan['partner_id'];
					$this->map_partner[$rekanan_id] = $partner_id;
				}
			}
		} catch (\Exception $ex) {
			throw $ex;
		}
	}


	public function cekSite(string $region_id, string $periode_id) : void {
		log::info("[$region_id] cek site");
		try {
			if (!isset($this->stmt_regionbranch_list)) {
				$sql = "select distinct saldo_id from tmp_heinvsaldo where region_id = :region_id and periode_id = :periode_id";
				$stmt = Database::$DbReport->prepare($sql);
				$this->stmt_regionbranch_list = $stmt;
			}
			$stmt = $this->stmt_regionbranch_list;
			$stmt->execute([":region_id" => $region_id, ":periode_id" => $periode_id]);
			$rows = $stmt->fetchAll();
			foreach ($rows as $row) {
				$saldo_id = $row['saldo_id'];
				$branch_id = substr($saldo_id, 15, 7);

				if (!isset($this->stmt_regionbranch_cek)) {
					$sql = "
						select * from mst_siteref 
						where 
							interface_id='TB' 
						and siteref_name='regionbranch' 
						and siteref_code=:regionbranch
					";
					$stmt = Database::$DbMain->prepare($sql);
					$this->stmt_regionbranch_cek = $stmt;
				}


				$regionbranch = "$region_id:$branch_id";
				$stmt = $this->stmt_regionbranch_cek;
				$stmt->execute([":regionbranch" => $regionbranch]);
				$row_regionbranch = $stmt->fetch();
				if ($row_regionbranch==null) {
					$this->error_site[] = $regionbranch;
				} else {
					$site_id = $row_regionbranch['site_id'];
					$site_otherdata = $row_regionbranch['siteref_otherdata'];
					$data = json_decode($site_otherdata, true);
					if (!is_array($data)) {
						$data = [];
					}
					$data['site_id'] = $site_id;
					$this->map_site[$regionbranch] = $data;
				}

			}
		} catch (\Exception $ex) {
			throw $ex;
		}

	}


	public function getMappingErrorString() : string {
		$error = "";
		if (count($this->error_season)>0) {
			$error .= "Season: ".implode(", ", $this->error_season) . "; ";
		} 

		if (count($this->error_partner)>0) {
			$error .= "Partner: ".implode(", ", $this->error_partner) . "; ";
		}

		if (count($this->error_kategori)>0) {
			$error .= "Kategori: ".implode(", ", $this->error_kategori) . "; ";
		}

		if (count($this->error_kategori)>0) {
			$error .= "Kategori: ".implode(", ", $this->error_kategori) . "; ";
		}

		if (count($this->error_brand)>0) {
			$error .= "Brand: ".implode(", ", $this->error_brand) . "; ";
		}

		if (count($this->error_site)>0) {
			$error .= "Site: ".implode(", ", $this->error_site) . "; ";
		}

		return $error;
	}


	public function getMapRekanan() : array {
		return $this->map_partner;
	}

	public function getMapSeason() : array {
		return $this->map_season;
	}

	public function getMapKategori() : array {
		return $this->map_kategori;
	}	

	public function getMapBrand() : array {
		return $this->map_brand;
	}

	public function getMapSite() : array {
		return $this->map_site;
	}

}