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
			$this->deleteRegisterData($heinvregister_id);
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

	private function deleteRegisterData($heinvregister_id) : void {
		try {

		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	private function copyToTempRegisterHeader(array $row) : void {
		try {

		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	private function copyToTempRegisterItems(array $rows) : void {
		try {

		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}
}
