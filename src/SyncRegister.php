<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;

class SyncRegister extends SyncBase {

	private object $cmd_register_header_del;
	private object $cmd_register_items_del;
	private object $cmd_register_header_ins;
	private object $cmd_register_items_ins;


	function __construct() {
		parent::__construct();
	}

	public function Sync(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!='REG') {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan REG"); 
			}

			$endpoint = $this->url . '/getregister.php?id='. $id;
			$data = $this->getDataFromUrl($endpoint);

			$heinvregister_id = $data['header']['heinvregister_id'];
			$this->deleteRegisterData($heinvregister_id);
			$this->copyToTempRegisterHeader($heinvregister_id, $data['header']);
			$this->copyToTempRegisterItems($heinvregister_id, $data['items']);

		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	private function deleteRegisterData($heinvregister_id) : void {
		Log::info("Delete Register $heinvregister_id");

		try {

		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	private function copyToTempRegisterHeader(array $row) : void {
		Log::info("Copy Register Header $heinvregister_id");

		try {

		} catch (\Exception $ex) {
			Log::error("[HEAD] " . $ex->getMessage());
			throw $ex;
		}
	}

	private function copyToTempRegisterItems(array $rows) : void {
		Log::info("Copy Register Items $heinvregister_id");

		try {

		} catch (\Exception $ex) {
			Log::error("[ITEM] " . $ex->getMessage());
			throw $ex;
		}
	}
}
